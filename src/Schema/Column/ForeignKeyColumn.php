<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

use LSS\YADbal\Schema\SchemaException;

class ForeignKeyColumn extends IntegerColumn
{
    public const RELATED_TEXT = 'The related';

    public const ACTION_NO_ACTION = 'NO ACTION'; // same as restrict on mysql
    public const ACTION_CASCADE   = 'CASCADE';
    public const ACTION_RESTRICT  = 'RESTRICT';
    public const ACTION_SET_NULL  = 'SET NULL';

    public function __construct(
        string $name,
        string $description,
        private string $otherTable,
        private string $onDelete = self::ACTION_NO_ACTION,
        private string $onUpdate = self::ACTION_NO_ACTION,
        private string $constraintName = '' // blank = automatic
    ) {
        if (empty($this->otherTable)) {
            if (\Safe\preg_match('|^' . self::RELATED_TEXT . '\s+([-A-Za-z_]+)|', $description, $matches) > 0) {
                $this->otherTable = $matches[1];
            } else {
                throw new SchemaException('Cannot determine the related table');
            }
        }
        if (empty($description)) {
            $description = self::RELATED_TEXT . ' ' . $otherTable;
        }
        parent::__construct($name, $description);
    }

    public function getOtherTable(): string
    {
        return $this->otherTable;
    }

    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    public function getConstraintName(): string
    {
        return $this->constraintName;
    }

    public function toMySQLForeignKey(): string
    {
        if ($this->onUpdate == self::ACTION_NO_ACTION && $this->onDelete == self::ACTION_NO_ACTION) {
            // disable foreign key constraints
            return '';
        }
        return '(' . $this->name . ') REFERENCES ' . $this->otherTable . ' (id)' .
            ' ON DELETE ' . $this->onDelete .
            ' ON UPDATE ' . $this->onUpdate;
    }
}
