<?php

namespace HexMakina\Crudites\Table;

use \HexMakina\Crudites\Interfaces\TableManipulationInterface;
use \HexMakina\Crudites\Queries\{BaseQueryWhere,Insert,SelectJoin,Update,Delete};

class Manipulation extends Description implements TableManipulationInterface
{

  // creates a new Row based on the table
  public function produce($dat_ass=[]) : Row
  {
    return new Row($this, $dat_ass);
  }

  public function restore($dat_ass) : Row
  {
    $row = new Row($this);
    $row->load($dat_ass);
    return $row;
  }

  public function insert($values=[]) : Insert
  {
    return (new Insert($this, $values))->connection($this->connection());
  }

  public function select($columns=null, $table_alias=null) : Select
	{

		$table_alias = $table_alias ?? $this->name();
    $select = (new SelectJoin($columns ?? [$table_alias.'.*'], $this, $table_alias))->connection($this->connection());
    return $select;
	}

  public function update($modifications = [], $conditions = []) : Update
  {
    return (new Update($this, $modifications, $conditions))->connection($this->connection());
  }

  public function delete($conditions=[]) : Delete
  {
    return (new Delete($this, $conditions))->connection($this->connection());
  }

}
