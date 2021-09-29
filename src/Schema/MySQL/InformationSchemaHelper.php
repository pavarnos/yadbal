<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\MySQL;

use LSS\YADbal\DatabaseConnectionInterface;

/**
 * an internal helper class that wraps database access.
 * It does only three queries to the database and breaks things down per table and index so we do not have to make two
 * queries per table: avoids the N+1 problem times two for a big schema with lots of tables.
 */
class InformationSchemaHelper
{
    /** @var string[] table name => description */
    private array $table = [];

    /** @var array table name => array of column info */
    private array $column = [];

    /** @var array table name => index name => array of column info */
    private array $index = [];

    /** @var array table name => constraint name => array of constraint info */
    private array $foreignKey = [];

    public function __construct(private DatabaseConnectionInterface $database)
    {
    }

    public function populate(): void
    {
        $databaseName = $this->database->fetchValue('select database()');
        $this->populateTableNames($databaseName);
        $this->populateColumnInfo($databaseName);
        $this->populateIndexInfo($databaseName);
        $this->populateForeignKeys($databaseName);
    }

    public function getTableNames(): array
    {
        return $this->table;
    }

    public function getColumnsFor(string $tableName): array
    {
        return $this->column[$tableName] ?? [];
    }

    public function getIndexesFor(string $tableName): array
    {
        return $this->index[$tableName] ?? [];
    }

    public function getForeignKeysFor(string $tableName): array
    {
        return $this->foreignKey[$tableName] ?? [];
    }

    private function populateTableNames(string $databaseName): void
    {
        $sql         = 'select table_name, table_comment from information_schema.tables ' .
            'where table_schema = :schema and lower(table_type) = "base table" order by table_name';
        $this->table = [];
        foreach ($this->database->fetchAll($sql, ['schema' => $databaseName]) as $row) {
            $row = array_change_key_case($row, CASE_LOWER);

            $this->table[$row['table_name']] = $row['table_comment'];
        }
    }

    private function populateColumnInfo(string $databaseName): void
    {
        $sql          = 'select table_name, column_name, column_default, column_key, is_nullable, data_type, character_maximum_length, ' .
            'numeric_precision, numeric_scale, column_type, extra, column_comment, generation_expression ' .
            'from information_schema.columns ' .
            'where table_schema = :schema order by table_name, ordinal_position';
        $this->column = [];
        foreach ($this->database->fetchAll($sql, ['schema' => $databaseName]) as $column) {
            $column = array_change_key_case($column, CASE_LOWER);

            $this->column[$column['table_name']][] = $column;
        }
    }

    private function populateIndexInfo(string $databaseName): void
    {
        $sql = 'select table_name, index_name, non_unique, column_name, index_type from information_schema.statistics ' .
            'where table_schema = :schema order by table_name, index_name, seq_in_index';

        $this->index = [];
        foreach ($this->database->fetchAll($sql, ['schema' => $databaseName]) as $column) {
            $column = array_change_key_case($column, CASE_LOWER);

            $this->index[$column['table_name']][$column['index_name']][] = $column;
        }
    }

    private function populateForeignKeys(string $databaseName): void
    {
        // deliberate limitation here: a foreign key constraint can only go from a single column to the id column of its parent table
        // this keeps things very simple and is all that is needed by this schema library

        // https://dev.mysql.com/doc/refman/8.0/en/information-schema-referential-constraints-table.html
        $sql = 'SELECT * FROM information_schema.referential_constraints WHERE constraint_schema = :schema';
        foreach ($this->database->fetchAll($sql, ['schema' => $databaseName]) as $constraint) {
            $constraint = array_change_key_case($constraint, CASE_LOWER);

            $column = $this->getReferencedColumn(
                $databaseName,
                $constraint['constraint_name'],
                $constraint['table_name']
            );
            assert($column['referenced_column_name'] === 'id');

            $this->foreignKey[$constraint['table_name']][$column['column_name']] = [
                'to_table'        => $column['referenced_table_name'],
                // 'to_column'       => 'id', // $column['referenced_column_name']
                'on_delete'       => $constraint['delete_rule'],
                'on_update'       => $constraint['update_rule'],
                'constraint_name' => $constraint['constraint_name'],
            ];
        }
    }

    private function getReferencedColumn(string $databaseName, string $constraintName, string $tableName): array
    {
        // https://dev.mysql.com/doc/refman/8.0/en/information-schema-key-column-usage-table.html
        $sql        = 'SELECT * FROM information_schema.key_column_usage WHERE table_schema = :schema and constraint_name = :constraint and table_name = :tableName';
        $parameters = ['schema' => $databaseName, 'constraint' => $constraintName, 'tableName' => $tableName];
        $columnInfo = $this->database->fetchRow($sql, $parameters);
        $columnInfo = array_change_key_case($columnInfo, CASE_LOWER);
        return $columnInfo;
    }
}
