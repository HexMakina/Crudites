<?php
namespace HexMakina\Crudites\Queries;

// use \HexMakina\Crudites\{CruditesException,TableInterface,Crudites};
use \HexMakina\Crudites\Interfaces\{TableManipulationInterface};

class ClauseJoin
{
  CONST TYPES = ['INNER', 'LEFT', 'RIGHT', 'INNER', 'FULL'];

  protected $table = null;
  protected $table_alias = null;

  protected $type = null;

  protected $join_table = null;
  protected $join_table_alias = null;

  protected $selection = null;

  protected $join = null;

  public function __construct(TableManipulationInterface $table, $table_alias, $type, TableManipulationInterface $join_table, $join_table_alias)
  {
    $this->table = $table;
    $this->table_alias = $table_alias;

    $this->type = $type;

    $this->join_table = $join_table;
    $this->join_table_alias = $join_table_alias;

    if(($join = $this->has_single_foreign_key()) !== false)
      $this->join = $join;
    // elseif(($join = ) !== false)
    //   $this->join = $join;
  }

  public function has_single_foreign_key()
  {
    $bond = $this->$table->foreign_keys_by_table()[$join_table->name()] ?? null;
    if(count($bond) === 1)
      return [$bond->table_name(), $bond->name(), $this->join_table_alias, $bond->foreign_column_name()];

    return false;
  }
}
