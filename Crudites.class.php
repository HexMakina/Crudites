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
	static private $database = null;

	static private $databases = []; // TODO DEPRECATED KISS goodbye multi databases support

	// TODO DEPRECATED KISS goodbye multi databases support
	// public static function setInspector($database_name, DatabaseInterface $inspector)
  // {
  //   self::$databases[$database_name] = $inspector;
  // }

	public static function setDatabase(DatabaseInterface $db)
	{
		self::$database = $db;
		// self::$databases[$db->name()] = $db; // TODO DEPRECATED KISS goodbye multi databases support
	}

	public static function inspect($table_name)
	{
		if(is_null(self::$database))
			throw new CruditesException('NO_DATABASE');
		try
		{
			return self::$database->inspect($table_name);
		}
		catch(\Exception $e)
		{
			throw new CruditesException('TABLE_INTROSPECTION');
		}
	}

	// TODO DEPRECATED KISS goodbye multi databases support
	// public static function inspect($table_name, $database_name=null)
	// {
	// 	if(!isset($database_name) && !defined('DEFAULT_DATABASE'))
	// 	{
	// 		throw new CruditesException('DATABASE_NAME_UNKNOWN');
	// 	}
	//
  //   try
	// 	{
	//
	// 		$conx = self::$databases[$database_name ?? DEFAULT_DATABASE];
  //   	return $conx->inspect($table_name);
	//
	//
  //   }
	// 	catch(\Exception $e){
	// 		throw new CruditesException('TABLE_INTROSPECTION');
	// 	}
	// }

	// public static function connect($name=null, $db_host=null, $db_port=null, $db_name=null, $charset=null, $db_user=null, $db_pass=null)
	// TODO DEPRECATED KISS goodbye multi databases support
	// public static function connect($props=null, $name=null)
	// {
	// 	$database_name = $name ?? DEFAULT_DATABASE;
	//
	// 	if(!isset($props['host'],$props['port'],$props['name'],$props['char'],$props['user'],$props['pass']))
	// 	{
	// 		if(isset(self::$databases[$database_name]))
	// 			return self::$databases[$database_name]->contentConnection();
	//
	// 		throw new CruditesException('CONNECTION_MISSING');
	// 	}
	// 	// 20210729: this stored connection in database array.. so wrong..
	// 	// return (self::$databases[$database_name] = new Connection($props['host'],$props['port'],$props['name'],$props['char'],$props['user'],$props['pass']));
	// 	$conx = new Connection($props['host'],$props['port'],$props['name'],$props['char'],$props['user'],$props['pass']);;
	// 	self::$databases[$database_name] = new Database($conx);
	// 	return $conx;
	// }

	public static function connect($props=null)
	{
		// no props, means connection already exists, verify and return
		if(!isset($props['host'],$props['port'],$props['name'],$props['char'],$props['user'],$props['pass']))
		{
			if(is_null(self::$database))
				throw new CruditesException('CONNECTION_MISSING');

			return self::$database->contentConnection();
		}

		$conx = new Connection($props['host'],$props['port'],$props['name'],$props['char'],$props['user'],$props['pass']);;
		// self::$databases[$database_name] = new Database($conx);
		return $conx;
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

		$ret = [];
		try
		{
			if($Query->run()->is_success())
			{
				foreach($Query->ret_ass() as $rec)
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

	public static function raw($sql, $dat_ass=[])
	{
		$conx = self::connect();
		if(empty($dat_ass))
			$res = $conx->query($sql);
		else
		{
			$stmt = $conx->prepare($sql);
			$res = $stmt->execute($dat_ass);
		}
		return $res;
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

		try
		{
			$Query->run();
		}
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
