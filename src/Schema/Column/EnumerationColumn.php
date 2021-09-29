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

class EnumerationColumn extends Column
{
    /** @var string[] */
    private array $values;

    public function __construct(string $name, string $description, array $values, string $default = null)
    {
        parent::__construct($name, $description, $default ?? $values[0]);
        $this->values     = $values;
        $this->columnType = 'enum(' . Table::quoteValueArray($values) . ')';
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function toSQLite(): string
    {
        return $this->name . ' text default "' . $this->default . '"';
    }
}
