<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   23 Sep 2021
 */

declare(strict_types=1);

namespace LSS\YADbal;

use LSS\YADbal\DatabaseConnection\PDOConnection;
use LSS\YADbal\DatabaseConnection\SQLQueryFactoryTrait;

/**
 * This is a lower level wrapper around PDO that lets us have
 * - read + write connections (if $write is non null)
 * - building sql queries and then running the query and fetching the result
 * - cleaner interfaces for common actions like reading a single value, or a pair of columns
 *
 * You probably want to a subclass of AbstractRepository or AbstractChildRepository
 */
class DatabaseConnection implements DatabaseConnectionInterface
{
    use SQLQueryFactoryTrait;

    private PDOConnection $write;

    public function __construct(private PDOConnection $read, PDOConnection $write = null)
    {
        $this->write = $write ?? $this->read;
        $this->setupQueryFactory();
    }

    public function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->read->perform($sql, $parameters)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function fetchPairs(string $sql, array $parameters = []): array
    {
        return $this->read->perform($sql, $parameters)->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
    }

    public function fetchRow(string $sql, array $parameters = []): array
    {
        $result = $this->read->perform($sql, $parameters)->fetch(\PDO::FETCH_ASSOC);
        return $result === false ? [] : (array) $result;
    }

    public function fetchCol(string $sql, array $parameters = []): array
    {
        return $this->read->perform($sql, $parameters)->fetchAll(\PDO::FETCH_COLUMN, 0) ?: [];
    }

    public function fetchValue(string $sql, array $parameters = []): string
    {
        return (string)$this->read->perform($sql, $parameters)->fetchColumn(0);
    }

    public function fetchInt(string $sql, array $parameters = []): int
    {
        return intval($this->read->perform($sql, $parameters)->fetchColumn(0));
    }

    public function fetchString(string $sql, array $parameters = []): string
    {
        return (string)($this->read->perform($sql, $parameters)->fetchColumn(0) ?? '');
    }

    public function write(string $sql, array $parameters = []): int
    {
        return $this->write->perform($sql, $parameters)->rowCount();
    }

    public function lastInsertId(): int
    {
        return $this->write->lastInsertId();
    }

    public function transaction(callable $function): void
    {
        $this->write->transaction($function);
    }
}
