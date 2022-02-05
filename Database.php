<?php

namespace HexMakina\Crudites;

use HexMakina\Crudites\Queries\Select;
use HexMakina\Crudites\Queries\Describe;
use HexMakina\Crudites\Table\Manipulation;
use HexMakina\Crudites\Table\Column;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableManipulationInterface;

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

    private function setForeignFor($table, $column){
      $reference = $this->getForeignKey($table->name(), $column->name());

      if($reference === false)
        return null;

      $column->isForeign(true);
      $column->setForeignTableName($reference[0]);
      $column->setForeignColumnName($reference[1]);

      $table->addForeignKey($column);

      return $reference;
    }

    private function getForeignKey($table_name, $column_name)
    {
        return $this->fk_by_table[$table_name][$column_name] ?? false;
    }

    private function setUniqueFor($table, $column){
        if(!$this->hasUniqueFor($table, $colun))
          return null;

        $unique_name = $this->unique_by_table[$table->name()][$column->name()][0];
        if (count($this->unique_by_table[$table->name()][$column->name()]) == 2) {
            // constraint name + column
            $column->uniqueName($unique_name);
            $table->addUniqueKey($unique_name, $column->name());
        else{
            $column->uniqueGroupName($unique_name);
            unset($this->unique_by_table[$table->name()][$column->name()][0]);
            $table->addUniqueKey($unique_name, $this->unique_by_table[$table->name()][$column->name()]);
        }
        return $unique_name;
    }

    private function hasUniqueFor($table, $column): bool{
      return isset($this->unique_by_table[$table->name()][$column->name()]);
    }

    // vague memory that it makes later operation easier. written on the spot.. testing will reveal it's true nature
    private function refactorConstraintNameIndex()
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

    private function addUniqueKeyByTable($table_name, $key_usage)
    {
        $constraint_name = $key_usage['CONSTRAINT_NAME'];
        $column_name = $key_usage['COLUMN_NAME'];

        $this->unique_by_table[$table_name] = $this->unique_by_table[$table_name] ?? [];
        $this->unique_by_table[$table_name][$constraint_name] = $this->unique_by_table[$table_name][$constraint_name] ?? [];
        $this->unique_by_table[$table_name][$constraint_name][$key_usage['ORDINAL_POSITION']] = $column_name;
    }

    private function addForeignKeyByTable($table_name, $key_usage)
    {
        $this->fk_by_table[$table_name] = $this->fk_by_table[$table_name] ?? [];
        $this->fk_by_table[$table_name][$key_usage['COLUMN_NAME']] = [$key_usage['REFERENCED_TABLE_NAME'], $key_usage['REFERENCED_COLUMN_NAME']];
    }

    // private function describe($table_name): array
    // {
    //     $query = $this->connection()->query((new Describe($table_name)));
    //     if ($query === false) {
    //         throw new CruditesException('TABLE_DESCRIBE_FAILURE');
    //     }
    //
    //     return $query->fetchAll(\PDO::FETCH_UNIQUE);
    // }

    private function introspect()
    {
        $previous_database_name = $this->connection()->databaseName();
        $this->connection->useDatabase('INFORMATION_SCHEMA');
        $res = $this->connection->query($this->introspectionQuery($previous_database_name));
        $this->connection->useDatabase($previous_database_name);

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

    private function introspectionQuery($database_name){
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
}
