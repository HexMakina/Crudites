<?php

namespace HexMakina\Crudites\Table;

use \HexMakina\Crudites\Interfaces\TableManipulationInterface;
use \HexMakina\Crudites\Queries\{BaseQuery,Insert,SelectJoin,Update,Delete};

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

  public function insert($values=[]) : BaseQuery
  {
    return (new Insert($this, $values))->connection($this->connection());
  }

  public function select($columns=null, $table_alias=null) : BaseQuery
	{

		$table_alias = $table_alias ?? $this->name();
    $select = (new SelectJoin($columns ?? [$table_alias.'.*'], $this, $table_alias))->connection($this->connection());
    return $select;
	}

  public function update($modifications = [], $conditions = []) : BaseQuery
  {
    return (new Update($this, $modifications, $conditions))->connection($this->connection());
  }

  public function delete($conditions=[]) : BaseQuery
  {
    return (new Delete($this, $conditions))->connection($this->connection());
  }

}
