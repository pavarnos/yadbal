<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

class Schema implements SchemaInterface
{
    /** @var Table[] table name => Table */
    private array $table = [];

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->table;
    }

    /**
     * @return string[]
     */
    public function getTableNames(): array
    {
        return array_keys($this->table);
    }

    public function hasTable(string $name): bool
    {
        return isset($this->table[$name]);
    }

    public function getTable(string $name): Table
    {
        assert(isset($this->table[$name]), 'wanted ' . $name);
        return $this->table[$name];
    }

    public function addTable(Table $table): self
    {
        $this->table[$table->getName()] = $table;
        return $this;
    }

    public function getTableCount(): int
    {
        return count($this->getTables());
    }
}
