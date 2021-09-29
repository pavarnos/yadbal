<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\DatabaseException;
use LSS\YADbal\Schema\Column\BooleanColumn;
use LSS\YADbal\Schema\Column\EnumerationColumn;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use LSS\YADbal\Schema\Column\IntegerColumn;
use LSS\YADbal\Schema\Column\PrimaryKeyColumn;
use LSS\YADbal\Schema\Column\StringColumn;
use LSS\YADbal\Schema\Column\TextColumn;
use LSS\YADbal\Schema\Index\PrimaryIndex;
use LSS\YADbal\Schema\Index\SecondaryIndex;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testConstructor(): void
    {
        $columns = [
            'id'    => new IntegerColumn('id', 'v desc'),
            'words' => new StringColumn('words', 'word desc', '', 20),
        ];
        $indexes = ['id' => new PrimaryIndex()];
        $table   = new Table($name = 'the_name', $description = 'the description', $columns, $indexes);
        self::assertEquals($name, $table->getName());
        self::assertEquals($description, $table->getDescription());
        self::assertEquals($columns, $table->getColumns());
        self::assertEquals($indexes, $table->getIndexes());
        self::assertFalse($table->hasColumn('no_such'));
        foreach ($columns as $id => $column) {
            self::assertTrue($table->hasColumn($id));
            self::assertEquals($column, $table->getColumn($id));
        }
        self::assertEquals([], $table->getForeignKeys(), 'none defined');
    }

    public function testEnumValues(): void
    {
        $columns = [
            'id'    => new IntegerColumn('id', 'v desc'),
            'words' => new EnumerationColumn('words', 'word desc', $values = ['one', 'two', 'three']),
        ];
        $table   = new Table('the_name', 'the description', $columns);
        self::assertEquals(\Safe\array_combine($values, $values), $table->getEnumFieldValues('words'));
        try {
            $table->getEnumFieldValues('id');
            self::fail('Expected exception');
        } catch (DatabaseException $ex) {
        }
    }

    public function testIndexes(): void
    {
        $columns = [
            'id'    => new PrimaryKeyColumn('id', 'id desc'),
            'value' => new BooleanColumn('value', 'v desc'),
            'words' => new StringColumn('words', 'word desc', '', 20),
        ];
        $indexes = [new PrimaryIndex('id'), new SecondaryIndex('value2', 'value')];
        $table   = new Table('the_name', 'the description', $columns, $indexes);
        $sql     = $table->toMySQL();
        self::assertMatchesRegularExpression('|id .* auto_increment|i', $sql);
        self::assertStringContainsString('PRIMARY KEY  (id)', $sql);
        self::assertStringContainsString('KEY value2 (value)', $sql);
    }

    public function testForeignKeys(): void
    {
        $columns = [
            'id'    => new IntegerColumn('id', 'v desc'),
            'fk1'   => $fk1 = new ForeignKeyColumn('fk1', '', 'other'),
            'words' => new StringColumn('words', 'word desc', '', 20),
            'fk2'   => $fk2 = new ForeignKeyColumn('fk2', '', 'other', ForeignKeyColumn::ACTION_CASCADE),
            'blah'  => $blah = new TextColumn('blah', 'really'),
        ];
        $table   = new Table('the_name', 'the description', $columns);
        $sql     = $table->toMySQL();
        self::assertEquals(['fk1' => $fk1, 'fk2' => $fk2], $table->getForeignKeys());
        self::assertStringNotContainsString('FOREIGN KEY (fk1)', $sql);
        self::assertStringContainsString('FOREIGN KEY (fk2) REFERENCES other', $sql);
    }
}
