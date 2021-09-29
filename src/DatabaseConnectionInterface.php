<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   23 Sep 2021
 */

declare(strict_types=1);

namespace LSS\YADbal;

use LSS\YADbal\DatabaseConnection\SQLQueryFactoryInterface;

interface DatabaseConnectionInterface extends SQLQueryFactoryInterface
{
    public function fetchAll(string $sql, array $parameters = []): array;

    public function fetchPairs(string $sql, array $parameters = []): array;

    public function fetchRow(string $sql, array $parameters = []): array;

    public function fetchCol(string $sql, array $parameters = []): array;

    public function fetchValue(string $sql, array $parameters = []): string;

    public function fetchInt(string $sql, array $parameters = []): int;

    public function fetchString(string $sql, array $parameters = []): string;

    public function write(string $sql, array $parameters = []): int;

    public function lastInsertId(): int;

    public function transaction(callable $function): void;
}
