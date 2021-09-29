<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   07 Jul 2020
 */

declare(strict_types=1);

namespace LSS\YADbal;

use LSS\YADbal\DatabaseConnection\SQLQueryFactoryTrait;
use LSS\YADbal\ExpectQuery\ExpectDeleteQuery;
use LSS\YADbal\ExpectQuery\ExpectInsertQuery;
use LSS\YADbal\ExpectQuery\ExpectPaginatorQuery;
use LSS\YADbal\ExpectQuery\ExpectQuery;
use LSS\YADbal\ExpectQuery\ExpectSelectQuery;
use LSS\YADbal\ExpectQuery\ExpectUpdateQuery;
use LSS\YADbal\Schema\Table;
use PHPUnit\Framework\TestCase;

/**
 * A test has Arrange, Act, Assert phases
 * Add expectations in the order you expect them in your Arrange phase
 * The destructor will check that all queries have been seen, and that there are no extra queries
 */
class FakeDatabaseConnection implements DatabaseConnectionInterface
{
    use SQLQueryFactoryTrait;

    /** @var ExpectQuery[] */
    private array $queries = [];

    private int $lastInsertId = 0;

    /** @var bool true if we want to allow more queries than we have expected */
    private bool $ignoreExtraQueries = false;

    public function __construct()
    {
        $this->setupQueryFactory();
    }

    public function __destruct()
    {
        TestCase::assertEmpty($this->queries, count($this->queries) . ' queries left');
    }

    public function setIgnoreExtraQueries(bool $ignoreExtraQueries = true): self
    {
        $this->ignoreExtraQueries = $ignoreExtraQueries;
        return $this;
    }

    public function expectQuery(ExpectQuery $query): self
    {
        $this->queries[] = $query;
        return $this;
    }

    public function expectSelect(
        string $sql,
        array $params = [],
        array $result = [],
        string $name = ''
    ): ExpectSelectQuery {
        $this->expectQuery($select = new ExpectSelectQuery($sql, $params, $result, $name));
        return $select;
    }

    public function expectPaginatedSelect(
        string $sql,
        array $params = [],
        array $result = [],
        string $name = ''
    ): ExpectSelectQuery {
        $this->expectQuery(new ExpectPaginatorQuery($sql, $params, count($result)));
        $this->expectQuery($select = new ExpectSelectQuery($sql, $params, $result, $name));
        return $select;
    }

    public function expectUpdate(string $sql, array $params, int $affectedRows = 0): ExpectUpdateQuery
    {
        $this->expectQuery($update = new ExpectUpdateQuery($sql, $params, $affectedRows));
        return $update;
    }

    public function expectInsert(string $sql, array $params, int $insertId): ExpectInsertQuery
    {
        $this->expectQuery($update = new ExpectInsertQuery($sql, $params, $insertId));
        return $update;
    }

    public function expectDelete(string $sql, array $params, int $affectedRows = 0): ExpectDeleteQuery
    {
        $this->expectQuery($update = new ExpectDeleteQuery($sql, $params, $affectedRows));
        return $update;
    }

    public function query(string $sql, array $params = []): ExpectQuery
    {
        if (empty($this->queries) && $this->ignoreExtraQueries) {
            return new ExpectQuery('', []); // null object
        }
        $result = array_shift($this->queries);
        TestCase::assertNotNull($result, 'no queries left. Wanted ' . $sql);
        $result->assertMatch($sql, $params);
        if ($result instanceof ExpectInsertQuery) {
            $this->lastInsertId = $result->getResultInt();
        }
        return $result;
    }

    public function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->query($sql, $parameters)->getResultArray();
    }

    public function fetchPairs(string $sql, array $parameters = []): array
    {
        return $this->query($sql, $parameters)->getResultArray();
    }

    public function fetchRow(string $sql, array $parameters = []): array
    {
        return $this->query($sql, $parameters)->getResultArray();
    }

    public function fetchCol(string $sql, array $parameters = []): array
    {
        return $this->query($sql, $parameters)->getResultArray();
    }

    public function fetchValue(string $sql, array $parameters = []): string
    {
        return $this->query($sql, $parameters)->getResultArray()[0];
    }

    public function fetchInt(string $sql, array $parameters = []): int
    {
        return (int)array_values($this->query($sql, $parameters)->getResultArray())[0];
    }

    public function fetchString(string $sql, array $parameters = []): string
    {
        return array_values($this->query($sql, $parameters)->getResultArray())[0];
    }

    public function write(string $sql, array $parameters = []): int
    {
        return $this->query($sql, $parameters)->getResultInt();
    }

    public function lastInsertId(): int
    {
        // only use the last insert id once
        $id                 = $this->lastInsertId;
        $this->lastInsertId = 0;
        return $id;
    }

    public function transaction(callable $function): void
    {
        $function();
    }

    public function getFooRepository(): AbstractRepository
    {
        $database = $this;
        return new class($database) extends AbstractRepository {
            public const TABLE_NAME = 'foo';

            public function getSchema(): Table
            {
                return new Table(self::TABLE_NAME, 'some foo things');
            }
        };
    }
}
