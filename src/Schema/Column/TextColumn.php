<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;

class TextColumn extends Column
{
    public function __construct(string $name, string $description, string $columnType = 'text')
    {
        parent::__construct($name, $description, null);
        $this->columnType = $columnType;
        $this->allowNull  = true; // MySQL will not allow default values for text columns any more
    }

    public function toSQLite(): string
    {
        return $this->name . ' text default ""';
    }
}
