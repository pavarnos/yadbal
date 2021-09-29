<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   23 Sep 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\DatabaseConnection;

/**
 * Use one of these for a read connection, one for a write connection.
 * Abstracts away all of PDO
 * Lazy: only connects when used
 * You probably don;t want to use this directly in your application. Prefer a DatabaseConnection instead because it
 * comes with a whole lot of utilities to reduce the amount of boilerplate code needed per query
 */
class PDOConnection
{
    private \PDO $pdo;

    public function __construct(
        private string $dsn,
        private ?string $userName = null,
        private ?string $password = null,
        private array $options = []
    ) {
    }

    public function lastInsertId(): int
    {
        return intval($this->connect()->lastInsertId());
    }

    public function transaction(callable $function): void
    {
        $pdo = $this->connect();
        try {
            $pdo->beginTransaction();
            $function();
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function connect(): \PDO
    {
        if (isset($this->pdo)) {
            return $this->pdo;
        }
        return $this->pdo = new \PDO($this->dsn, $this->userName, $this->password, $this->options);
    }

    public function perform(string $sql, array $parameters = []): \PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        foreach ($parameters as $index => $value) {
            $type = match (gettype($value)) {
                'boolean' => \PDO::PARAM_BOOL,
                'integer' => \PDO::PARAM_INT,
                'NULL' => \PDO::PARAM_NULL,
                default => \PDO::PARAM_STR,
            };
            // ? parameter indexes are 1 based (not 0 based), otherwise it is a :named parameter
            $statement->bindValue(is_int($index) ? ($index + 1) : $index, $value, $type);
        }
//        try {
        $statement->execute();
//        } catch (\PDOException $ex) {
//            throw new ERBException('Database Error in ' . $sql, 0, $ex);
//        }
        return $statement;
    }
}
