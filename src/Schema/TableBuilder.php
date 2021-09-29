<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Column\DateColumn;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;

/**
 * a collection of fluent convenience methods to set up a new Table and return it from the build() method.
 * This was split out from the Table class to keep the Table interface small and coherent. Once built, the Table
 * would never need any of the methods in the TableBuilder.
 */
class TableBuilder
{
    /** @var Column[] column name => Column class instance */
    private array $column = [];

    /** @var Index[] index name => Index class instance */
    private array $index = [];

    public function __construct(private string $name, private string $description)
    {
    }

    public function build(): Table
    {
        return new Table($this->name, $this->description, $this->column, $this->index);
    }

    public function addColumn(Column $column): self
    {
        $this->column[$column->getName()] = $column;
        return $this;
    }

    public function addIndex(Index $index): self
    {
        $this->index[$index->getName()] = $index;
        return $this;
    }

    /**
     * a primary key is usually 'id'
     * @param string $name        of field
     * @param string $description field comments
     * @return $this
     */
    public function addPrimaryKeyColumn(string $name = '', string $description = ''): self
    {
        if (empty($name)) {
            $name = 'id';
        }
        if (empty($description)) {
            $description = 'Unique ' . $this->name . ' identifier';
        }
        $this->addColumn(new Column\PrimaryKeyColumn($name, $description));
        $this->addIndex(new Index\PrimaryIndex($name));
        return $this;
    }

    /**
     * @param string $foreignTable string table name of other table to link to (assumes there is an 'id' field which is
     *                             the primary key of that table)
     * @param string $description  field comments
     * @param string $name         name of column: defaults to 'id'
     * @return $this
     */
    public function addOneToOneKeyColumn(string $foreignTable, string $description = '', string $name = ''): self
    {
        if (empty($name)) {
            $name = 'id';
        }
        if (empty($description)) {
            $description = trim(Column\ForeignKeyColumn::RELATED_TEXT . ' ' . $foreignTable . '. ' . $description);
        }
        $this->addColumn(new Column\ForeignKeyColumn($name, $description, $foreignTable));
        $this->addUniqueIndex($name);
        return $this;
    }

    /**
     * adds an indexed integer field linking this table with another.
     * The field name defaults to the foreign_table_name with suffix _id and assumes
     * the foreign key field is an integer. To add a string foreign key, call addStringField()
     * and addIndex().
     * @param string $foreignTable string table name of other table to link to (assumes there is an 'id' field which is
     *                             the primary key of that table)
     * @param string $description  string defaults to '' unless id is also blank
     * @param string $thisField    string name of field in this table
     * @param string $onDelete
     * @param string $onUpdate
     * @return $this
     * @throws SchemaException
     */
    public function addForeignKeyColumn(
        string $foreignTable,
        string $description = '',
        string $thisField = '',
        string $onDelete = ForeignKeyColumn::ACTION_NO_ACTION,
        string $onUpdate = ForeignKeyColumn::ACTION_NO_ACTION
    ): self {
        if ($thisField === '') {
            $thisField = $foreignTable . '_id';
        }
        $description = trim(Column\ForeignKeyColumn::RELATED_TEXT . ' ' . $foreignTable . ' ' . $description);
        $this->addColumn(new Column\ForeignKeyColumn($thisField, $description, $foreignTable, $onDelete, $onUpdate));
        $this->addIndex(new Index\SecondaryIndex($thisField));
        return $this;
    }

    public function addForeignKeyColumnNullable(
        string $foreignTable,
        string $description = '',
        string $thisField = ''
    ): self {
        if ($thisField === '') {
            $thisField = $foreignTable . '_id';
        }
        $description = trim(Column\ForeignKeyColumn::RELATED_TEXT . ' ' . $foreignTable . ' ' . $description);
        $column      = new ForeignKeyColumn($thisField, $description, $foreignTable, ForeignKeyColumn::ACTION_SET_NULL);
        $column->setAllowNull()->setDefault(null);
        $this->addColumn($column);
        $this->addIndex(new Index\SecondaryIndex($thisField));
        return $this;
    }

