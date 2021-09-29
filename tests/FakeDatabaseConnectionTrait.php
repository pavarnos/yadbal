<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   02 Jun 2021
 */

declare(strict_types=1);

namespace LSS\YADbal;

use LSS\YADbal\ExpectQuery\ExpectDeleteQuery;
use LSS\YADbal\ExpectQuery\ExpectInsertQuery;
use LSS\YADbal\ExpectQuery\ExpectPaginatorQuery;
use LSS\YADbal\ExpectQuery\ExpectQuery;
use LSS\YADbal\ExpectQuery\ExpectSelectQuery;
use LSS\YADbal\ExpectQuery\ExpectUpdateQuery;

/**
 * Use this on your TestCase s to save a bit of typing and create a nice compact syntax for expecting queries.
 */
trait FakeDatabaseConnectionTrait
{
    protected FakeDatabaseConnection $database;

    protected function expectQuery(ExpectQuery $query): ExpectQuery
    {
        $this->database->expectQuery($query);
        return $query;
    }

    protected function expectSelect(
        string $sql,
        array $params = [],
        array $result = [],
        string $name = ''
    ): ExpectSelectQuery {
        return $this->database->expectSelect($sql, $params, $result, $name);
    }

    protected function expectPaginatedSelect(
        string $sql,
        array $params = [],
        array $result = [],
        string $name = ''
    ): ExpectSelectQuery {
        $this->expectQuery(new ExpectPaginatorQuery($sql, $params, count($result)));
        $this->expectQuery($select = new ExpectSelectQuery($sql, $params, $result, $name));
        return $select;
    }

    protected function expectUpdate(string $sql, array $params, int $affectedRows = 0): ExpectUpdateQuery
    {
        return $this->database->expectUpdate($sql, $params, $affectedRows);
    }

    protected function expectInsert(string $sql, array $params, int $affectedRows = 0): ExpectInsertQuery
    {
        return $this->database->expectInsert($sql, $params, $affectedRows);
    }

    protected function expectDelete(string $sql, array $params, int $affectedRows = 0): ExpectDeleteQuery
    {
        return $this->database->expectDelete($sql, $params, $affectedRows);
    }
}
