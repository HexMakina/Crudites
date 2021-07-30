<?php

namespace HexMakina\Crudites\Interfaces;

use \HexMakina\Crudites\Queries\{BaseQuery};
use \HexMakina\Crudites\Table\Row;

interface TableManipulationInterface extends TableDescriptionInterface
{
  // fetch or instanciate new Table\Rows
  public function produce($dat_ass=[]) : Row;
  public function restore($dat_ass) : Row;

  // query generators
  public function insert($values=[]) : BaseQuery;
  public function select($columns=null, $table_alias=null) : BaseQuery;
  public function update($modifications = [], $conditions = []) : BaseQuery;
  public function delete($conditions=[]) : BaseQuery;

}
