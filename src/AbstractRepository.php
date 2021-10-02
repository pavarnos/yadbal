<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   15 Jun 2020
 */

declare(strict_types=1);

namespace LSS\YADbal;

use Latitude\QueryBuilder\ExpressionInterface;
use Latitude\QueryBuilder\Query\AbstractQuery;
use Latitude\QueryBuilder\Query\DeleteQuery;
use Latitude\QueryBuilder\Query\InsertQuery;
use Latitude\QueryBuilder\Query\SelectQuery;
use Latitude\QueryBuilder\Query\UpdateQuery;
use LSS\YADbal\Paginator\AbstractPaginator;
use LSS\YADbal\Paginator\LatitudePaginator;
use LSS\YADbal\Schema\GetSchemaInterface;

use function Latitude\QueryBuilder\field;

/**
 * Utility functions to ease database access
 *
 * Public ones are higher level
 * Protected are low level
 */
abstract class AbstractRepository implements GetSchemaInterface
{
    public const TABLE_NAME = '';

    public function __construct(protected DatabaseConnectionInterface $database)
    {
        assert(static::TABLE_NAME !== '', 'set TABLE_NAME in your derived class');
    }

    public function getTableName(): string
    {
        return static::TABLE_NAME;
    }

    public function getIDFieldName(): string
    {
        return 'id';
    }

    public function now(): ExpressionInterface
    {
        return $this->database->now();
    }

    /**
     * @param int   $recordId primary key
     * @param array $columns  to return or [] for all
     * @return ?array
     */
    public function findOrNull(int $recordId, array $columns = []): ?array
    {
        if (empty($recordId)) {
            // no id means no record. Records should not have an id of 0
            return null;
        }
        $query = $this->select()
                      ->from(static::TABLE_NAME)
                      ->andWhere(field('id')->eq($recordId));
        if (!empty($columns)) {
            $query->columns(...$columns);
        }
        return $this->afterFind($this->fetchRow($query) ?: null);
    }

    /**
     * @param int   $recordId primary key
     * @param array $columns  to return or [] for all
     * @return array rejects on no such record
     * @throws DatabaseException
     */
    public function findOrException(int $recordId, array $columns = []): array
    {
        $result = $this->findOrNull($recordId, $columns);
        if (is_null($result)) {
            throw new DatabaseException('No such ' . static::TABLE_NAME . ' ' . $recordId);
        }
        return $result;
    }

    /**
     * save the $data back in to the repository.
     * A new record has $data[self::Id_FIELD] empty or not set
     * An existing record has this field populated, so the existing database row will be updated.
     * @param array $data
     * @return int $recordId created or updated
     */
    public function save(array $data): int
    {
        if (empty($data['id'])) {
            return $this->insertRow($data);
        }
        return $this->updateRow(intval($data['id']), $data);
    }

    public function deleteRow(int $recordId): int
    {
        if (empty($recordId)) {
            throw new DatabaseException(static::TABLE_NAME . ' Record not specified');
        }
        $query = $this->delete(static::TABLE_NAME)->andWhere(field('id')->eq($recordId));
        return $this->writeAndCount($query);
    }

    /**
     * convenience / shortcut for when you need to build queries
     * @param string|ExpressionInterface ...$columns
     * @return SelectQuery
     */
    public function selectAll(...$columns): SelectQuery
    {
        return $this->select(...$columns)->from(static::TABLE_NAME);
    }

    public function getShowAllPaginator(SelectQuery $select): AbstractPaginator
    {
        return LatitudePaginator::forShowAll($this->fetchAll($select));
    }

    public function getFastPaginator(SelectQuery $select, int $pageNumber, int $pageSize): AbstractPaginator
    {
        $rows = $this->fetchAll(LatitudePaginator::getPageSelect($select, $pageNumber, $pageSize));
        return LatitudePaginator::forFastMode($rows, $pageNumber, $pageSize);
    }

