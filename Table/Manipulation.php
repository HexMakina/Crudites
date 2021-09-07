<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Interfaces\Database\TableManipulationInterface;
use HexMakina\Interfaces\Database\SelectInterface;
use HexMakina\Interfaces\Database\QueryInterface;
use HexMakina\Interfaces\Database\RowInterface;
use HexMakina\Crudites\Queries\Select;
use HexMakina\Crudites\Queries\Insert;
use HexMakina\Crudites\Queries\Update;
use HexMakina\Crudites\Queries\Delete;

class Manipulation extends Description implements TableManipulationInterface
{

    // creates a new Row based on the table
    public function produce($dat_ass = []): RowInterface
    {
        return new Row($this, $dat_ass);
    }

    public function restore($dat_ass): RowInterface
    {
        $row = new Row($this);
        $row->load($dat_ass);
        return $row;
    }

    public function insert($values = []): QueryInterface
    {
        $q = new Insert($this, $values);
        $q->connection($this->connection());
        return $q;
    }

    public function update($modifications = [], $conditions = []): QueryInterface
    {
        $q = new Update($this, $modifications, $conditions);
        $q->connection($this->connection());
        return $q;
    }

    public function delete($conditions = []): QueryInterface
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
