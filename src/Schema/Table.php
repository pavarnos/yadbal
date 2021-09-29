<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Column\ForeignKeyColumn;

class Table
{
    /** @var string */
    private string $name = '';

    /** @var string */
    private string $description = '';

    /** @var Column[] column name => Column class instance */
    private array $column = [];

    /** @var Index[] index name => Index class instance */
    private array $index = [];

    /**
     * @param string   $name
     * @param string   $description
     * @param Column[] $columns indexed by name
     * @param Index[]  $indexes indexed by name
     */
    public function __construct(string $name, string $description, array $columns = [], array $indexes = [])
    {
        $this->name        = $name;
        $this->description = $description;
        $this->column      = $columns;
        $this->index       = $indexes;
    }

    public static function quoteDescription(string $text): string
    {
        $quote = "'";
        return $quote . str_replace($quote, $quote . $quote, $text) . $quote;
    }

    public static function quoteValueArray(array $values): string
    {
        return join(',', array_map(fn($value) => self::quoteDescription($value), $values));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->column;
    }

    public function getColumn(string $name): Column
    {
        assert(isset($this->column[$name]));
        return $this->column[$name];
    }

    public function hasColumn(string $name): bool
    {
        return isset($this->column[$name]);
    }

    /**
     * @return ForeignKeyColumn[] name => Column
     */
    public function getForeignKeys(): array
    {
        return array_filter(
            $this->column,
            function (Column $column): bool {
                return $column instanceof ForeignKeyColumn;
            }
        );
    }

    /**
     * return values suitable for data entry form
     * @param string $fieldName
     * @return string[]
     */
    public function getEnumFieldValues(string $fieldName): array
    {
        $field = $this->getColumn($fieldName);
        if ((!$field instanceof Column\EnumerationColumn) && (!$field instanceof Column\SetColumn)) {
            throw new SchemaException($this->getName() . '.' . $fieldName . ' must be an enumeration or set column');
        }
        $values = $field->getValues();
        return empty($values) ? [] : \Safe\array_combine($values, $values);
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->index;
    }

    public function toSQLite(): string
    {
        $items = [];
        foreach ($this->column as $column) {
            $items[] = $column->toSQLite();
        }
//        foreach ($this->index as $index) {
//            $items[] = $index->toSQLite();
//        }
        $space = '    ';
        return 'create table ' . $this->name . ' (' . PHP_EOL .
            $space . join(',' . PHP_EOL . $space, array_filter($items)) . PHP_EOL . ')';
    }

    /**
     * generate column spec for CREATE TABLE statement
     */
    public function toMySQL(): string
    {
        $items = [];
        foreach ($this->column as $column) {
            $items[] = $column->toMySQL();
        }
        foreach ($this->index as $index) {
            $items[] = $index->toMySQL();
        }
        foreach ($this->getForeignKeys() as $fk) {
            $key = $fk->toMySQLForeignKey();
            if (!empty($key)) {
                $items[] = 'FOREIGN KEY ' . $key;
            }
        }
        $space  = '    ';
        $output = 'create table ' . $this->name . ' (' . PHP_EOL;
        $output .= $space . join(',' . PHP_EOL . $space, array_filter($items)) . PHP_EOL . ')';
        if (strlen($this->description) > 0) {
            $output .= ' comment=' . self::quoteDescription($this->description);
        }
        return $output;
    }
}
