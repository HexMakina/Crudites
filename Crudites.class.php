<?php
/**
 * Crudités, it's a cup of carrots sticks (but are they organic ?)
  	* Codd's Relational model, Unicity, Definitions, Introspection, Tests, Execution & Sets
		* Create - Retrieve - Update - Delete
 		* API for writing and running SQL queries
 */

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Queries\BaseQuery;
use \HexMakina\Crudites\Interfaces\DatabaseInterface;
use \HexMakina\Crudites\CruditesException;

class Crudites
{
	static private $databases = [];

	public static function setInspector($database_name, DatabaseInterface $inspector)
  {
    self::$databases[$database_name] = $inspector;
  }

	public static function inspect($table_name, $database_name=null)
	{
    try{
			$conx = self::$databases[$database_name ?? DEFAULT_DATABASE];
    	return $conx->inspect($table_name);
    }
		catch(\Exception $e){
			throw new CruditesException('TABLE_INTROSPECTION');
		}
	}

	public static function connect($name=null, $db_host=null, $db_port=null, $db_name=null, $charset=null, $db_user=null, $db_pass=null)
	{
		$selected_database = $name ?? DEFAULT_DATABASE;

		if(is_null($db_host) || is_null($db_port) || is_null($db_name) || is_null($charset) || is_null($db_user) || is_null($db_pass))
		{
			if(isset(self::$databases[$selected_database]))
				return self::$databases[$selected_database]->contentConnection();

			throw new CruditesException('CONNECTION_MISSING');
		}

		return (self::$databases[$name] = new Connection($db_host,$db_port,$db_name,$charset,$db_user,$db_pass));

	}

	//------------------------------------------------------------  DataRetrieval
	// success: return AIPK-indexed array of results (associative array or object)
	public static function count(BaseQuery $Query)
	{
		$Query->select_also(['COUNT(*) as count']);
		return intval(current($Query->ret_col()));
  }

	// success: return AIPK-indexed array of results (associative array or object)
	public static function retrieve(BaseQuery $Query) : array
	{
		$pk_name = implode('_', array_keys($Query->table()->primary_keys()));

    dd($pk_name);
		$ret = [];
		try
		{
			if($Query->run()->is_success())
			{
				foreach($res->ret_ass() as $rec)
					$ret[$rec[$pk_name]] = $rec;
			}
		}
		catch(CruditesException $e)
		{
			vdt($e->getMessage());
			return [];
		}

		return $ret;
	}

	public static function distinct_for($table, $column_name, $filter_by_value=null)
	{
		$table = self::table_name_to_Table($table);

		if(is_null($table->column($column_name)))
			throw new CruditesException('TABLE_REQUIRES_COLUMN');

		$Query = $table->select(["DISTINCT `$column_name`"])->aw_not_empty($column_name)->order_by([$table->name(), $column_name, 'ASC']);

		if(!is_null($filter_by_value))
			$Query->aw_like($column_name, "%$filter_by_value%");

    $Query->order_by($column_name, 'DESC');
		// ddt($Query);
		return $Query->ret_col();
	}

	public static function distinct_for_with_id($table, $column_name, $filter_by_value=null)
	{
		$table = self::table_name_to_Table($table);

		if(is_null($table->column($column_name)))
			throw new CruditesException('TABLE_REQUIRES_COLUMN');

		$Query = $table->select(["DISTINCT `id`,`$column_name`"])->aw_not_empty($column_name)->order_by([$table->name(), $column_name, 'ASC']);

		if(!is_null($filter_by_value))
			$Query->aw_like($column_name, "%$filter_by_value%");

		return $Query->ret_par();
	}

	//------------------------------------------------------------  DataManipulation Helpers
	// returns true on success, false on failure
	// does NOT return the value of the toggled boolean
	public static function toggle_boolean($table, $boolean_column_name, $id) : bool
	{

		$table = self::table_name_to_Table($table);

		if(is_null($column = $table->column($boolean_column_name)) || !$column->is_boolean())
			return false;

		// TODO: still using 'id' instead of table->primaries
		$Query = $table->update();
    $Query->statement("UPDATE ".$table->name()." SET $boolean_column_name = !$boolean_column_name WHERE id=:id");
    $Query->bindings([':id' => $id]);

		try{$Query->run();}
		catch(CruditesException $e)
		{
			vdt($e->getMessage());
			return false;
		}

		return $Query->is_success();
	}

	private static function table_name_to_Table($table)
	{
		return is_string($table) ? self::inspect($table) : $table;
	}



}
?>
