<?php

/**
 * Simple PDO connection wrapper for Crudites
 *
 * This class provides a simple PDO connection wrapper for the Crudites library.
 *
 * Sets defaults: ERRMODE_EXCEPTION, CASE_NATURAL, FETCH_ASSOC, required by Crudites
 * Sets preferred fetch mode: associative array.
 *
 * @package HexMakina\Crudites
 */
namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\Crudites\Source;
use PDO;
use PDOStatement;

class Connection implements ConnectionInterface
{
    /**
     * @var \PDO $pdo The PDO instance used for the connection
     */
    private \PDO $pdo;

    /**
     * @var Source $source The Source instance used to parse the DSN
     */
    private Source $source;

    /**
     * @var string $using_database The name of the database currently in use
     */
    private string $using_database;

    /**
     * @var array $driver_default_options Default options to be used for the PDO instance
     */
    private static array $driver_default_options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // the one option you cannot change
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
    ];

    /**
     * Constructor.
     *
     * @param string $dsn The Data Source Name for the connection
     * @param string $username The username to use for the connection (optional)
     * @param string $password The password to use for the connection (optional)
     * @param array $driver_options Additional driver options for the PDO instance (optional)
     *
     * @throws \PDOException When the DSN is invalid
     */
    public function __construct(string $dsn, string $username = '', string $password = '', array $driver_options = [])
    {
        // Remove ERRMODE_EXCEPTION from the custom driver options, as it is required to be ERRMODE_EXCEPTIOn
        if (isset($driver_options[\PDO::ATTR_ERRMODE])) {
            unset($driver_options[\PDO::ATTR_ERRMODE]);
        }

        // Merge driver options with default options
        $driver_options = array_merge(self::$driver_default_options, $driver_options);

        // Create a new PDO instance with the given options
        $this->pdo = new \PDO($dsn, $username, $password, $driver_options);

        // Create a new Source instance and parse the DSN to extract the database name
        $this->source = new Source($dsn);

        // Set the using_database property to the extracted database name
        $this->useDatabase($this->source->database());
    }

    /**
     * Set the currently used database.
     *
     * @param string $name The name of the database to use
     */
    public function useDatabase(string $name): void
    {
        $this->using_database = $name;
        $this->pdo->query(sprintf('USE `%s`;', $name));
    }

    /**
     * Restore the default database after a useDatabase call
     */
    public function restoreDatabase(): void
    {
        $this->useDatabase($this->source->database());
    }

    /**
     * Returns the name of the driver used by the PDO instance.
     *
     * @return string The name of the driver.
     */
    public function driverName(): mixed
    {
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

  /**
     * Returns the name of the currently used database
     *
     * @return string the name of the database
     */
    public function databaseName(): string
    {
        return $this->using_database;
    }

    /**
     * Prepares an SQL statement for execution and returns a statement object
     *
     * @param string $sql_statement the SQL statement to prepare
     * @param array $options options for the prepared statement
     * @return PDOStatement|null a PDOStatement object or null on failure
     */
    public function prepare(string $sql_statement, $options = []): ?\PDOStatement
    {
        $res = $this->pdo->prepare($sql_statement, $options);

        return $res instanceof \PDOStatement ? $res : null;
    }

    /**
     * Executes an SQL statement and returns a statement object or null on failure
     *
     * @param string $sql_statement the SQL statement to execute
     * @param mixed $fetch_mode the fetch mode to use, or null to use the default mode
     * @param mixed $fetch_col_num a fetch argument used by some fetch modes
     * @return PDOStatement|null a PDOStatement object or null on failure
     */
    public function query(string $sql_statement, $fetch_mode = null, $fetch_col_num = null): ?\PDOStatement
    {
        if (is_null($fetch_mode)) {
            $res = $this->pdo->query($sql_statement);
        } else {
            $res = $this->pdo->query($sql_statement, $fetch_mode, $fetch_col_num);
        }

        return $res instanceof \PDOStatement ? $res : null;
    }

    /**
     * Executes an SQL statement and returns the number of affected rows or null on failure
     *
     * @param string $sql_statement the SQL statement to execute
     * @return int|null the number of affected rows or null on failure
     */
    public function alter(string $sql_statement): ?int
    {
        $res = $this->pdo->exec($sql_statement);
        return $res === false ? null : $res;
    }

    /**
     * Initiates a transaction
     *
     * @return bool true on success or false on failure
     */
    public function transact(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits the current transaction
     *
     * @return bool true on success or false on failure
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rolls back the current transaction
     *
     * @return bool true on success or false on failure
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param string|null $name the name of the sequence object from which the ID should be returned
     * @return mixed the ID of the last inserted row or sequence value
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }   

    /**
     * Returns extended error information for the last operation on the database handle.
     *
     * The array consists of the following fields:
     *   0: error code
     *   1: error message
     *   2: an array of driver specific error information
     *
     * @return mixed[]|null An array of error information, or null if no error occurred
     */
    public function errorInfo(): ?array
    {
        return $this->pdo->errorInfo();
    }

    /**
     * Retrieves the SQLSTATE associated with the last operation on the database handle.
     *
     * @return string|null The error code associated with the last operation, or null if no error occurred
     */
    public function errorCode(): ?string
    {
        return $this->pdo->errorCode();
    }
}
