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
     * Returns a representation the database schema
     * @throws \HexMakina\Crudites\CruditesException if the schema cannot be loaded
     */
    public function schema(): SchemaInterface
    {
        $database = $this->database();
        if(!isset($this->schema)){
            $this->schema = SchemaLoader::cache($database) ?? SchemaLoader::load($database, $this->pdo);
        }

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
}
