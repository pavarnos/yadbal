<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Column\ForeignKeyColumn;

/**
 * Take the Schema and return a string containing the ALTER TABLE statements necessary to update a database
 */
class SchemaUpgradeCalculator
{
    /**
     * return a set of sql statements that turn $actual into $wanted
     * @param SchemaInterface $wanted
     * @param SchemaInterface $actual
     * @return string[] sql statements to execute
     */
    public function getUpgradeSQL(SchemaInterface $wanted, SchemaInterface $actual): array
    {
        $sql = [];

        $wantedTables = $wanted->getTableNames();
        $actualTables = $actual->getTableNames();

        // all tables not in $actual will be created
        foreach (array_diff($wantedTables, $actualTables) as $tableName) {
            $sql[] = $wanted->getTable($tableName)->toMySQL();
        }

        // all tables not in $wanted will be dropped
        foreach (array_diff($actualTables, $wantedTables) as $tableName) {
            $sql[] = 'drop table ' . $tableName;
        }

        // tables in both will be compared column by column, index by index
        foreach (array_intersect($wantedTables, $actualTables) as $tableName) {
            $sql = array_merge(
                $sql,
                $this->upgradeColumns(
                    $tableName,
                    array_values($wanted->getTable($tableName)->getColumns()),
                    array_values($actual->getTable($tableName)->getColumns())
                )
            );
            $sql = array_merge(
                $sql,
                $this->upgradeIndexes(
                    $tableName,
                    $wanted->getTable($tableName)->getIndexes(),
                    $actual->getTable($tableName)->getIndexes()
                )
            );
            $sql = array_merge(
                $sql,
                $this->upgradeForeignKeys(
                    $tableName,
                    $wanted->getTable($tableName)->getForeignKeys(),
                    $actual->getTable($tableName)->getForeignKeys()
                )
            );
            $sql = array_merge(
                $sql,
                $this->upgradeDescription(
                    $tableName,
                    $wanted->getTable($tableName)->getDescription(),
                    $actual->getTable($tableName)->getDescription()
                )
            );
        }

        return $sql;
    }

    /**
     * Compare the two sets of columns in $wanted and $actual
     * @param string   $tableName
     * @param Column[] $wantedColumns
     * @param Column[] $actualColumns
     * @return string[] of sql alter table statements
     */
    private function upgradeColumns(string $tableName, array $wantedColumns, array $actualColumns): array
    {
        $wantedColumnCount = count($wantedColumns);
        $actualColumnCount = count($actualColumns);

        $sql                = $drop = [];
        $actualColumnNumber = 0;
        for ($wantedColumnNumber = 0; $wantedColumnNumber < $wantedColumnCount; $wantedColumnNumber++) {
            // search for the wanted column in the actual
            // Only need to search forward as all fields match up to the current position in each table.
            $found = false;
            for ($c = $actualColumnNumber; $c < $actualColumnCount && !$found; $c++) {
                $comparison = $wantedColumns[$wantedColumnNumber]->compareTo($actualColumns[$c]);
                if ($comparison == Column::NOT_EQUAL) {
                    continue; // keep searching for a column that matches (fully or partially)
                }
                if ($comparison == Column::PARTIAL_MATCH) {
                    $sql[] = $this->modifyColumn($tableName, $wantedColumns[$wantedColumnNumber], $actualColumns[$c]);
                }

                if ($actualColumnNumber < $c) {
                    $drop = array_merge(
                        $drop,
                        $this->deleteColumnsBetween($tableName, $actualColumnNumber, $c, $actualColumns)
                    );
                }
                $actualColumnNumber = $c + 1;
                $found              = true;
            }
            if (!$found) {
                $sql[] = $this->addColumn($tableName, $wantedColumns, $wantedColumnNumber);
            }
        }
        // delete all the extra columns to the end
        $drop = array_merge(
            $drop,
            $this->deleteColumnsBetween($tableName, $actualColumnNumber, $actualColumnCount, $actualColumns)
        );

        // drop columns first
        return array_merge($drop, $sql);
    }

    /**
     * add a new column to the table
     * @param string   $tableName
     * @param Column[] $allColumns
     * @param int      $wantedColumnNumber
     * @return string sql ddl
     */
    private function addColumn(string $tableName, array $allColumns, int $wantedColumnNumber): string
    {
        $location = $wantedColumnNumber <= 0 ? 'first' : ('after ' . $allColumns[$wantedColumnNumber - 1]->getName());
        return 'alter table ' . $tableName . ' add column ' . $allColumns[$wantedColumnNumber]->toMySQL(
            ) . ' ' . $location;
    }

