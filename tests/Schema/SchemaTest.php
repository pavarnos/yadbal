<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   02 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testEmpty(): void
    {
        $schema = new Schema();
        self::assertEquals(0, $schema->getTableCount());
        self::assertEquals([], $schema->getTableNames());
        self::assertEquals([], $schema->getTables());
        self::assertFalse($schema->hasTable('no_such'));
    }

    public function testTables(): void
    {
        $one    = new Table('one', 'the one');
        $two    = new Table('two', 'the second');
        $schema = new Schema();
        $schema->addTable($two);
        $schema->addTable($one);
        self::assertEquals(2, $schema->getTableCount());
        self::assertEquals(['two', 'one'], $schema->getTableNames());
        self::assertEquals(['two' => $two, 'one' => $one], $schema->getTables());
        self::assertTrue($schema->hasTable('one'));
    }
}
