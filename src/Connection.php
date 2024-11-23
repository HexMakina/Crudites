<?php

/**
 * Simple PDO connection wrapper for Crudites
 *
 * Provides a simple PDO connection wrapper for the Crudites library.
 * 
 * It encapsulates a PDO instance and provides methods for preparing and executing SQL statements.
 * All PDO methods are available through the magic __call method.
 * 
 * Also provides a Source instance to parse the DSN and a Schema instance to interact with the database schema.
 * 
 * Sets defaults: ERRMODE_EXCEPTION, CASE_NATURAL, FETCH_ASSOC, required by Crudites
 * Sets preferred fetch mode: associative array.
 *
 * @package HexMakina\Crudites
 */
namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\SchemaInterface;
use HexMakina\BlackBox\Database\SourceInterface;

class Connection implements ConnectionInterface
{
    /**
     * using \PDO encapsulation
     * 
     * the PDO instance is created on instantiation
     * the $pdo property is private, so it can only be accessed from within the class
     * 
     * the PDO instance is created with the following options:
     *      ERRMODE_EXCEPTION: throws exceptions on errors (mandatory)
     *      CASE_NATURAL: column names are returned in their natural case
     *      FETCH_ASSOC: returns rows as associative arrays
     * 
     */
    private \PDO $pdo;
    
    private string $dsn;
    private string $database;

    /**
     * @var Schema $schema used to interact with the database schema
     */
    private SchemaInterface $schema;

    
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
        // Create a new PDO instance with the given options
        $this->pdo = new \PDO($dsn, $username, $password, self::options($driver_options));

        // Create a new Source instance and parse the DSN to extract the database name
        $this->dsn = $dsn;
    }

    /**
     * Magic method, transfers calls to the PDO instance
     * but ConnectionInterface requires: commit(), rollback(), lastInserId(), errorInfo(), errorCode()
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->pdo, $method))
            throw new \BadMethodCallException(__FUNCTION__." method $method() does not exist in PDO class");
    
        return call_user_func_array([$this->pdo, $method], $args);
    }

    /**
     * Returns an array of PDO options for database connection.
     * all other options can be overridden by the provided array.
     * 
     * \PDO::ATTR_ERRMODE  is ALWAYS \PDO::ERRMODE_EXCEPTION,
     * 
     * @param array $provided An array of additional options to merge with the default options.
     * @return array The merged array of PDO options.
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
     * Returns the Schema instance used to interact with the database schema
     * 
     */
    public function schema(SchemaInterface $schema=null): SchemaInterface
    {
        if(!is_null($schema))
            $this->schema = $schema;

        if(!isset($this->schema))
            $this->schema = new Schema($this);

        return $this->schema;
    }

    public function database(): string
    {
        if (is_null($this->database)) {
            $matches = [];

            if (1 !== preg_match('/dbname=(.+);/', $this->name(), $matches) || !isset($matches[1])) {
                throw new CruditesException('DSN_DATABASE_NOT_FOUND');
            }

            $this->database = $matches[1];
        }
        return $this->database;
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

        return $res !== false ? $res : null;
    }

    /**
     * makes the PDO errorInfo array associative using 'state', 'code' and 'message' keys
     */
    public function error(): array
    {
        //url: https://www.php.net/manual/en/pdo.errorinfo.php
        $info = $this->pdo->errorInfo();

        // 0: the SQLSTATE associated with the last operation on the database handle
        //    SQLSTATE is a five characters alphanumeric identifier defined in the ANSI SQL standard
        $info['state'] = $info[0] ?? null;

        // 1: driver-specific error code.
        $info['code'] = $info[1] ?? null;

        // 2: driver-specific error message
        $info['message'] = $info[2] ?? null;
        
        return $info;
    }

    /**
     * Initiates a transaction, alias for beginTransaction()
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
     * @return bool True on success, false on failure
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rolls back the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }
}
