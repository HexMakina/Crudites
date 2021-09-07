<?php

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Queries\Select;
use \HexMakina\Crudites\Queries\Describe;
use \HexMakina\Crudites\Table\Manipulation;
use \HexMakina\Crudites\Table\Column;
use \HexMakina\Interfaces\Database\ConnectionInterface;
use \HexMakina\Interfaces\Database\DatabaseInterface;
use \HexMakina\Interfaces\Database\TableManipulationInterface;

class Database implements DatabaseInterface
{
    private $connection = null;
    private $table_cache = [];
    private $fk_by_table = [];
    private $unique_by_table = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->introspect();
    }

    public function name()
    {
        return $this->connection()->databaseName();
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function introspect()
    {
        $statement = 'SELECT TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION, COLUMN_NAME, POSITION_IN_UNIQUE_CONSTRAINT, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = "%s" ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION';

        $this->connection->useDatabase('INFORMATION_SCHEMA');
        $res = $this->connection->query(sprintf($statement,$this->name()))->fetchAll();
        $this->connection->useDatabase($this->name());

        foreach ($res as $key_usage) {
            $table_name = $key_usage['TABLE_NAME'];

            if (isset($key_usage['REFERENCED_TABLE_NAME'])) { // FOREIGN KEYS
                $this->fk_by_table[$table_name] = $this->fk_by_table[$table_name] ?? [];
                $this->fk_by_table[$table_name][$key_usage['COLUMN_NAME']] = [$key_usage['REFERENCED_TABLE_NAME'], $key_usage['REFERENCED_COLUMN_NAME']];
            }

            if (!isset($key_usage['POSITION_IN_UNIQUE_CONSTRAINT'])) { // PRIMARY & UNIQUES
                $constraint_name = $key_usage['CONSTRAINT_NAME'];
                $column_name = $key_usage['COLUMN_NAME'];

                $this->unique_by_table[$table_name] = $this->unique_by_table[$table_name] ?? [];
                $this->unique_by_table[$table_name][$constraint_name] = $this->unique_by_table[$table_name][$constraint_name] ?? [];
                $this->unique_by_table[$table_name][$constraint_name][$key_usage['ORDINAL_POSITION']] = $column_name;
            }
        }

        foreach ($this->unique_by_table as $table_name => $uniques) {
            foreach ($uniques as $constraint_name => $columns) {
                foreach ($columns as $column_name) {
                    $this->unique_by_table[$table_name][$column_name] = [0 => $constraint_name] + $columns;
                }
                unset($this->unique_by_table[$table_name][$constraint_name]);
            }
        }
    }

    public function inspect($table_name): TableManipulationInterface
    {
        if (isset($this->table_cache[$table_name])) {
            return $this->table_cache[$table_name];
        }


        $describe = (new Describe($table_name));
        $describe->connection($this->connection());
        $description = $describe->ret();

      // TODO test this when all is back to normal 2021.03.09
        if ($description === false) {
            throw new \PDOException("Unable to describe $table_name");
        }

        $table = new Manipulation($table_name, $this->connection());

        foreach ($description as $column_name => $specs) {
            $column = new Column($table, $column_name, $specs);

          // handling usage constraints
            if (isset($this->unique_by_table[$table_name][$column_name])) {
                $unique_name = $this->unique_by_table[$table_name][$column_name][0];

                switch (count($this->unique_by_table[$table_name][$column_name])) {
                    case 2: // constraint name + column
                        $column->uniqueName($unique_name);
                        $table->add_unique_key($unique_name, $column_name);
                        break;

                    default:
                        $column->uniqueGroupName($unique_name);
                        unset($this->unique_by_table[$table_name][$column_name][0]);
                        $table->add_unique_key($unique_name, $this->unique_by_table[$table_name][$column_name]);
                        break;
                }
            }
            // handling usage foreign keys
            if (($reference = $this->getForeignKey($table_name, $column_name)) !== false) {
                $column->isForeign(true);
                $column->setForeignTableName($reference[0]);
                $column->setForeignColumnName($reference[1]);

                $table->add_foreign_key($column);
            }
            $table->add_column($column);
        }
      // ddt($table);
        $this->table_cache[$table_name] = $table;

        return $this->table_cache[$table_name];
    }

    public function getForeignKey($table_name, $column_name)
    {
        return $this->fk_by_table[$table_name][$column_name] ?? false;
    }
}