    public function addIntegerColumn(string $name, string $description = ''): self
    {
        $this->addColumn(new Column\IntegerColumn($name, $description));
        return $this;
    }

    /**
     * add a column called display_order and create an index for it
     * @param string $description field comments
     * @return $this
     */
    public function addDisplayOrderColumn(string $description = 'Lower values go to the top, 0 = sort alphabetically'
    ): self {
        $this->addIntegerColumn('display_order', $description);
        $this->addIndex(new Index\SecondaryIndex('display_order'));
        return $this;
    }

    public function addCurrencyColumn(string $name, string $description = ''): self
    {
        return $this->addFloatColumn($name, 10, 2, $description);
    }

    public function addFloatColumn(string $name, int $width, int $decimalPlaces, string $description = ''): self
    {
        $this->addColumn(new Column\FloatColumn($name, $description, $width, $decimalPlaces));
        return $this;
    }

    public function addEnumerationColumn(string $name, array $values, string $description = ''): self
    {
        $this->addColumn(new Column\EnumerationColumn($name, $description, $values));
        return $this;
    }

    public function addSetColumn(string $name, array $values, string $description = ''): self
    {
        $this->addColumn(new Column\SetColumn($name, $description, $values));
        return $this;
    }

    public function addDateTimeColumn(string $name, string $description = '', string $default = null): self
    {
        $column = new Column\DateTimeColumn($name, $description, $default);
        if (empty($default)) {
            $column->setAllowNull();
        }
        $this->addColumn($column);
        return $this;
    }

    public function addDateColumn(string $name, string $description = '', string $default = null): self
    {
        $column = new Column\DateColumn($name, $description, $default);
        if (empty($default)) {
            $column->setAllowNull();
        }
        $this->addColumn($column);
        return $this;
    }

    public function addDateUpdatedColumn(string $description = 'Date and time this record was last changed'): self
    {
        $this->addColumn(new Column\DateTimeColumn('date_updated', $description, DateColumn::CURRENT_TIMESTAMP));
        return $this;
    }

    public function addDateCreatedColumn(string $description = 'Date and time this record was first created'): self
    {
        $this->addColumn(new Column\DateTimeColumn('date_created', $description, DateColumn::CURRENT_TIMESTAMP));
        return $this;
    }

    public function addBooleanColumn(string $name, string $description = ''): self
    {
        $this->addColumn(new Column\BooleanColumn($name, $description));
        return $this;
    }

    public function addStringColumn(string $name, int $length, string $description = ''): self
    {
        assert($length > 0);
        assert($length < 65535, 'max varchar length for mysql');
        $this->addColumn(new Column\StringColumn($name, $description, '', $length));
        return $this;
    }

    public function addTextColumn(string $name, string $description = ''): self
    {
        $this->addColumn(new Column\TextColumn($name, $description));
        return $this;
    }

    public function addJsonColumn(string $name, string $description = ''): self
    {
        $this->addColumn(new Column\JsonColumn($name, $description));
        return $this;
    }

    public function addMediumTextColumn(string $name, string $description = ''): self
    {
        $this->addColumn(new Column\TextColumn($name, $description, 'mediumtext'));
        return $this;
    }

    public function addCalculatedColumn(Column $inner, string $sqlExpression): self
    {
        $this->addColumn(new Column\CalculatedColumn($inner, $sqlExpression));
        return $this;
    }

    public function addStandardIndex(string $name, array $columns = [], bool $isUnique = false): self
    {
        if (empty($columns)) {
            $columns[] = $name;
        }
        $firstColumn = array_shift($columns);
        $index       = new Index\SecondaryIndex($name, $firstColumn, $isUnique);
        foreach ($columns as $column) {
            $index->addColumn($column);
        }
        $this->addIndex($index);
        return $this;
    }

    public function addUniqueIndex(string $name, array $columns = []): self
    {
        return $this->addStandardIndex($name, $columns, true);
    }
}
