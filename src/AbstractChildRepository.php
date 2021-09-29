<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   15 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal;

use function Latitude\QueryBuilder\field;

/**
 * Use this for a child table eg you have a 'person' parent table and child tables 'person_address', 'person_message' etc
 * each with many records per person.id.
 * This class has utilities to help prevent insecure direct object references: so you can only read or write the
 * child record if you also supply the correct parent record id. If the parent id is wrong, the read will return null
 * or throw, and the write will not change anything (zero rows affected)
 */
abstract class AbstractChildRepository extends AbstractRepository
{
    private string $parentTableName;

    private string $parentIdField;

    public function __construct(DatabaseConnectionInterface $database)
    {
        parent::__construct($database);
        $parts = explode('_', $this->getTableName());
        array_pop($parts);
        $this->parentTableName = join('_', $parts);
        $this->parentIdField   = $this->parentTableName . '_id';
    }

    public function getParentIdField(): string
    {
        return $this->parentIdField;
    }

    /**
     * @param int   $recordId primary key
     * @param int   $parentId parent key interlock
     * @param array $columns  to return or [] for all
     * @return ?array
     */
    public function findChildOrNull(int $recordId, int $parentId, array $columns = []): ?array
    {
        if (empty($recordId)) {
            // no id means no record. Records should not have an id of 0
            return null;
        }
        $query = $this->select()
                      ->from(static::TABLE_NAME)
                      ->andWhere(field('id')->eq($recordId))
                      ->andWhere(field($this->parentIdField)->eq($parentId));
        if (!empty($columns)) {
            $query->columns(...$columns);
        }
        return $this->fetchRow($query) ?: null;
    }

    /**
     * @param int   $recordId primary key
     * @param int   $parentId parent key interlock
     * @param array $columns  to return or [] for all
     * @return array rejects on no such record
     * @throws DatabaseException
     */
    public function findChildOrException(int $recordId, int $parentId, array $columns = []): array
    {
        $result = $this->findChildOrNull($recordId, $parentId, $columns);
        if (is_null($result)) {
            throw new DatabaseException('No such ' . static::TABLE_NAME . ' ' . $recordId);
        }
        return $result;
    }

    public function deleteChildRow(int $recordId, int $parentId): int
    {
        if (empty($recordId)) {
            throw new DatabaseException(static::TABLE_NAME . ' record not specified', $recordId);
        }
        $query    = $this->delete(static::TABLE_NAME)
                         ->andWhere(field('id')->eq($recordId))
                         ->andWhere(field($this->parentIdField)->eq($parentId));
        $compiled = $query->compile();
        return $this->database->write($compiled->sql(), $compiled->params());
    }

    protected function updateRow(int $id, array $data): int
    {
        // interlock so we can only update a row where the child id matches the parent id
        $data = $this->beforeSave($data);
        assert(isset($data[$this->parentIdField]), 'missing ' . $this->parentIdField);
        $parentId = $data[$this->parentIdField];
        unset($data['id'], $data[$this->parentIdField]);
        $update   = $this->update(static::TABLE_NAME, $data)
                         ->andWhere(field('id')->eq($id))
                         ->andWhere(field($this->parentIdField)->eq($parentId));
        $compiled = $update->compile();
        $this->database->write($compiled->sql(), $compiled->params());
        return $id;
    }

    protected function insertRow(array $data): int
    {
        assert(isset($data[$this->parentIdField]), 'missing ' . $this->parentIdField);
        return parent::insertRow($data);
    }
}
