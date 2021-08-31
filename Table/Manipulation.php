<?php

namespace HexMakina\Crudites\Table;

use \HexMakina\Crudites\Interfaces\TableManipulationInterface;
use \HexMakina\Crudites\Interfaces\SelectInterface;
use \HexMakina\Crudites\Queries\Select;
use \HexMakina\Crudites\Queries\Insert;
use \HexMakina\Crudites\Queries\Update;
use \HexMakina\Crudites\Queries\Delete;

class Manipulation extends Description implements TableManipulationInterface
{

    // creates a new Row based on the table
    public function produce($dat_ass = []): Row
    {
        return new Row($this, $dat_ass);
    }

    public function restore($dat_ass): Row
    {
        $row = new Row($this);
        $row->load($dat_ass);
        return $row;
    }

    public function insert($values = []): Insert
    {
        $q = new Insert($this, $values);
        $q->connection($this->connection());
        return $q;
    }

    public function update($modifications = [], $conditions = []): Update
    {
        $q = new Update($this, $modifications, $conditions);
        $q->connection($this->connection());
        return $q;
    }

    public function delete($conditions = []): Delete
    {
        $q = new Delete($this, $conditions);
        $q->connection($this->connection());
        return $q;
    }

    public function select($columns = null, $table_alias = null): SelectInterface
    {
        $table_alias = $table_alias ?? $this->name();
        $q = new Select($columns ?? [$table_alias . '.*'], $this, $table_alias);
        $q->connection($this->connection());
        return $q;
    }
}
