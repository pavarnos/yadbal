<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

class PrimaryKeyColumn extends IntegerColumn
{
    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, '0');
    }

    /**
     * generate column spec for CREATE TABLE statement
     */
    public function toMySQL(): string
    {
        return $this->name . ' ' . $this->columnType . ' NOT NULL AUTO_INCREMENT ' . $this->mySQLComment();
    }

    public function toSQLite(): string
    {
        return $this->name . ' integer primary key autoincrement';
    }
}
