<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\{QueryInterface, SelectInterface};
use HexMakina\BlackBox\Database\{ConnectionInterface, SchemaInterface, SchemaAttributeInterface};

use HexMakina\Crudites\SchemaLoader;
use HexMakina\Crudites\Queries\{Select, Insert, Update, Delete};

/**
 * The class provides an abstraction for database schema information.
 * It is built using the INFORMATION_SCHEMA database.
 */
class Schema implements SchemaInterface
{
    private ConnectionInterface $connection;
    private array $tables = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->tables = SchemaLoader::load($connection);
    }

    /**
     * Checks if the specified table exists.
     * 
     * @param string $table The name of the table.
     * @return bool True if the table exists, false otherwise.
     */
    public function hasTable(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    /**
     * Retrieves the names of the tables in the database.
     *
     * @return array<string> The names of the tables.
     */
    public function tables(): array
    {
        return array_keys($this->tables);
    }

    /**
     * Retrieves the auto-incremented primary key column for the specified table.
     * 
     * @param string $table The name of the table.
     * @return string|null The name of the auto-incremented primary key column, or null if the table has no auto-incremented primary key.
     */
    public function autoIncrementedPrimaryKey(string $table): ?string
    {
        foreach ($this->primaryKeys($table) as $column) {
            return $this->attributes($table, $column)->isAuto() ? $column : null;
        }

        return null;
    }

    /**
     * Retrieves the primary key columns for the specified table.
     *
     * @param string $table The name of the table.
     * @return array<int,string> An array of column names that are part of the primary key.
     */
    public function primaryKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['primary'] : [];
    }

    /**
     * Retrieves the foreign key columns for the specified table.
     *
     * @param string $table The name of the table.
     * @return array<string,array> An array of foreign references [table, column], indexed by column name.
     */
    public function foreignKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['foreign'] : [];
    }

    /**
     * Retrieves the foreign key columns for the specified table and column.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     * @return array<string,array> An array [table, column] of the foreign reference.
     */
    public function foreignKey(string $table, string $column): array
    {
        return $this->hasTable($table) ? ($this->tables[$table]['foreign'][$column] ?? []) : [];
    }

    /**
     * Retrieves the unique constraints for the specified table.
     *
     * @param string $table The table name.
     * @return array The sets of unique columns, indexed by constraint name.
     */
    public function uniqueKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['unique'] : [];
    }

    /**
     * Retrieves the unique columns for the specified table and constraint name.
     *
     * @param string $table The name of the table.
     * @param string $constraint The name of the constraint.
     * @return array The unique columns for the specified constraint.
     */
    public function uniqueColumnsByName(string $table, string $constraint): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['unique'][$constraint] : [];
    }

    /**
     * Retrieves the unique constraints for the specified table and column.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     * @return array The sets of unique constraints columns the column belongs to, indexed by constraint name.
     */
    public function uniqueColumnsFor(string $table, string $column): array
    {
        $ret = [];
        
        if ($this->hasColumn($table, $column)) {
            foreach ($this->tables[$table]['columns'][$column]['unique'] as $constraint_name) {
                $ret[$constraint_name] = $this->uniqueColumnsByName($table, $constraint_name);
            }
        }

        return $ret;
    }

    /**
     * Checks if the specified column exists in the table.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     * @return bool True if the column exists in the table, false otherwise.
     */
    public function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) && !empty($this->tables[$table]['columns'][$column]);
    }

    /**
     * Retrieves the names of the columns in the specified table.
     *
     * @param string $table The name of the table.
     * @return array<string> The names of the columns in the table.
     */
    public function columns(string $table): array
    {
        return $this->hasTable($table) ? array_keys($this->tables[$table]['columns']) : [];
    }

    /**
     * Retrieves the schema information for the specified column in the table.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     * @return array The schema information for the column.
     * @throws \InvalidArgumentException If the column does not exist in the table.
     */
    public function column(string $table, string $column): array
    {
        if(!$this->hasColumn($table, $column)){
            throw new \InvalidArgumentException('CANNOT FIND COLUMN ' . $column . ' IN TABLE ' . $table);
        }

        return $this->tables[$table]['columns'][$column]['schema'] ?? throw new \InvalidArgumentException("ERR_MISSING_COLUMN_SCHEMA");
    }

    /**
     * Retrieves the attributes for the specified column in the table.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     * @return SchemaAttributeInterface The attributes of the column.
     */
    public function attributes(string $table, string $column): SchemaAttributeInterface
    {
        return new SchemaAttribute($this, $table, $column);
    }

    /**
     * Creates an insert query for the specified table with the given data.
     *
     * @param string $table The name of the table.
     * @param array $dat_ass The data to insert.
     * @return QueryInterface The insert query.
     */
    public function insert(string $table, array $dat_ass): QueryInterface
    {
        $insert = new Insert($table, $this->filterData($table, $dat_ass));
        $insert->connection($this->connection);

        return $insert;
    }

    /**
     * Creates an update query for the specified table with the given alterations and conditions.
     *
     * @param string $table The name of the table.
     * @param array<string,mixed> $alterations The data to update.
     * @param array<string,mixed> $conditions The conditions for the update.
     * @return QueryInterface The update query.
     */
    public function update(string $table, array $alterations = [], array $conditions = []): QueryInterface
    {
        $update = new Update($table, $this->filterData($table, $alterations), $this->filterData($table, $conditions));
        $update->connection($this->connection);

        return $update;
    }

    /**
     * Creates a delete query for the specified table with the given conditions.
     *
     * @param string $table The name of the table.
     * @param array<string,mixed> $conditions The conditions for the delete.
     * @return QueryInterface The delete query.
     */
    public function delete(string $table, array $conditions): QueryInterface
    {
        $delete = new Delete($table, $this->filterData($table, $conditions));
        $delete->connection($this->connection);

        return $delete;
    }

    /**
     * Creates a select query for the specified table with the given columns and table alias.
     *
     * @param string $table The name of the table.
     * @param array|null $columns The columns to select.
     * @param string|null $table_alias The alias for the table.
     * @return SelectInterface The select query.
     */
    public function select(string $table, array $columns = null, string $table_alias = null): SelectInterface
    {
        $table_alias ??= $table;

        $select = new Select($columns, $table, $table_alias);
        $select->connection($this->connection);

        return $select;
    }

    /**
     * Matches the uniqueness constraints for the specified table with the given data.
     *
     * @param string $table The name of the table.
     * @param array $dat_ass The data to match.
     * @return array The matched uniqueness constraints.
     */
    public function matchUniqueness(string $table, array $dat_ass): array
    {
        return $this->matchPrimaryKeys($table, $dat_ass) ?? $this->matchUniqueKeys($table, $dat_ass) ?? [];
    }

    /**
     * Matches the primary key constraints for the specified table with the given data.
     *
     * @param string $table The name of the table.
     * @param array $dat_ass The data to match.
     * @return array|null The matched primary key constraints, or null if no match is found.
     */
    public function matchPrimaryKeys(string $table, array $dat_ass): ?array
    {
        $primaryKeys = $this->primaryKeys($table);
        $match = array_intersect_key($dat_ass, array_flip($primaryKeys));

        return count($match) === count($primaryKeys) ? $match : null;
    }

    /**
     * Matches the unique key constraints for the specified table with the given data.
     *
     * @param string $table The name of the table.
     * @param array $dat_ass The data to match.
     * @return array|null The matched unique key constraints, or null if no match is found.
     */
    public function matchUniqueKeys(string $table, array $dat_ass): ?array
    {
        foreach ($this->uniqueKeys($table) as $constraint => $columns) {
            $match = array_intersect_key($dat_ass, array_flip($columns));

            if (count($match) === count($columns)) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Filters the given data to only include columns that exist in the specified table.
     *
     * @param string $table The name of the table.
     * @param array $dat_ass The data to filter.
     * @return array The filtered data.
     */
    private function filterData(string $table, array $dat_ass)
    {
        return array_intersect_key($dat_ass, array_flip($this->columns($table)));
    }
}
