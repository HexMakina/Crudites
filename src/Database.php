<?php

namespace HexMakina\Crudites;

use HexMakina\Crudites\Queries\Select;
use HexMakina\Crudites\Table\Manipulation;
use HexMakina\Crudites\Table\Column;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableManipulationInterface;

class Database implements DatabaseInterface
{
    private ConnectionInterface $connection = null;

    /** @var array<string,TableManipulationInterface> */
    private $table_cache = [];

    /** @var array<string,array> */
    private $fk_by_table = [];

    /** @var array<string,array> */
    private $unique_by_table = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->introspect();
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function name(): string
    {
        return $this->connection()->databaseName();
    }

    public function inspect($table_name): TableManipulationInterface
    {
        if (isset($this->table_cache[$table_name])) {
            return $this->table_cache[$table_name];
        }

        $table = new Manipulation($table_name, $this->connection());

        foreach ($table->describe() as $column_name => $specs) {
            $column = new Column($table, $column_name, $specs);
            $this->setUniqueFor($table, $column);
            $this->setForeignFor($table, $column);
            $table->addColumn($column);
        }

        $this->table_cache[$table_name] = $table;

        return $this->table_cache[$table_name];
    }


    public function introspect(): void
    {
        // $previous_database_name = $this->connection()->databaseName();
        $query = $this->introspectionQuery($this->connection()->databaseName());

        $this->connection->useDatabase('INFORMATION_SCHEMA');
        $res = $this->connection->query($query);
        $this->connection->restoreDatabase();

        $res = $res->fetchAll();
        foreach ($res as $key_usage) {
            $table_name = $key_usage['TABLE_NAME'];

            // FOREIGN KEYS
            if (isset($key_usage['REFERENCED_TABLE_NAME'])) {
                $this->addForeignKeyByTable($table_name, $key_usage);
            }

            // PRIMARY & UNIQUES
            if (!isset($key_usage['POSITION_IN_UNIQUE_CONSTRAINT'])) {
                $this->addUniqueKeyByTable($table_name, $key_usage);
            }
        }

        $this->refactorConstraintNameIndex();
    }

    private function introspectionQuery(string $database_name): string
    {
        $fields = [
        'TABLE_NAME',
        'CONSTRAINT_NAME',
        'ORDINAL_POSITION',
        'COLUMN_NAME',
        'POSITION_IN_UNIQUE_CONSTRAINT',
        'REFERENCED_TABLE_NAME',
        'REFERENCED_COLUMN_NAME'
        ];

        $statement = 'SELECT ' . implode(', ', $fields)
        . ' FROM KEY_COLUMN_USAGE'
        . ' WHERE TABLE_SCHEMA = "%s"'
        . ' ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION';

        return sprintf($statement, $database_name);
    }


    private function setForeignFor(TableDescriptionInterface $table, ColumnInterface $column): ?string
    {
        $reference = $this->getForeignKey($table->name(), $column->name());

        if (!is_null($reference)) {
            $column->isForeign(true);
            $column->setForeignTableName($reference[0]);
            $column->setForeignColumnName($reference[1]);

            $table->addForeignKey($column);
        }

        return $reference;
    }

    private function getForeignKey(string $table_name, string $column_name): ?string
    {
        return $this->fk_by_table[$table_name][$column_name] ?? null;
    }

    private function setUniqueFor(TableDescriptionInterface $table, ColumnInterface $column): ?string
    {
        if (!$this->hasUniqueFor($table, $column)) {
            return null;
        }

        $unique_name = $this->unique_by_table[$table->name()][$column->name()][0];
        if (count($this->unique_by_table[$table->name()][$column->name()]) == 2) {
            // constraint name + column
            $column->uniqueName($unique_name);
            $table->addUniqueKey($unique_name, $column->name());
        } else {
            $column->uniqueGroupName($unique_name);
            unset($this->unique_by_table[$table->name()][$column->name()][0]);
            $table->addUniqueKey($unique_name, $this->unique_by_table[$table->name()][$column->name()]);
        }

        return $unique_name;
    }

    private function hasUniqueFor($table, $column): bool
    {
        return isset($this->unique_by_table[$table->name()][$column->name()]);
    }

    // vague memory that it makes later operation easier. written on the spot.. testing will reveal it's true nature
    private function refactorConstraintNameIndex(): void
    {
        foreach ($this->unique_by_table as $table_name => $uniques) {
            foreach ($uniques as $constraint_name => $columns) {
                foreach ($columns as $column_name) {
                    $this->unique_by_table[$table_name][$column_name] = [0 => $constraint_name] + $columns;
                }
                unset($this->unique_by_table[$table_name][$constraint_name]);
            }
        }
    }

    private function addUniqueKeyByTable($table_name, $key_usage): void
    {
        $constraint_name = $key_usage['CONSTRAINT_NAME'];
        $column_name = $key_usage['COLUMN_NAME'];

        $this->unique_by_table[$table_name] = $this->unique_by_table[$table_name] ?? [];
        $this->unique_by_table[$table_name][$constraint_name] = $this->unique_by_table[$table_name][$constraint_name] ?? [];
        $this->unique_by_table[$table_name][$constraint_name][$key_usage['ORDINAL_POSITION']] = $column_name;
    }

    private function addForeignKeyByTable($table_name, $key_usage): void
    {
        $this->fk_by_table[$table_name] = $this->fk_by_table[$table_name] ?? [];
        $this->fk_by_table[$table_name][$key_usage['COLUMN_NAME']] = [$key_usage['REFERENCED_TABLE_NAME'], $key_usage['REFERENCED_COLUMN_NAME']];
    }
}
