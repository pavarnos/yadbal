<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;

class FloatColumn extends Column
{
    const DEFAULT_WIDTH          = 9;
    const DEFAULT_DECIMAL_PLACES = 2;

    public function __construct(
        string $name,
        string $description,
        private int $width = self::DEFAULT_WIDTH,
        private int $decimalPlaces = self::DEFAULT_DECIMAL_PLACES
    ) {
        parent::__construct($name, $description, '0');
        $this->columnType = 'decimal(' . $width . ',' . $decimalPlaces . ')';
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces;
    }

    public function toSQLite(): string
    {
        return $this->name . ' float not null default 0';
    }
}
