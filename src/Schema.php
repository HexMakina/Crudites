<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\SchemaInterface;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\Crudites\Table\Table;

/**
 * The class provides an abstraction for database schema information.
 * It is build using the INFORMATION_SCHEMA database and the DESCRIBE query
 * Querying the INFORMATION_SCHEMA is done once, the KEY_COLUMN_USAGE table are stored in arrays
 * Querying DESCRIBE is done on demand, and the result is cached
 * 
 */

class Schema implements SchemaInterface
{
    /** @var array<string,array> */
    private array $unique_by_table = [];

    /** @var array<string,TableInterface> The cache of table objects */
    private array $table_cache = [];

    private Introspector $introspector;
    private ConnectionInterface $connection;

    /**
     * Constructor.
     *
     * @param DatabaseInterface $db The database instance to load the schema from.
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->connection = $db->connection();
        $this->introspector = new Introspector($db->name(), $db->connection());
        $this->fk_by_table = $this->introspector->foreignKeysByTable();
        $this->unique_by_table = $this->introspector->uniqueKeysByTable();
    }


    public function table(string $name): TableInterface
    {
        if (isset($this->table_cache[$name])) {
            return $this->table_cache[$name];
        }

        $table = new Table($name, $this->connection);
        $table->describe($this);
        
        $this->table_cache[$name] = $table;
        return $this->table_cache[$name];
    }

    /**
     * Gets the name of the unique constraint that the specified column belongs to, if any.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     *
     * @return string|null The name of the unique constraint, or null if the column is not part of a unique constraint.
     */
    public function uniqueConstraintNameFor(string $table, string $column): ?string
    {
        return $this->unique_by_table[$table][$column][0] ?? null;
    }

    /**
     * Gets an array of column names that are part of the same unique constraint as the specified column.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     *
     * @return array<int,string> An array of column names that are part of the same unique constraint as the specified column.
     */
    public function uniqueColumnNamesFor(string $table, string $column): array
    {
        return isset($this->unique_by_table[$table][$column][0])
               ? array_slice($this->unique_by_table[$table][$column], 1)
               : [];
    }

    /**
     * Gets the foreign key that references the specified column, if any.
     *
     * @param string|null $table_name The name of the table. Defaults to null.
     * @param string|null $column_name The name of the column. Defaults to null.
     *
     * @return array An array containing the name of the referenced table and column, empty if the column is not part of a foreign key.
     */
    public function foreignKeysFor(?string $table_name = null, ?string $column_name = null): array
    {
        return $this->introspector->foreignKeysByTable($table_name, $column_name);
    }
    
}
