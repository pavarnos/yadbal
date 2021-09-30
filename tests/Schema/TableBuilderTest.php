<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Column\DateColumn;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use LSS\YADbal\Schema\Column\StringColumn;
use LSS\YADbal\Schema\Index\PrimaryIndex;
use LSS\YADbal\Schema\Index\SecondaryIndex;
use PHPUnit\Framework\TestCase;

class TableBuilderTest extends TestCase
{
    private const NAME               = 'table_name';
    private const DESCRIPTION        = 'table description';
    private const COLUMN_NAME        = 'col_name';
    private const COLUMN_DESCRIPTION = 'column description';

    public function testAddPrimaryKeyColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addPrimaryKeyColumn()->build();
        $column = $table->getColumn('id');
        self::assertInstanceOf(Column\IntegerColumn::class, $column);
        self::assertInstanceOf(PrimaryIndex::class, $table->getIndexes()[''], 'primary key has no index name');
    }

    public function testAddOneToOneKeyColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addOneToOneKeyColumn($otherName = 'other_table')->build();
        $column = $table->getColumn('id');
        self::assertInstanceOf(Column\ForeignKeyColumn::class, $column);
        self::assertEquals($otherName, $column->getOtherTable());
        self::assertInstanceOf(SecondaryIndex::class, $table->getIndexes()['id']);
    }

    public function testAddForeignKeyColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addForeignKeyColumn(
                $otherName = 'other_table',
                self::COLUMN_DESCRIPTION,
                '',
                $action = ForeignKeyColumn::ACTION_CASCADE
            )
            ->build();
        $column = $table->getColumn($name = $otherName . '_id');
        self::assertInstanceOf(Column\ForeignKeyColumn::class, $column);
        self::assertEquals($otherName, $column->getOtherTable());
        self::assertEquals($name, $column->getName());
        self::assertStringContainsString(self::COLUMN_DESCRIPTION, $column->getDescription());
        self::assertStringContainsString(
            Column\ForeignKeyColumn::RELATED_TEXT . ' ' . $otherName,
            $column->getDescription()
        );
        self::assertStringContainsString('ON DELETE ' . $action, $column->toMySQLForeignKey());
        self::assertInstanceOf(SecondaryIndex::class, $table->getIndexes()[$name]);
    }

    public function testAddForeignKeyColumnNullable(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addForeignKeyColumnNullable($otherName = 'other_table', self::COLUMN_DESCRIPTION)
            ->build();
        $column = $table->getColumn($name = $otherName . '_id');
        self::assertInstanceOf(Column\ForeignKeyColumn::class, $column);
        self::assertEquals($otherName, $column->getOtherTable());
        self::assertEquals($name, $column->getName());
        self::assertStringContainsString(self::COLUMN_DESCRIPTION, $column->getDescription());
        self::assertStringContainsString(
            Column\ForeignKeyColumn::RELATED_TEXT . ' ' . $otherName,
            $column->getDescription()
        );
        self::assertStringContainsString(
            'ON DELETE ' . ForeignKeyColumn::ACTION_SET_NULL,
            $column->toMySQLForeignKey()
        );
        self::assertInstanceOf(SecondaryIndex::class, $table->getIndexes()[$name]);
    }

    public function testAddIntegerColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addIntegerColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\IntegerColumn::class);
    }

    public function testAddBooleanColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addBooleanColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\BooleanColumn::class);
    }

    public function testAddFloatColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addFloatColumn(self::COLUMN_NAME, $width = 12, $dp = 3, self::COLUMN_DESCRIPTION)->build();
        /** @var Column\FloatColumn $column */
        $column = $this->checkColumn($table, Column\FloatColumn::class);
        self::assertStringContainsString('decimal(' . $width . ',' . $dp, $column->toMySQL());
        self::assertEquals($width, $column->getWidth());
        self::assertEquals($dp, $column->getDecimalPlaces());
    }

    public function testAddStringColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addStringColumn(self::COLUMN_NAME, $length = 123, self::COLUMN_DESCRIPTION)->build();
        /** @var Column\StringColumn $column */
        $column = $this->checkColumn($table, Column\StringColumn::class);
        self::assertStringContainsString('varchar(' . $length, $column->toMySQL());
        self::assertEquals($length, $column->getLength());
    }

    public function testAddTextColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addTextColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\TextColumn::class);
    }

    public function testAddMediumTextColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addMediumTextColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $column = $this->checkColumn($table, Column\TextColumn::class);
        self::assertStringContainsString('mediumtext', $column->toMySQL());
    }

    public function testAddCalculatedColumn(): void
    {
        $inner  = new StringColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION, '', 5);
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addCalculatedColumn($inner, $formula = 'soundex(`first_name`)')->build();
        $column = $this->checkColumn($table, Column\CalculatedColumn::class);
        self::assertStringContainsString($formula, $column->toMySQL());
        self::assertStringContainsString('STORED', $column->toMySQL(), 'defaults to stored');

        // check that null is passed through to inner
        self::assertStringContainsString('NOT NULL', $inner->toMySQL());
        $column->setAllowNull();
        self::assertStringNotContainsString('NOT NULL', $inner->toMySQL());
    }

    public function testAddJsonColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addJsonColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\JsonColumn::class);
    }

    public function testAddEnumerationColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addEnumerationColumn(self::COLUMN_NAME, $values = ['one', 'two'], self::COLUMN_DESCRIPTION)->build();
        /** @var Column\EnumerationColumn $column */
        $column = $this->checkColumn($table, Column\EnumerationColumn::class);
        self::assertEquals($values, $column->getValues());
    }

    public function testAddSetColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addSetColumn(self::COLUMN_NAME, $values = ['one', 'two'], self::COLUMN_DESCRIPTION)->build();
        /** @var Column\SetColumn $column */
        $column = $this->checkColumn($table, Column\SetColumn::class);
        self::assertEquals($values, $column->getValues());
    }

    public function testAddDateColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addDateColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\DateColumn::class);
        self::assertStringNotContainsString(DateColumn::CURRENT_TIMESTAMP, $table->toMySQL());
    }

    public function testAddDateDefaultColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addDateColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION, DateColumn::CURRENT_TIMESTAMP)->build();
        $this->checkColumn($table, Column\DateColumn::class);
        self::assertStringContainsString(DateColumn::CURRENT_TIMESTAMP, $table->toMySQL());
    }

    public function testAddDateTimeColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addDateTimeColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\DateTimeColumn::class);
        self::assertStringNotContainsString(DateColumn::CURRENT_TIMESTAMP, $table->toMySQL());
    }

    public function testAddDateTimeDefaultColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addDateTimeColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION, DateColumn::CURRENT_TIMESTAMP)->build();
        $this->checkColumn($table, Column\DateTimeColumn::class);
        self::assertStringContainsString(DateColumn::CURRENT_TIMESTAMP, $table->toMySQL());
    }

    public function testAddCurrencyColumn(): void
    {
        $table = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addCurrencyColumn(self::COLUMN_NAME, self::COLUMN_DESCRIPTION)->build();
        $this->checkColumn($table, Column\FloatColumn::class);
    }

    public function testAddDateUpdatedColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))->addDateUpdatedColumn()->build();
        $column = $table->getColumn('date_updated');
        self::assertInstanceOf(Column\DateTimeColumn::class, $column);
        self::assertStringContainsString(DateColumn::CURRENT_TIMESTAMP, $table->toMySQL());
    }

    public function testAddDateCreatedColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))->addDateCreatedColumn()->build();
        $column = $table->getColumn('date_created');
        self::assertInstanceOf(Column\DateTimeColumn::class, $column);
        self::assertStringContainsString(DateColumn::CURRENT_TIMESTAMP, $table->toMySQL());
    }

    public function testAddDisplayOrderColumn(): void
    {
        $table  = (new TableBuilder(self::NAME, self::DESCRIPTION))->addDisplayOrderColumn()->build();
        $column = $table->getColumn('display_order');
        self::assertInstanceOf(Column\IntegerColumn::class, $column);
        self::assertArrayHasKey('display_order', $table->getIndexes());
    }

    public function testAddStandardIndexSingle(): void
    {
        $table   = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addIntegerColumn(self::COLUMN_NAME)
            ->addStandardIndex('foo')
            ->build();
        $indexes = $table->getIndexes();
        self::assertEquals(['foo'], $indexes['foo']->getColumns());
        self::assertStringNotContainsString('UNIQUE', $indexes['foo']->toMySQL());
    }

    public function testAddStandardIndexMulti(): void
    {
        $table   = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addIntegerColumn(self::COLUMN_NAME)
            ->addStandardIndex('foo', $columns = ['bar', 'baz'])
            ->build();
        $indexes = $table->getIndexes();
        self::assertEquals($columns, $indexes['foo']->getColumns());
        self::assertStringNotContainsString('UNIQUE', $indexes['foo']->toMySQL());
    }

    public function testAddUniqueIndex(): void
    {
        $table   = (new TableBuilder(self::NAME, self::DESCRIPTION))
            ->addIntegerColumn(self::COLUMN_NAME)
            ->addUniqueIndex('foo', $columns = ['bar', 'baz'])
            ->build();
        $indexes = $table->getIndexes();
        self::assertEquals($columns, $indexes['foo']->getColumns());
        self::assertStringContainsString('UNIQUE', $indexes['foo']->toMySQL());
    }

    /**
     * @param Table                $table
     * @param class-string<Column> $class
     * @return Column|Column\ForeignKeyColumn|Column\IntegerColumn|Column\StringColumn
     */
    private function checkColumn(Table $table, string $class): Column
    {
        $column = $table->getColumn(self::COLUMN_NAME);
        self::assertEquals(self::COLUMN_NAME, $column->getName());
        self::assertEquals(self::COLUMN_DESCRIPTION, $column->getDescription());
        self::assertInstanceOf($class, $column);
        return $column;
    }
}
