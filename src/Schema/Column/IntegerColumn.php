<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;

class IntegerColumn extends Column
{
    public function __construct(string $name, string $description, string $default = '0', string $columnType = 'int')
    {
        parent::__construct($name, $description, $default);
        $this->columnType = $columnType;
    }

    public function toSQLite(): string
    {
        return $this->name . ' integer not null default 0';
    }
}
