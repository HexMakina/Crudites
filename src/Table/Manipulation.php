<?php

namespace HexMakina\Crudites\Table;

use HexMakina\BlackBox\Database\TableManipulationInterface;
use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\RowInterface;
use HexMakina\BlackBox\Database\QueryInterface;
use HexMakina\Crudites\Queries\{Select,Insert,Update,Delete};

class Manipulation extends Description implements TableManipulationInterface
{

    public function __construct(string $table_name, ConnectionInterface $connection)
    {
        $this->name = $table_name;
        $this->connection = $connection;
    }
    /**
      * creates a new Row based on the table
      * @param array<string,mixed> $dat_ass
      */
    public function produce(array $dat_ass = []): RowInterface
    {
        return new Row($this, $dat_ass);
    }

    /** @param array<string,mixed> $dat_ass */
    public function restore(array $dat_ass): RowInterface
    {
        $row = new Row($this);
        $row->load($dat_ass);
        return $row;
    }

    public function insert(array $dat_ass): QueryInterface
    {
        $insert = new Insert($this, $dat_ass);
        $insert->connection($this->connection());
        return $insert;
    }

    /** @param array<string,mixed> $modifications
      * @param array<string,mixed> $conditions
      */
    public function update(array $modifications = [], array $conditions = []): QueryInterface
    {
        $update = new Update($this, $modifications, $conditions);
        $update->connection($this->connection());
        return $update;
    }

    /** @param array<string,mixed> $conditions */
    public function delete(array $conditions): QueryInterface
    {
        $delete = new Delete($this, $conditions);
        $delete->connection($this->connection());
        return $delete;
    }

    public function select(array $columns = null, string $table_alias = null): SelectInterface
    {
        $table_alias ??= $this->name();
        $select = new Select($columns ?? [$table_alias . '.*'], $this, $table_alias);
        $select->connection($this->connection());
        return $select;
    }
}
