<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\QueryInterface;
use HexMakina\BlackBox\Queries\SelectInterface;
use HexMakina\BlackBox\Database\{ConnectionInterface, SchemaInterface, SchemaAttributeInterface};

use HexMakina\Crudites\SchemaLoader;
use HexMakina\Crudites\Queries\{Select, Insert, Update, Delete};

/**
 * The class provides an abstraction for database schema information.
 * It is build using the INFORMATION_SCHEMA database
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
     * Tests if the specified table exists.
     * @param string $table The name of the table.
     * @return bool True if the table exists, false otherwise.
     */
    public function hasTable(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    /**
     * Gets the names of the tables in the database.
     *
     * @return array<string> The names of the tables.
     */
    public function tables(): array
    {
        return array_keys($this->tables);
    }


    /**
     * Gets the auto incremented primary key column for the specified table.
     * 
     * @param string $table The name of the table.
     * @return string|null The name of the auto incremented primary key column, or null if the table has no auto incremented primary key.
     */
    public function autoIncrementedPrimaryKey(string $table): ?string
    {
        foreach ($this->primaryKeys($table) as $column) {
            return $this->attributes($table, $column)->isAuto() ? $column : null;
        }

        return null;
    }

    /**
     * Gets the primary key columns for the specified table.
     *
     * @param string $table The name of the table.
     *
     * @return array<int,string> An array of column names that are part of the primary key.
     */
    public function primaryKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['primary'] : [];
    }


    /**
     * Gets the foreign key columns for the specified table.
     *
     * @param string $table The name of the table.
     *
     * @return array<string,array> An array of foreign references [table, column], indexed by column name.
     */
    public function foreignKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['foreign'] : [];
    }

    /**
     * Gets the foreign key columns for the specified table and column.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     *
     * @return array<string,array> An array [table, column] of the foreign reference.
     */
    public function foreignKey(string $table, string $column): array
    {
        return $this->hasTable($table) ? ($this->tables[$table]['foreign'][$column] ?? []) : [];
    }

    /**
     * Gets the unique constraints for the specified table
     *
     * @param string|null $table The table name.
     *
     * @return array the sets of unique columns, indexed by constraint name
     */
    public function uniqueKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['unique'] : [];
    }

    public function uniqueColumns(string $table, string $constraint): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['unique'][$constraint] : [];
    }

    /**
     * Gets the unique constraints for the specified table and column.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     *
     * @return array The sets of unique constraints columns the column belongs to, indexed by constraint name.
     */
    public function uniqueColumnsFor(string $table, string $column): array
    {
        if (!$this->hasColumn($table, $column)) {
            return [];
        }
        $ret = [];

        $uniques = $this->uniqueKeys($table);
        foreach ($this->tables[$table]['columns'][$column]['unique'] as $constraint_name) {
            $ret[$constraint_name] = $this->uniqueColumns($table, $constraint_name);
        }

        return $ret;
    }

    /**
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     * @return bool True if the column exists in the table, false otherwise.
     */
    public function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) ? in_array($column, $this->columns($table)) : false;
    }

    /**
     * @param string $table The name of the table.
     * @return array<string> The names of the columns in the table.
     */
    public function columns(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['columns'] : [];
    }

    public function attributes(string $table, string $column): SchemaAttributeInterface
    {
        return new SchemaAttribute($this, $table, $column);
    }

    public function insert(string $table, array $dat_ass): QueryInterface
    {
        $insert = new Insert($table, $this->filterData($table, $dat_ass));
        $insert->connection($this->connection);

        return $insert;
    }

    /** @param array<string,mixed> $alterations
     * @param array<string,mixed> $conditions
     */
    public function update(string $table, array $alterations = [], array $conditions = []): QueryInterface
    {
        $update = new Update($table, $this->filterData($table, $alterations), $this->filterData($table, $conditions));
        $update->connection($this->connection);

        return $update;
    }


    /** @param array<string,mixed> $conditions */
    public function delete(string $table, array $conditions): QueryInterface
    {
        $delete = new Delete($table, $this->filterData($table, $conditions));
        $delete->connection($this->connection);

        return $delete;
    }

    public function select(string $table, array $columns = null, string $table_alias = null): SelectInterface
    {
        $table_alias ??= $table;

        $select = new Select($columns, $table, $table_alias);
        $select->connection($this->connection);

        return $select;
    }

    public function matchUniqueness(string $table, array $dat_ass): array
    {
        return $this->matchPrimaryKeys($table, $dat_ass) ?? $this->matchUniqueKeys($table, $dat_ass) ?? [];
    }

    public function matchPrimaryKeys(string $table, array $dat_ass): ?array
    {
        $match = [];

        foreach ($this->primaryKeys($table) as $column) {
            if (array_key_exists($column, $dat_ass)) {
                $match[$column] = $dat_ass[$column];
            } else {
                return null;
            }
        }

        return $match;
    }

    public function matchUniqueKeys(string $table, array $dat_ass): ?array
    {

        foreach ($this->uniqueKeys($table) as $constraint => $columns) {

            $match = [];
            $missing = false;

            foreach ($columns as $column) {
                if (array_key_exists($column, $dat_ass)) {
                    $match[$column] = $dat_ass[$column];
                } else {
                    $missing = true;
                }
            }

            if ($missing === false) {
                return $match;
            }
        }

        return null;
    }

    private function filterData(string $table, array $dat_ass)
    {
        return array_intersect_key($dat_ass, array_flip($this->columns($table)));
    }
}
