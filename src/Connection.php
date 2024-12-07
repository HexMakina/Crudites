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
use HexMakina\BlackBox\Database\ResultInterface;
use HexMakina\BlackBox\Database\SchemaInterface;
use HexMakina\Crudites\Schema\SchemaLoader;

class Connection extends \PDO implements ConnectionInterface
{
    private string $dsn;
    private ?string $database = null;
    private SchemaInterface $schema;

    /**
     * Constructor. Same as PDO constructor
     *
     * @throws \PDOException if the attempt to connect to the requested database fails
     */
    public function __construct(string $dsn, string $username = '', string $password = '', array $driver_options = [])
    {
        try {
            $this->dsn = $dsn;
            $options = self::options($driver_options);
            parent::__construct($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new CruditesException($e->getMessage(), $e->getCode());
        }
    }
    
    public function __toString()
    {
        return $this->dsn;
    }


    /**
     * Returns the name of the database
     * @throws \HexMakina\Crudites\CruditesException if the database name cannot be extracted from the DSN
     */
    public function database(): string
    {
        if ($this->database === null) {
            $this->database = $this->extractDatabaseNameFromDsn();
        }
        return $this->database;
    }


    /**
     * Returns a representation the database schema
     * @throws \HexMakina\Crudites\CruditesException if the schema cannot be loaded
     */
    public function schema(): SchemaInterface
    {
        $database = $this->database();
        if (!isset($this->schema)) {
            $this->schema = SchemaLoader::cache($database) ?? SchemaLoader::load($database, $this->pdo());
        }
        return $this->schema;
    }

    public function result($statement, $bindings = []): ResultInterface
    {
        return new Result($this, $statement, $bindings);
    }

    public function pdo(): \PDO
    {
        return $this;
    }

    
    /**
     * Extracts the database name from the DSN
     * @throws \HexMakina\Crudites\CruditesException if the database name cannot be extracted from the DSN
     */
    private function extractDatabaseNameFromDsn(): string
    {
        $matches = [];
        if (1 === preg_match('/dbname=(.+);/', $this->dsn, $matches) && isset($matches[1])) {
            return $matches[1];
        }
        throw new CruditesException('PARSING_DSN_FOR_DATABASE');
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
            ],
            $provided
        );
    }
}
