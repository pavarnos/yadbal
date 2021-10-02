<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\DatabaseConnectionInterface;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use LSS\YADbal\Schema\MySQL\InformationSchemaHelper;

/**
 * uses information schema queries to build and return a Database
 */
class SchemaFromMySQL
{
    private DatabaseConnectionInterface $database;

    public function __construct(DatabaseConnectionInterface $database)
    {
        $this->database = $database;
    }

    public function build(): Schema
    {
        $helper = new InformationSchemaHelper($this->database);
        $helper->populate();
        $schema = new Schema();
        foreach ($helper->getTableNames() as $tableName => $tableDescription) {
            $columns = $this->buildColumns($helper->getColumnsFor($tableName), $helper->getForeignKeysFor($tableName));
            $indexes = $this->buildIndexes($helper->getIndexesFor($tableName));
            $schema->addTable(new Table($tableName, $tableDescription, $columns, $indexes));
        }
        return $schema;
    }

    /**
     * @param array $columnsInfo    array of raw column info from the database
     * @param array $foreignKeyInfo array of foreign key constraint info from the database
     * @return Column[]
     * @throws SchemaException
     */
    protected function buildColumns(array $columnsInfo, array $foreignKeyInfo): array
    {
        $columns = [];
        foreach ($columnsInfo as $columnInfo) {
            $column                      = $this->columnFromInfo($columnInfo, $foreignKeyInfo);
            $columns[$column->getName()] = $column;
        }
        return $columns;
    }

    /**
     * @param array $indexesInfo array of raw index info from the database, grouped by index with columns inside
     * @return Index[]
     */
    protected function buildIndexes(array $indexesInfo): array
    {
        $indexes = [];
        foreach ($indexesInfo as $indexInfo) {
            $index                      = $this->indexFromInfo($indexInfo);
            $indexes[$index->getName()] = $index;
        }
        return $indexes;
    }

    protected function columnFromInfo(array $info, array $foreignKey): Column
    {
        if ($info['column_key'] == 'PRI') {
            return new Column\PrimaryKeyColumn($info['column_name'], $info['column_comment']);
        }
        if (!empty($foreignKey[$info['column_name']])) {
            $key = $foreignKey[$info['column_name']];
            return new ForeignKeyColumn(
                $info['column_name'],
                $info['column_comment'],
                $key['to_table'],
                $key['on_delete'],
                $key['on_update'],
                $key['constraint_name']
            );
        }
        $column = match ($info['data_type']) {
            'mediumtext', 'text' => new Column\TextColumn(
                $info['column_name'],
                $info['column_comment'],
                $info['data_type']
            ),
            'varchar' => new Column\StringColumn(
                $info['column_name'], $info['column_comment'],
                $info['column_default'], intval($info['character_maximum_length'])
            ),
            'int', 'mediumint' => new Column\IntegerColumn(
                $info['column_name'], $info['column_comment'],
                $info['column_default'] ?? '0', $info['column_type']
            ),
            'tinyint' => new Column\BooleanColumn($info['column_name'], $info['column_comment']),
            'decimal' => new Column\FloatColumn(
                $info['column_name'], $info['column_comment'],
                intval($info['numeric_precision']), intval($info['numeric_scale'])
            ),
            'date' => new Column\DateColumn($info['column_name'], $info['column_comment'], $info['column_default']),
            'datetime' => new Column\DateTimeColumn(
                $info['column_name'], $info['column_comment'],
                $info['column_default']
            ),
            'enum' => new Column\EnumerationColumn(
                $info['column_name'], $info['column_comment'],
                $this->getValues($info['column_type']), $info['column_default']
            ),
            'set' => new Column\SetColumn(
                $info['column_name'], $info['column_comment'],
                $this->getValues($info['column_type'])
            ),
            'json' => new Column\JsonColumn($info['column_name'], $info['column_comment']),
            // @codeCoverageIgnoreStart
            default => throw new SchemaException(
                'Unknown column type ' .
                join(' ', [$info['table_name'], $info['column_name'], $info['data_type']])
            ),
            // @codeCoverageIgnoreEnd
        };
        if (!empty($info['generation_expression'])) {
            $column = new Column\CalculatedColumn($column, $this->stripIntroducers($info['generation_expression']));
        }
        if ($info['is_nullable']) {
            $column->setAllowNull();
        }
        return $column;
    }

    protected function getValues(string $text): array
    {
        if (\Safe\preg_match('/^(set|enum)\((.*)\)$/', $text, $matches) == 0) {
            // @codeCoverageIgnoreStart
            throw new SchemaException('Could not match set or enum ' . $text);
            // @codeCoverageIgnoreEnd
        }
        return (array)\Safe\preg_replace('/^([\'"])(.*)([\'"])$/', '\2', explode(',', $matches[2]));
    }

    protected function indexFromInfo(array $columns): Index
    {
        /** @var Index|null $index */
        $index = null;
        foreach ($columns as $info) {
            if (!is_null($index)) {
                $index->addColumn($info['column_name']);
                continue;
            }
            if ($info['index_name'] == 'PRIMARY') {
                $index = new Index\PrimaryIndex($info['column_name']);
            } else {
                $index = new Index\SecondaryIndex($info['index_name'], $info['column_name'], $info['non_unique'] == 0);
            }
        }
        assert(!is_null($index));
        return $index;
    }

    private function stripIntroducers(string $expression): string
    {
        // generated columns have character set prefixes when we read them from the information schema
        // see https://dev.mysql.com/doc/refman/8.0/en/charset-introducer.html
        // they also have backslashed quote characters around them, which we strip here but it assumes there is
        // no single quote character within the string literal
        $expression = \Safe\preg_replace('|_utf8[a-z0-9]*|', '', $expression);
        assert(!is_array($expression)); // for phpstan
        return str_replace("\\'", "'", $expression);
    }
}
