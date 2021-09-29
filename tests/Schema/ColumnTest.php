<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Column\ForeignKeyColumn;
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
}
