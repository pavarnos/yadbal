<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

interface SchemaInterface
{
    /**
     * return the number of tables added by add()
     * @return int
     */
    public function getTableCount(): int;

    /**
     * @param string $name
     * @return bool true if the table exists in the schema
     */
    public function hasTable(string $name): bool;

    /**
     * return the table
     * @param string $name of the table to return
     * @return Table table in the database
     */
    public function getTable(string $name): Table;

    /**
     * return all the tables indexed by name
     * @return Table[] table in the database
     */
    public function getTables(): array;

    /**
     * @return string[]
     */
    public function getTableNames(): array;
}
