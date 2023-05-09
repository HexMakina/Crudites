<?php

/**
 * Simple PDO connection wrapper for Crudites
 *
 * Sets defaults: ERRMODE_EXCEPTION, CASE_NATURAL, FETCH_ASSOC, required by Crudites
 * Sets prefered fetch mode: associative array
 *
 * Throws \PDOException when DSN is wrong
 */

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\Crudites\{Source};
use PDO;
use PDOStatement;

class Connection implements ConnectionInterface
{
    private \PDO $pdo;

    private Source $source;

    // in case we change the database (f.i. INFORMATION_SCHEMA)
    private string $using_database;

    private static array $driver_default_options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // the one option you cannot change
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
    ];

    /*
     * @throws CruditesException when $dsn is parsed by Source
     */
    public function __construct(string $dsn, string $username = '', string $password = '', array $driver_options = [])
    {
        if (isset($driver_options[\PDO::ATTR_ERRMODE])) {
            unset($driver_options[\PDO::ATTR_ERRMODE]);
        }

        $driver_options = array_merge(self::$driver_default_options, $driver_options);

        $this->pdo = new \PDO($dsn, $username, $password, $driver_options);

        // if PDO didn't throw an Exception, finish the object
        $this->source = new Source($dsn);

        $this->useDatabase($this->source->database());
    }

    // database level
    public function useDatabase(string $name): void
    {
        $this->using_database = $name;
        $this->pdo->query(sprintf('USE `%s`;', $name));
    }

    public function restoreDatabase(): void
    {
        $this->useDatabase($this->source->database());
    }

    public function driverName(): mixed
    {
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function databaseName(): string
    {
        return $this->using_database;
    }


    // statements
    public function prepare(string $sql_statement, $options = []): ?\PDOStatement
    {
        $res = $this->pdo->prepare($sql_statement, $options);

        return $res instanceof \PDOStatement ? $res : null;
    }

    public function query(string $sql_statement, $fetch_mode = null, $fetch_col_num = null): ?\PDOStatement
    {
        if (is_null($fetch_mode)) {
            $res = $this->pdo->query($sql_statement);
        } else {
            $res = $this->pdo->query($sql_statement, $fetch_mode, $fetch_col_num);
        }

        return $res instanceof \PDOStatement ? $res : null;
    }

    public function alter(string $sql_statement): ?int
    {
        $res = $this->pdo->exec($sql_statement);
        return $res === false ? null : $res;
    }


    // transactions
    public function transact(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }

    // success & errors
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * @return mixed[]
     */
    public function errorInfo(): array
    {
        return $this->pdo->errorInfo();
    }

    public function errorCode(): string
    {
        return $this->pdo->errorCode();
    }
}
