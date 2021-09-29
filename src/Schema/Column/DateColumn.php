<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;

class DateColumn extends Column
{
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    public function __construct(string $name, string $description, string $default = null)
    {
        parent::__construct($name, $description, $default);
        $this->columnType = 'date';
    }

    protected function mySQLDefault(): string
    {
        if ($this->default === self::CURRENT_TIMESTAMP) {
            return ' DEFAULT CURRENT_TIMESTAMP ';
        }
        return parent::mySQLDefault();
    }
}
