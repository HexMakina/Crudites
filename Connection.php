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

class Connection implements ConnectionInterface
{
    private $source;

    // in case we change the database (f.i. INFORMATION_SCHEMA)
    private $using_database = null;

    private $pdo;

    private static $driver_default_options = [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // the one option you cannot change
      \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
    ];

    /*
     * @throws CruditesException when $dsn is parsed by Source
     */
    public function __construct($dsn, $username = '', $password = '', $driver_options = [])
    {
        $this->source = new Source($dsn);

        if (isset($driver_options[\PDO::ATTR_ERRMODE])) {
            unset($driver_options[\PDO::ATTR_ERRMODE]);
        }
        $driver_options = array_merge(self::$driver_default_options, $driver_options);

        $this->pdo = new \PDO($this->source->DSN(), $username, $password, $driver_options);

        $this->useDatabase($this->source->database());
    }

    // database level
    public function useDatabase($name)
    {
        $this->using_database = $name;
        $this->pdo->query(sprintf('USE `%s`;', $name));
    }

    public function restoreDatabase()
    {
        $this->useDatabase($this->source->database());
    }

    public function driverName()
    {
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function databaseName(): string
    {
        return $this->using_database;
    }

    // statements
    public function prepare($sql_statement, $options = [])
    {
        return $this->pdo->prepare($sql_statement, $options);
    }

    public function query($sql_statement, $fetch_mode = null, $fetch_col_num = null)
    {
        if (is_null($fetch_mode)) {
            return $this->pdo->query($sql_statement);
        }

        return $this->pdo->query($sql_statement, $fetch_mode, $fetch_col_num);
    }

    public function alter($sql_statement)
    {
        return $this->pdo->exec($sql_statement);
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

    public function errorInfo(): array
    {
        return $this->pdo->errorInfo();
    }

    public function errorCode(): ?string
    {
        return $this->pdo->errorCode();
    }
}