    public function getPaginator(SelectQuery $select, int $pageNumber, int $pageSize): AbstractPaginator
    {
        $itemCount = $this->fetchInt(LatitudePaginator::getCountSelect($select, $this));
        $rows      = $this->fetchAll(LatitudePaginator::getPageSelect($select, $pageNumber, $pageSize));
        return LatitudePaginator::forPage($rows, $pageNumber, $pageSize, $itemCount);
    }


    // it would be nice to make all fetch*() methods protected, but they are needed for DisplayOrder and pagination.
    // the gymnastics involved in hiding them was not worth it
    public function fetchPairs(SelectQuery $select): array
    {
        $compiled = $select->compile();
        return $this->database->fetchPairs($compiled->sql(), $compiled->params());
    }

    public function fetchInt(SelectQuery $select): int
    {
        $compiled = $select->compile();
        return $this->database->fetchInt($compiled->sql(), $compiled->params());
    }

    public function fetchAll(SelectQuery $select): array
    {
        $compiled = $select->compile();
        return $this->database->fetchAll($compiled->sql(), $compiled->params());
    }

    protected function fetchRow(SelectQuery $select): array
    {
        $compiled = $select->compile();
        return $this->database->fetchRow($compiled->sql(), $compiled->params());
    }

    protected function fetchString(SelectQuery $select): string
    {
        $compiled = $select->compile();
        return $this->database->fetchString($compiled->sql(), $compiled->params());
    }

    protected function fetchColumn(SelectQuery $select, string $columnName = 'title'): array
    {
        $output = [];
        foreach ($this->fetchAll($select) as $row) {
            $output[] = $row[$columnName];
        }
        return $output;
    }

    // low level utility methods

    protected function updateRow(int $id, array $data): int
    {
        $data = $this->beforeSave($data);
        unset($data['id']);
        $update = $this->update(static::TABLE_NAME, $data)
                       ->andWhere(field('id')->eq($id));
        $this->write($update);
        return $id;
    }

    protected function insertRow(array $data): int
    {
        $data = $this->beforeSave($data);
        unset($data['id']);
        $insert = $this->insert(static::TABLE_NAME, $data);
        $this->write($insert);
        return $this->database->lastInsertId();
    }

    protected function write(AbstractQuery $query): void
    {
        $compiled = $query->compile();
        $this->database->write($compiled->sql(), $compiled->params());
    }

    protected function writeAndCount(AbstractQuery $query): int
    {
        $compiled = $query->compile();
        return $this->database->write($compiled->sql(), $compiled->params());
    }

    // row transformations

    protected function beforeSave(array $data): array
    {
        // magically call all beforeSave*() methods on this class. call order is not guaranteed
        foreach ((new \ReflectionClass($this))->getMethods() as $method) {
            if (!str_starts_with($method->getName(), 'beforeSave') || $method->getName() === 'beforeSave') {
                // do not recursively call this method! infinite doom!
                continue;
            }
            $method->setAccessible(true);
            $data = (array)$method->invoke($this, $data);
        }
        return $data;
    }

    protected function afterFind(?array $data): ?array
    {
        if (is_null($data)) {
            return null;
        }
        // magically call all afterFind*() methods on this class. call order is not guaranteed
        foreach ((new \ReflectionClass($this))->getMethods() as $method) {
            if (!str_starts_with($method->getName(), 'afterFind') || $method->getName() === 'afterFind') {
                // do not recursively call this method! infinite doom!
                continue;
            }
            $method->setAccessible(true);
            $data = (array)$method->invoke($this, $data);
        }
        return $data;
    }

    // query building tools

    /**
     * @param string|ExpressionInterface ...$columns
     * @return SelectQuery
     */
    protected function select(...$columns): SelectQuery
    {
        return $this->database->select(...$columns);
    }

    protected function insert(string $tableName, array $map = []): InsertQuery
    {
        return $this->database->insert($tableName, $map);
    }

    protected function update(string $tableName, array $map = []): UpdateQuery
    {
        return $this->database->update($tableName, $map);
    }

    protected function delete(string $tableName): DeleteQuery
    {
        return $this->database->delete($tableName);
    }
}