    /**
     * return SQL to change $actual into $wanted
     * @param string $tableName
     * @param Column $wanted
     * @param Column $actual
     * @return string sql ddl
     */
    private function modifyColumn(string $tableName, Column $wanted, Column $actual): string
    {
        return 'alter table ' . $tableName . ' change ' . $actual->getName() . ' ' . $wanted->toMySQL();
    }

    /**
     * return alter table sql to delete the selected columns
     * @param string   $tableName
     * @param int      $start  starting column number to delete
     * @param int      $finish delete columns up to (but not including) this column number
     * @param Column[] $actualColumns
     * @return string[] sql ddl
     */
    private function deleteColumnsBetween(string $tableName, int $start, int $finish, array $actualColumns): array
    {
        $sql = [];
        for ($i = $start; $i < $finish; $i++) {
            $sql[] = 'alter table ' . $tableName . ' drop column ' . $actualColumns[$i]->getName();
        }
        return $sql;
    }

    /**
     * see which indexes need to be added / deleted.
     * @param string $tableName
     * @param array  $wantedIndexes
     * @param array  $actualIndexes
     * @return string[] sql ddl
     */
    private function upgradeIndexes(string $tableName, array $wantedIndexes, array $actualIndexes): array
    {
        $sql = [];

        foreach ($actualIndexes as $name => $index) {
            if (!isset($wantedIndexes[$name])) {
                // all indexes not in $wanted will be dropped
                $sql[] = 'alter table ' . $tableName . ' drop index ' . $name;
            }
        }

        foreach ($wantedIndexes as $name => $index) {
            if (isset($actualIndexes[$name])) {
                // indexes in both will be compared: if any change, they will be dropped and re-created
                $wantedSQL = $index->toMySQL();
                $actualSQL = $actualIndexes[$name]->toMySQL();
                if ($wantedSQL != $actualSQL) {
                    $sql[] = 'alter table ' . $tableName . ' drop index ' . $name;
                    $sql[] = 'alter table ' . $tableName . ' add ' . $wantedSQL;
                }
            } else {
                // indexes not in $actual will be created
                $sql[] = 'alter table ' . $tableName . ' add ' . $index->toMySQL();
            }
        }

        return $sql;
    }

    /**
     * see which indexes need to be added / deleted.
     * @param string             $tableName
     * @param ForeignKeyColumn[] $wantedKeys
     * @param ForeignKeyColumn[] $actualKeys
     * @return string[] sql ddl
     */
    private function upgradeForeignKeys(string $tableName, array $wantedKeys, array $actualKeys): array
    {
        $sql = [];

        foreach ($actualKeys as $name => $key) {
            if (!isset($wantedKeys[$name])) {
                // all indexes not in $wanted will be dropped
                $sql[] = 'alter table ' . $tableName . ' drop foreign key ' . $key->getConstraintName();
            }
        }

        foreach ($wantedKeys as $name => $key) {
            if (isset($actualKeys[$name])) {
                // foreign keys in both will be compared: if any change, they will be dropped and re-created
                $wantedSQL = $key->toMySQLForeignKey();
                $actualSQL = $actualKeys[$name]->toMySQLForeignKey();
                if ($wantedSQL === $actualSQL) {
                    continue;
                }
                if (!empty($actualSQL)) {
                    $sql[] = 'alter table ' . $tableName . ' drop foreign key ' .
                        $actualKeys[$name]->getConstraintName();
                }
                if (!empty($wantedSQL)) {
                    $sql[] = 'alter table ' . $tableName . ' add foreign key ' . $wantedSQL;
                }
            } else {
                // indexes not in $actual will be created: if they are needed (not blank / disabled)
                $foreignKey = $key->toMySQLForeignKey();
                if (!empty($foreignKey)) {
                    $sql[] = 'alter table ' . $tableName . ' add foreign key ' . $foreignKey;
                }
            }
        }

        return $sql;
    }

    private function upgradeDescription(string $tableName, string $wantedDescription, string $actualDescription): array
    {
        if ($wantedDescription == $actualDescription) {
            return [];
        }
        return ['alter table ' . $tableName . ' comment = \'' . $wantedDescription . '\''];
    }
}
