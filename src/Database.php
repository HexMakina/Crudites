<?php

namespace HexMakina\Crudites;

use HexMakina\Crudites\Table\Table;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableInterface;

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

    /** @var array<string,TableInterface> The cache of table objects */
    private $table_cache = [];

    /**
     * Database constructor.
     *
     * @param ConnectionInterface $connection The database connection object
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema($this);
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
    public function inspect(string $table_name): TableInterface
    {
        if (isset($this->table_cache[$table_name])) {
            return $this->table_cache[$table_name];
        }

        $table = new Table($table_name, $this->connection());
        $table->describe($this->schema);

        $this->table_cache[$table_name] = $table;
        return $this->table_cache[$table_name];
    }
}
