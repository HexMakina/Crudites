<?php

namespace HexMakina\Crudites\Queries;

class Describe extends BaseQuery
{
  private $table_name = null; // table objects are not a thing here.. table() return null;

  public function __construct($table_name)
  {
    $this->table_name = $table_name;
  }

  // implements BaseQuery, pretty basic stuff
  public function generate() : string
  {
    return sprintf('DESCRIBE `%s`;', $this->table_name);
  }

  // overwrites BaseQuery, bypassing null table object
  public function table_name() : string
  {
    return $this->table_name;
  }

  // overwrites BaseQuery, return description as key value pair
  public function ret($mode=null, $option=null)
  {
    return parent::ret(\PDO::FETCH_UNIQUE); // fetch by key
  }
}
