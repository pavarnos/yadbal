<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\Column;

class CalculatedColumn extends Column
{
    private bool $isStored = true;

    private Column $inner;

    /**
     * you may need to add extra brackets in the $expression because MySQL likes to add lots
     * @param Column $inner
     * @param string $expression
     */
    public function __construct(Column $inner, string $expression)
    {
        parent::__construct($inner->name, $inner->description, $inner->default);
        $this->inner       = $inner;
        $this->columnType  = $inner->columnType . ' as (' . $expression . ')';
        $this->allowNull   = $inner->allowNull;
    }

    public function toMySQL(): string
    {
        return $this->inner->name . ' ' . $this->columnType . ' ' .
            ($this->isStored ? 'STORED ' : 'VIRTUAL ') .
            ($this->inner->allowNull ? '' : ' NOT NULL') .
            $this->inner->mySQLComment();
    }

    public function setAllowNull(bool $allowNull = true): static
    {
        $this->inner->allowNull = $allowNull;
        return parent::setAllowNull($allowNull);
    }
}
