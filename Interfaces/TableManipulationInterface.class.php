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
  public function insert($values=[]) : Insert;
  public function select($columns=null, $table_alias=null) : BaseQueryWhere;
  public function update($modifications = [], $conditions = []) : Update;
  public function delete($conditions=[]) : Delete;

}
