<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;
use LSS\YADbal\Schema\Table;

class SetColumn extends Column
{
    /** @var string[] */
    private array $values;

    public function __construct(string $name, string $description, array $values)
    {
        parent::__construct($name, $description, null);
        $this->allowNull  = true;
        $this->values     = $values;
        $this->columnType = 'set(' . Table::quoteValueArray($values) . ')';
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function toSQLite(): string
    {
        return $this->name . ' text default ""';
    }
}
