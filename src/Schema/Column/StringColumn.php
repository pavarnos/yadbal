<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;

class StringColumn extends Column
{
    private int $length;

    public function __construct(string $name, string $description, ?string $default, int $length)
    {
        parent::__construct($name, $description, $default);
        $this->columnType = 'varchar(' . $length . ')';
        $this->length     = $length;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function toSQLite(): string
    {
        return $this->name . ' varchar(' . $this->length . ') not null default ""';
    }
}
