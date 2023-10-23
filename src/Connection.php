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
class Connection implements ConnectionInterface
{
    /**
     * sing encapsulation, the PDO instance is not accessible from outside the class
     * this allows to change the underlying database driver without breaking the Crudites API
     * (as long as the new driver is compatible with PDO)
     * the PDO instance is created in the constructor and stored in the $pdo property
     * the $pdo property is private, so it can only be accessed from within the class
     * 
     * the PDO instance is created with the following options:
     *      ERRMODE_EXCEPTION: throws exceptions on errors
     *      CASE_NATURAL: column names are returned in their natural case
     *      FETCH_ASSOC: returns rows as associative arrays
     * 
     */
    private \PDO $pdo;
    
    /**
     * @var string $using_database The name of the database currently in use
     */
    private string $using_database;

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
        // Create a new Source instance and parse the DSN to extract the database name
        $this->source = new Source($dsn);

        // Create a new PDO instance with the given options
        $this->pdo = new \PDO($dsn, $username, $password, self::options($driver_options));
        
        // Set the using_database property to the extracted database name
        $this->useDatabase($this->source->database());
    }

    /**
     * Magic method, transfers calls to the PDO instance
     * replaced: commit(), rollback(), lastInserId(), errorInfo(), errorCode()
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->pdo, $method))
            throw new \BadMethodCallException("Method $method() does not exist against PDO");
    
        return call_user_func_array([$this->pdo, $method], $args);
    }
    
    /**
     * Returns the default options to be used for the PDO instance
     *
     * @param array $provided The provided options
     * @return array The default options
     */
    private static function options(array $provided = []): array
    {
        if (isset($provided[\PDO::ATTR_ERRMODE])) {
            unset($provided[\PDO::ATTR_ERRMODE]);          // the one option you cannot change
        }
        
        return array_merge(
            [
                \PDO::ATTR_ERRMODE  => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_CASE     => \PDO::CASE_NATURAL,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]
            , $provided);
    }

    /**
     * Set the currently used database
     * Keeps track of the currently used database in the $using_database property
     *
     * @param string $name The name of the database to use
     */
    public function useDatabase(string $name): void
    {
        $this->pdo->query(sprintf('USE `%s`;', $name));
        $this->using_database = $name;
    }

    /**
     * Resets to the original database after ::useDatabase()
     */
    public function restoreDatabase(): void
    {
        $this->useDatabase($this->source->database());
    }

    /**
     * Returns the name of the driver used by the PDO instance.
     */
    public function driverName()
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
     * @return \PDOStatement|null a PDOStatement object or null on failure
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
     * @return \PDOStatement|null a PDOStatement object or null on failure
     */
    public function query(string $sql_statement, $fetch_mode = null, $fetch_col_num = null): ?\PDOStatement
    {
        try{
            if (is_null($fetch_mode)) {
                $res = $this->pdo->query($sql_statement);
            } else {
                $res = $this->pdo->query($sql_statement, $fetch_mode, $fetch_col_num);
            }
        }catch(\PDOException $e){
            throw new CruditesException($e->getMessage(), $e->getCode());   
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
     * makes the errorInfo array associative 'state', 'message', 'details'
     * url: https://www.php.net/manual/en/pdo.errorinfo.php

     * 
     * SQLSTATE is a five characters alphanumeric identifier defined in the ANSI SQL standard
     */
    public function error(): array
    {
        //url: https://www.php.net/manual/en/pdo.errorinfo.php
        $info = $this->pdo->errorInfo();

        // 0: the SQLSTATE associated with the last operation on the database handle
        $info['state'] = $info[0] ?? null;

        // 1: error message
        $info['message'] = $info[1] ?? null;

        // 2: an array of driver specific error information
        $info['details'] = $info[2] ?? null;
        
        return $info;
    }
}
