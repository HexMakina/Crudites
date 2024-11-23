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

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function hasTable(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    public function tables(): array
    {
        return array_keys($this->tables);
    }

    public function autoIncrementedPrimaryKey(string $table): ?string
    {
        foreach ($this->primaryKeys($table) as $column) {
            return $this->attributes($table, $column)->isAuto() ? $column : null;
        }

        return null;
    }

    public function primaryKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['primary'] : [];
    }

    public function foreignKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['foreign'] : [];
    }

    public function foreignKey(string $table, string $column): array
    {
        return $this->hasTable($table) ? ($this->tables[$table]['foreign'][$column] ?? []) : [];
    }

    public function uniqueKeys(string $table): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['unique'] : [];
    }

    public function uniqueColumnsByName(string $table, string $constraint): array
    {
        return $this->hasTable($table) ? $this->tables[$table]['unique'][$constraint] : [];
    }

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

    public function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) && !empty($this->tables[$table]['columns'][$column]);
    }

    public function columns(string $table): array
    {
        return $this->hasTable($table) ? array_keys($this->tables[$table]['columns']) : [];
    }

    public function column(string $table, string $column): array
    {
        if(!$this->hasColumn($table, $column)){
            throw new \InvalidArgumentException('CANNOT FIND COLUMN ' . $column . ' IN TABLE ' . $table);
        }

        return $this->tables[$table]['columns'][$column]['schema'] ?? throw new \InvalidArgumentException("ERR_MISSING_COLUMN_SCHEMA");
    }

    public function attributes(string $table, string $column): SchemaAttributeInterface
    {
        return new SchemaAttribute($this, $table, $column);
    }

    public function matchUniqueness(string $table, array $dat_ass): array
    {
        return $this->matchPrimaryKeys($table, $dat_ass) ?? $this->matchUniqueKeys($table, $dat_ass) ?? [];
    }

    public function matchPrimaryKeys(string $table, array $dat_ass): ?array
    {
        $primaryKeys = $this->primaryKeys($table);
        $match = array_intersect_key($dat_ass, array_flip($primaryKeys));

        return count($match) === count($primaryKeys) ? $match : null;
    }

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


    public function insert(string $table, array $dat_ass): QueryInterface
    {
        $insert = new Insert($table, $this->filterData($table, $dat_ass));
        $insert->connection($this->connection);

        return $insert;
    }

    public function update(string $table, array $alterations = [], array $conditions = []): QueryInterface
    {
        $update = new Update($table, $this->filterData($table, $alterations), $this->filterData($table, $conditions));
        $update->connection($this->connection);

        return $update;
    }

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
