<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

/**
 * metadata about a column, used to
 * - generate and sync the database schema with MySQL or other DBMS
 * - produce custom reports (isVisible)
 * - configure data entry form fields
 * - control what gets kept when archiving
 */
abstract class Column
{
    protected const COLUMN_TYPE_KEY = 'int';

    public const NOT_EQUAL     = 0; // returned from compareTo()
    public const EQUAL         = 1; // returned from compareTo()
    public const PARTIAL_MATCH = 2; // returned from compareTo()

    /** @var string eg varchar */
    protected string $columnType = '';

    /** @var string eg auto_increment */
    protected string $extra = '';

    /** @var bool */
    protected bool $allowNull = false;

    public function __construct(protected string $name, protected string $description, protected ?string $default)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setAllowNull(bool $allowNull = true): static
    {
        $this->allowNull = $allowNull;
        return $this;
    }

    public function setDefault(?string $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function toSQLite(): string
    {
        return $this->name . ' ' .
            $this->columnType .
            (is_null($this->default) ? '' : (' DEFAULT \'' . $this->default . '\''));
    }

    /**
     * generate column spec for CREATE TABLE statement
     */
    public function toMySQL(): string
    {
        assert(!empty($this->columnType));

        $sql = $this->name . ' ' .
            $this->columnType .
            (empty($this->calculateExpression) ? '' : (' as (' . $this->calculateExpression . ') STORED '));
        if ($this->allowNull) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL ' . $this->mySQLDefault();
        }
        $sql .= $this->mySQLComment();
        return $sql;
    }

    /**
     * @param Column $other
     * @return int Column::EQUAL if the columns are an exact match,
     * | Column::NOT_EQUAL if nothing matches
     * | Column::PARTIAL_MATCH if enough matches that we can assume it was updated
     */
    public function compareTo(Column $other): int
    {
        // could also look at extra or allow null
        if ($this->description == '' && $other->description == '') {
            // if no description, can compare type only
            if ($this->name != $other->name) {
                return self::NOT_EQUAL;
            }
            return $this->columnType == $other->columnType ? self::EQUAL : self::PARTIAL_MATCH;
        }

        if ($this->name == $other->name) {
            if ($this->columnType != $other->columnType) {
                return $this->description == $other->description ? self::PARTIAL_MATCH : self::NOT_EQUAL;
            }
            return $this->description == $other->description ? self::EQUAL : self::PARTIAL_MATCH;
        } else {
            if ($this->columnType != $other->columnType) {
                // its tempting to allow a difference in description to generate a partial match,
                // but if you do this a sequence of columns of the same type will all get altered
                // and it becomes impossible to add a new column at the start of the table
                // return $this->description == $other->description ? self::PARTIAL_MATCH : self::NOT_EQUAL;
                return self::NOT_EQUAL;
            }
            return $this->description == $other->description ? self::PARTIAL_MATCH : self::NOT_EQUAL;
        }
    }

    protected function mySQLComment(): string
    {
        if (empty($this->description)) {
            return '';
        }
        return ' COMMENT ' . Table::quoteDescription($this->description);
    }

    protected function mySQLDefault(): string
    {
        return (is_null($this->default) ? '' : (' DEFAULT \'' . $this->default . '\''));
    }
}
