<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   09 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\DatabaseConnection;

use Latitude\QueryBuilder\ExpressionInterface;
use Latitude\QueryBuilder\Query\DeleteQuery;
use Latitude\QueryBuilder\Query\InsertQuery;
use Latitude\QueryBuilder\Query\SelectQuery;
use Latitude\QueryBuilder\Query\UpdateQuery;

/**
 * for SQLQueryFactoryTrait
 */
interface SQLQueryFactoryInterface
{
    /**
     * @param string|ExpressionInterface ...$columns
     * @return SelectQuery
     */
    public function select(...$columns): SelectQuery;

    public function insert(string $tableName, array $map = []): InsertQuery;

    public function insertBulk(string $tableName, array $rows): InsertQuery;

    public function update(string $tableName, array $map = []): UpdateQuery;

    public function delete(string $tableName): DeleteQuery;
}
