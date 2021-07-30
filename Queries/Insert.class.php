<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\{Crudites,CruditesException};
use \HexMakina\Crudites\Interfaces\TableManipulationInterface;

class Insert extends BaseQuery
{
	private $query_fields = [];
  private $inserted_id = null;

	public function __construct(TableManipulationInterface $table, $assoc_data = [])
	{
		if(!is_array($assoc_data) || empty($assoc_data))
			throw new \Exception('INSERT_DATA_INVALID_OR_MISSING');

    $this->table = $table;
    $this->connection = $table->connection();

		if(empty($assoc_data))
			return $this;

		$this->values($assoc_data);
	}

  // public function is_create(){		return true;}

  public function values($assoc_data)
  {
		foreach($this->table->columns() as $column_name => $column)
		{
			if($column->is_auto_incremented())
				continue;

			if(isset($assoc_data[$column_name]))
			{
				$this->query_fields[$column_name] = $column_name;
				$this->bindings[':'.$this->table_name().'_'.$column_name] = $assoc_data[$column_name];
			}
		}
  }

  public function generate() : string
	{
    if(empty($this->query_fields) || count($this->bindings) !== count($this->query_fields))
     throw new CruditesException('INSERT_FIELDS_BINDINGS_MISMATCH');

		$fields = '`'.implode('`, `', $this->query_fields).'`';
		$values = implode(', ', array_keys($this->bindings));

		return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $values);
	}

	//------------------------------------------------------------ Auto Increment Value
	public function inserted_id()
	{
		return $this->inserted_id;
  }

  public function run() : BaseQuery
  {
    parent::run();

    if($this->is_success())
      $this->inserted_id = $this->connection()->lastInsertId();

    return $this;
  }
}
