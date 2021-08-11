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

class Connection implements Interfaces\ConnectionInterface
{
    private $database_name = null;
    private $pdo;

    private static $driver_options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // mandatory in CRUDITES error handler
    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
    ];

    public function __construct($db_host, $db_port, $db_name, $charset = 'utf8', $username = '', $password = '')
    {
        $this->database_name = $db_name;
        $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$charset";
        $this->validate_dsn($dsn); //throws \PDOException
        $this->pdo = new \PDO($dsn, $username, $password, self::$driver_options);
    }

    public function driver_name()
    {
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function database_name() : string
    {
        return $this->database_name;
    }

    public function prepare($sql_statement, $options = [])
    {
        return $this->pdo->prepare($sql_statement, $options);
    }

    public function transact() : bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit() : bool
    {
        return $this->pdo->commit();
    }

    public function rollback() : bool
    {
        return $this->pdo->rollback();
    }

    public function error_info() : array
    {
        return $this->pdo->errorInfo();
    }

    public function last_inserted_id($name = null)
    {
        return $this->pdo->lastInsertId();
    }

    public function error_code() : array
    {
        return $this->pdo->errorInfo();
    }

    private function validate_dsn($dsn)
    {
        $matches = null;
        if (preg_match('/^([a-z]+)\:/', $dsn, $matches) !== 1) {
            throw new \PDOException('DSN Error: bad format');
        }

        $dsn_driver = $matches[1];
        $available_drivers = \PDO::getAvailableDrivers();
        if (!in_array($dsn_driver, $available_drivers, true)) {
            $err_msg = 'DSN Error: "%s" was given, "%s" are available';
            $err_msg = sprintf($err_msg, $dsn_driver, implode(', ', \PDO::getAvailableDrivers()));
            throw new \PDOException($err_msg);
        }

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
}
