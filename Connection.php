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
use \HexMakina\Interfaces\Database\ConnectionInterface;

class Connection implements ConnectionInterface
{
    private $database_name = null;
    private $pdo;
    private $dsn;

    private static $driver_default_options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
    ];


    public function __construct($dsn, $username = '', $password = '', $driver_options=[])
    {
        $this->validate_dsn($dsn); //throws \PDOException
        $this->dsn = $dsn;

        if(isset($driver_options[\PDO::ATTR_ERRMODE])) {
          unset($driver_options[\PDO::ATTR_ERRMODE]); // mandatory for CRUDITES error handler
        }

        $driver_options = array_merge(self::$driver_default_options, $driver_options);
        $this->pdo = new \PDO($dsn, $username, $password, $driver_options);
    }

    public function driver_name()
    {
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function database_name(): string
    {
        return $this->database_name;
    }

    public function prepare($sql_statement, $options = [])
    {
        return $this->pdo->prepare($sql_statement, $options);
    }

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

    public function error_info(): array
    {
        return $this->pdo->errorInfo();
    }

    public function last_inserted_id($name = null)
    {
        return $this->pdo->lastInsertId();
    }

    public function error_code(): array
    {
        return $this->pdo->errorInfo();
    }

    private function validate_dsn($dsn)
    {
        $matches = [];
        if (preg_match('/^([a-z]+)\:/', $dsn, $matches) !== 1) {
            throw new \PDOException('DSN_NO_DRIVER');
        }

        if (!in_array($matches[1], \PDO::getAvailableDrivers(), true)) {
          throw new \PDOException('DSN_UNAVAILABLE_DRIVER');
        }

        if(preg_match('/dbname=(.+);/', $dsn, $matches)!==1){
          throw new \PDOException('DSN_NO_DBNAME');
        }

        $this->database_name = $matches[1];

        return true;
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

    public function useDatabase($db_name)
    {
      $this->pdo->query(sprintf('USE `%s`;',$db_name));
    }
}
