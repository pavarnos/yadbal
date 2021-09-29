<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   03 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\DatabaseConnection;
use PHPUnit\Framework\TestCase;

class SchemaFromMySQLTest extends TestCase
{
    public function testEmpty(): void
    {
        $database = $this->createMock(DatabaseConnection::class);
        $subject  = new SchemaFromMySQL($database);
        $schema   = $subject->build();
        self::assertEquals(0, $schema->getTableCount());
    }
}
