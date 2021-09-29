<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

abstract class Index
{
    protected string $name = '';

    /** @var string eg UNIQUE KEY | PRIMARY */
    protected string $indexType = 'KEY';

    /** @var string[] columns in the index */
    protected array $column = [];

    public function __construct(string $name, string $firstColumn = '')
    {
        $this->name = $name;
        $this->addColumn(empty($firstColumn) ? $name : $firstColumn);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addColumn(string $name): self
    {
        $this->column[] = $name;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->column;
    }

    public function toSQLite(): string
    {
        return $this->toMySQL();
    }

    /**
     * generate index spec for CREATE TABLE statement
     */
    public function toMySQL(): string
    {
        return $this->indexType . ' ' . $this->name . ' (' . join(',', $this->column) . ')';
    }
}
