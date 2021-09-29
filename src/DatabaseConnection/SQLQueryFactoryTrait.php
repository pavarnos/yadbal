<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   09 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\DatabaseConnection;

use Latitude\QueryBuilder\Engine\MySqlEngine;
use Latitude\QueryBuilder\ExpressionInterface;
use Latitude\QueryBuilder\Query\DeleteQuery;
use Latitude\QueryBuilder\Query\InsertQuery;
use Latitude\QueryBuilder\Query\SelectQuery;
use Latitude\QueryBuilder\Query\UpdateQuery;
use Latitude\QueryBuilder\QueryFactory;

/**
 * supports DatabaseConnectionInterface
 */
trait SQLQueryFactoryTrait
{
    private QueryFactory $queryFactory;

    /**
     * @param string|ExpressionInterface ...$columns
     * @return SelectQuery
     */
    public function select(...$columns): SelectQuery
    {
        return $this->queryFactory->select(...$columns);
    }

    public function insert(string $tableName, array $map = []): InsertQuery
    {
        return $this->queryFactory->insert($tableName, $map);
    }

    public function insertBulk(string $tableName, array $rows): InsertQuery
    {
        $insert = $this->insert($tableName)->columns(...array_keys($rows[0]));
        foreach ($rows as $row) {
            $insert->values(...array_values($row));
        }
        return $insert;
    }

    public function update(string $tableName, array $map = []): UpdateQuery
    {
        return $this->queryFactory->update($tableName, $map);
    }

    public function delete(string $tableName): DeleteQuery
    {
        return $this->queryFactory->delete($tableName);
    }

    private function setupQueryFactory(): void
    {
        $this->queryFactory = new QueryFactory(new MySqlEngine());
    }
}
