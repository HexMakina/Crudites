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
    private SchemaInterface $schema;

    /**
     * Constructor. Same as PDO constructor
     *
     * @throws \PDOException if the attempt to connect to the requested database fails
     */
    public function __construct(string $dsn, string $username = '', string $password = '', array $driver_options = [])
    {
        try{
            $this->dsn = $dsn;

            // Create a new PDO instance with the given options
            $this->pdo = new \PDO($dsn, $username, $password, self::options($driver_options));
        }
        catch(\PDOException $e){
            throw new CruditesException($e->getMessage(), $e->getCode());
        }
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
            arrays: [
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
        if($schema !== null)
            $this->schema = $schema;

        if(!isset($this->schema))
            $this->schema = new Schema($this);

        return $this->schema;
    }

    public function database(): string
    {
        if ($this->database === null) {
            $matches = [];

            if (1 !== preg_match('/dbname=(.+);/', $this->dsn, $matches) || !isset($matches[1])) {
                throw new CruditesException('DSN_DATABASE_NOT_FOUND');
            }

            $this->database = $matches[1];
        }
        return $this->database;
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
