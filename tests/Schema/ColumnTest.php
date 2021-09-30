<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Column\DateColumn;
use LSS\YADbal\Schema\Column\DateTimeColumn;
use LSS\YADbal\Schema\Column\EnumerationColumn;
use LSS\YADbal\Schema\Column\FloatColumn;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use LSS\YADbal\Schema\Column\IntegerColumn;
use LSS\YADbal\Schema\Column\SetColumn;
use LSS\YADbal\Schema\Column\StringColumn;
use LSS\YADbal\Schema\Index\PrimaryIndex;
use LSS\YADbal\Schema\Index\SecondaryIndex;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testCompareTo(): void
    {
        $a = new Column\TextColumn($name = 'abc', '');

        self::assertEquals($a::EQUAL, $a->compareTo(new Column\TextColumn($name, '')));
        self::assertEquals(
            $a::NOT_EQUAL,
            $a->compareTo(new Column\TextColumn($name . 'x', '')),
            'name is different'
        );
        self::assertEquals(
            $a::PARTIAL_MATCH,
            $a->compareTo(new Column\TextColumn($name, 'def')),
            'description is different'
        );

        self::assertEquals(
            $a::PARTIAL_MATCH,
            $a->compareTo(new Column\IntegerColumn($name, '')),
            'because type is different'
        );
        self::assertEquals(
            $a::NOT_EQUAL,
            $a->compareTo(new Column\IntegerColumn($name, 'def')),
            'name is different: cannot assume same just because they have the same type'
        );
        self::assertEquals(
            $a::NOT_EQUAL,
            $a->compareTo(new Column\IntegerColumn($name . 'x', '')),
            'because more than one thing is different'
        );
    }

    public function testForeignKeyNoConstraint(): void
    {
        $subject = new ForeignKeyColumn($name = 'a', $description = ForeignKeyColumn::RELATED_TEXT . ' ' . ($otherTable = 'b'), '');
        self::assertEquals($otherTable, $subject->getOtherTable());
        self::assertEquals(ForeignKeyColumn::ACTION_NO_ACTION, $subject->getOnDelete());
        self::assertEquals(ForeignKeyColumn::ACTION_NO_ACTION, $subject->getOnUpdate());
        self::assertEquals('', $subject->toMySQLForeignKey());
    }

    public function testForeignKeyConstraint(): void
    {
        $subject = new ForeignKeyColumn(
            $name = 'a',
            $description = 'b',
            $otherTable = 'c',
            $delete = ForeignKeyColumn::ACTION_CASCADE,
            $update = ForeignKeyColumn::ACTION_SET_NULL,
            $constraintName = 'ib_123'
        );
        self::assertEquals($otherTable, $subject->getOtherTable());
        self::assertEquals($delete, $subject->getOnDelete());
        self::assertEquals($update, $subject->getOnUpdate());
        self::assertEquals($constraintName, $subject->getConstraintName());
        self::assertStringContainsString('REFERENCES ' . $otherTable . ' (id)', $subject->toMySQLForeignKey());
        self::assertStringContainsString('ON DELETE ' . $delete, $subject->toMySQLForeignKey());
        self::assertStringContainsString('ON UPDATE ' . $update, $subject->toMySQLForeignKey());
    }

    public function testNullDefault(): void
    {
        self::assertStringNotContainsString('DEFAULT', (new DateColumn('foo', ''))->setDefault(null)->toMySQL());
        self::assertStringNotContainsString('DEFAULT', (new DateTimeColumn('foo', ''))->setDefault(null)->toMySQL());
    }

    public function testSQLiteGeneration(): void
    {
        self::assertEquals('foo text default "bar"', (new EnumerationColumn('foo', '', ['bar', 'baz']))->toSQLite());
        self::assertEquals('foo text default ""', (new SetColumn('foo', '', ['bar', 'baz']))->toSQLite());
        self::assertEquals('foo varchar(20) not null default ""', (new StringColumn('foo', '', '', 20))->toSQLite());
        self::assertEquals('foo float not null default 0', (new FloatColumn('foo', ''))->toSQLite());
        self::assertEquals('foo integer not null default 0', (new IntegerColumn('foo', ''))->toSQLite());
        self::assertEquals('', (new PrimaryIndex('foo'))->toSQLite());
        self::assertEquals('', (new SecondaryIndex('foo', ''))->toSQLite());
    }
}
