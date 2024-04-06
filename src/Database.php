<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\{ConnectionInterface, SchemaInterface, DatabaseInterface, TableInterface};
use HexMakina\Crudites\Relation\DatabaseRelations;

/**
 * Class Database
 *
 * Represents a database connection and provides a method for inspecting tables.
 */
class Database implements DatabaseInterface
{
    /** @var ConnectionInterface The database connection object */
    private ConnectionInterface $connection;

    /** @var Schema The database schema object */
    private Schema $schema;

    private DatabaseRelations $relations;

    /**
     * Database constructor.
     *
     * @param ConnectionInterface $connection The database connection object
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the database connection object.
     *
     * @return ConnectionInterface The database connection object
     */
    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function schema(): SchemaInterface
    {
        if(!isset($this->schema))
            $this->schema = new Schema($this);

        return $this->schema;
    }
    
    public function relations(): DatabaseRelations
    {
        if(!isset($this->relations))
            $this->relations = new DatabaseRelations($this);
        
        return $this->relations;
    }
    /**
     * Returns the name of the database.
     *
     * @return string The name of the database
     */
    public function name(): string
    {
        return $this->connection()->databaseName();
    }

    /**
     * Inspects a table and returns a TableInterface object.
     *
     * @param string $table_name The name of the table to inspect
     * @return TableInterface The TableInterface object representing the inspected table
     */
    /**
     * Inspects a table and returns a TableInterface object.
     *
     * @param string $table_name The name of the table to inspect
     * @return TableInterface The TableInterface object representing the inspected table
     * @deprecated Use the table() method instead
     */
    public function inspect(string $table_name): TableInterface
    {
        trigger_error('The inspect() method is deprecated. Use the table() method instead.', E_USER_DEPRECATED);
        return $this->schema()->table($table_name);
    }

        /**
     * Inspects a table and returns a TableInterface object.
     *
     * @param string $table_name The name of the table to inspect
     * @return TableInterface The TableInterface object representing the inspected table
     */
    public function table(string $name): TableInterface
    {
        return $this->schema()->table($name);
    }
}
