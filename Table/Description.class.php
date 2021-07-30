<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Crudites\{CruditesException};
use HexMakina\Crudites\Interfaces\{ConnectionInterface,TableDescriptionInterface,TableColumnInterface};

class Description implements TableDescriptionInterface
{
  protected $connection = null;

  protected $name = null;
  // protected $ORM_class_name = null;

  protected $columns = [];

  protected $auto_incremented_primary_key = null;

  protected $primary_keys = [];
  protected $foreign_keys_by_name = [];
  protected $foreign_keys_by_table = [];
  protected $unique_keys = [];

  public function __construct($table_name, ConnectionInterface $c)
  {
    $this->name = $table_name;
    $this->connection = $c;
  }

  public function __toString()
  {
    return $this->name;
  }

  public function connection() : ConnectionInterface
  {
    return $this->connection;
  }


  public function add_column(TableColumnInterface $column)
  {
    $this->columns[$column->name()] = $column;

    if($column->is_primary())
    {
      $this->add_primary_key($column);
      if($column->is_auto_incremented())
        $this->auto_incremented_primary_key($column);
    }
  }

  public function add_primary_key(TableColumnInterface $column)
  {
    $this->primary_keys[$column->name()] = $column;
  }

  public function add_unique_key($constraint_name, $columns)
  {
    if(!isset($this->unique_keys[$constraint_name]))
      $this->unique_keys[$constraint_name] = $columns;
  }

  public function add_foreign_key(TableColumnInterface $column)
  {
    $this->foreign_keys_by_table[$column->foreign_table_name()] = $this->foreign_keys_by_table[$column->foreign_table_name()] ?? [];
    $this->foreign_keys_by_table[$column->foreign_table_name()] []= $column;

    $this->foreign_keys_by_name[$column->name()] = $column;
  }

  //getsetter of AIPK, default get is null, cant set to null
  public function auto_incremented_primary_key(TableColumnInterface $setter = null) : ?TableColumnInterface
  {
    return is_null($setter) ? $this->auto_incremented_primary_key : ($this->auto_incremented_primary_key = $setter);
  }

  //------------------------------------------------------------  getters
  // TableDescriptionInterface implementation
  public function name() : string
  {
    return $this->name;
  }

  // TableDescriptionInterface implementation
  public function columns() : array
  {
    return $this->columns;
  }

  // TableDescriptionInterface implementation
  public function column($name) : ?TableColumnInterface
  {
    return $this->columns[$name] ?? null;
  }

  // TableDescriptionInterface implementation
  public function unique_keys_by_name() : array
  {
    return $this->unique_keys;
  }

  // TableDescriptionInterface implementation
  public function primary_keys($with_values=null) : array
  {
    if(is_null($with_values))
      return $this->primary_keys;

    if(!is_array($with_values) && count($this->primary_keys) === 1)
      $with_values = [current($this->primary_keys)->name() => $with_values];

    $valid_dat_ass = [];
    foreach($this->primary_keys as $pk_name => $pk_field)
    {
      if(!isset($with_values[$pk_name]) && !$pk_field->is_nullable())
        return [];

      $valid_dat_ass[$pk_name] = $with_values[$pk_name];
    }
    return $valid_dat_ass;
  }

  public function match_uniqueness($dat_ass) : array
  {
    if(!is_array($dat_ass)) // aipk value
      return $this->primary_keys_match($dat_ass);

    if(!empty($ret = $this->primary_keys_match($dat_ass)))
      return $ret;

    if(!empty($ret = $this->unique_keys_match($dat_ass)))
      return $ret;

    return [];
  }

  /*
   * @return empty array on mismatch
   * @return assoc array of column_name => $value on match
   * @throws CruditesException if no pk defined
   */

  public function primary_keys_match($dat_ass) : array
  {
    if(count($this->primary_keys()) === 0)
      throw new CruditesException('NO_PRIMARY_KEYS_DEFINED');

    if(!is_array($dat_ass) && count($this->primary_keys()) === 1)
      $dat_ass = [current($this->primary_keys())->name() => $dat_ass];

    $match_dat_ass = [];
    foreach($this->primary_keys as $pk_name => $pk_field)
    {
      // empty ensures non existing keys, null and empty values
      if(empty($dat_ass[$pk_name]) && !$pk_field->is_nullable())
        return [];

      $valid_dat_ass[$pk_name] = $dat_ass[$pk_name] ?? null;
    }

    return $valid_dat_ass;
  }

  public function unique_keys_match($dat_ass) : array
  {
    if(count($this->unique_keys_by_name()) === 0 || !is_array($dat_ass))
      return [];

		$keys = array_keys($dat_ass);

		foreach($this->unique_keys_by_name() as $constraint_name => $column_names)
		{
			if(!is_array($column_names))
				$column_names = [$column_names];

			if(empty(array_diff($keys, $column_names)))
        return $dat_ass;
		}
    return [];
  }

  // TableDescriptionInterface implementation
  public function foreign_keys_by_name() : array
  {
    return is_array($this->foreign_keys_by_name)? $this->foreign_keys_by_name : [];
  }

  // TableDescriptionInterface implementation
  public function foreign_keys_by_table() : array
  {
    return is_array($this->foreign_keys_by_table)? $this->foreign_keys_by_table : [];
  }

  public function single_foreign_key_to($other_table)
  {
    $bonding_column_candidates = $this->foreign_keys_by_table()[$other_table->name()] ?? [];

    if(count($bonding_column_candidates) === 1)
      return current($bonding_column_candidates);

    return null;
  }

  // TableDescriptionInterface implementation
  // public function map_class($setter=null)
  // {
  //   return is_null($setter) ? $this->ORM_class_name : ($this->ORM_class_name = $setter);
  // }
}
