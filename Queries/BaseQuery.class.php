<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\{Connection,CruditesException};
use \HexMakina\Crudites\Interfaces\TableManipulationInterface;

abstract class BaseQuery
{
  static private $executions = 0;

  const STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"

  const CODE_CREATE =   'C';
  const CODE_RETRIEVE = 'R';
  const CODE_UPDATE =   'U';
  const CODE_DELETE =   'D';

  protected $database=null;
  protected $table=null;
  protected $statement=null;
  protected $bindings=[];

  protected $connection = null;
  protected $executed = false;
  protected $state = null;

  protected $prepared_statement = null;

  protected $row_count = null;

  protected $error_code = null;
  protected $error_text = null;
  //------------------------------------------------------------  DEBUG
  public function __debugInfo()
  {
    $dbg = [];
    if(isset($this->table))
      $dbg['table_name()'] = $this->table_name();

    $dbg = array_merge($dbg, get_object_vars($this));
    unset($dbg['table']);

    foreach($dbg as $k => $v)
      if(!isset($dbg[$k]))
        unset($dbg[$k]);

    $dbg['bindings'] = json_encode($dbg['bindings']);
    if(empty($this->bindings))
      unset($dbg['bindings']);

    $dbg['statement()'] = $this->statement();
    return $dbg;
  }

  public function __toString()
  {
    return $this->statement();
  }


  abstract public function generate() : string;

  // returns one of C, R, U, D
  // public function is_create(){    return false;}
  // public function is_retrieve(){  return false;}
  // public function is_update(){    return false;}
  // public function is_delete(){    return false;}

  // public function query_code()
  // {
  //   if($this->is_create())        return self::CODE_CREATE;
  //   elseif($this->is_retrieve())  return self::CODE_RETRIEVE;
  //   elseif($this->is_update())    return self::CODE_UPDATE;
  //   elseif($this->is_delete())    return self::CODE_DELETE;
  //
  //   throw new CruditesException('UNKOWN_QUERY_CODE');
  // }

  //------------------------------------------------------------  GET/SETTERS
  public function statement($setter = null) : string
  {
    if(!is_null($setter))
      $this->statement = $setter;

    return $this->statement ?? $this->generate();
  }


  public function connection($setter = null)
  {
    if(is_null($setter))
      return $this->connection;

    $this->connection = $setter;
    return $this;
  }

  public function has_table() : bool
  {
    return !is_null($this->table);
  }

  public function table(TableManipulationInterface $setter = null) : TableManipulationInterface
  {
    return is_null($setter) ? $this->table : ($this->table = $setter);
  }

  public function table_name()
  {
    return $this->table()->name();
  }

  //------------------------------------------------------------  PREP::FIELDS
  public function table_label($table_name=null)
  {
    return $table_name ?? $this->table_name();
  }

  public function field_label($field, $table_name=null)
  {
    if($table_name === '')
      return "`$field`";
    return sprintf('`%s`.`%s`', $this->table_label($table_name), $field);
  }

  //------------------------------------------------------------  PREP::BINDINGS

  public function bindings($setter = null)
  {
    if(is_null($setter) || !is_array($setter))
      return $this->bindings;

    $this->bindings = $setter;
    return $this;
  }

  public function bind_label($field, $table_name=null)
  {
    return ':'.$this->table_label($table_name).'_'.$field;
  }

  public function add_binding($k, $v)
  {
    $this->bindings[$k] = $v;
  }

  public function bind_name($table_name, $field, $value, $bind_label=null)
  {
    $bind_label = $bind_label ?? $this->bind_label($field, $table_name);

    $this->bindings[$bind_label] = $value;

    return $bind_label;
  }

  //------------------------------------------------------------  Run
  // throws CruditesException on failure
  // returns itself
  // DEBUG dies on \Exception

  public function run() : BaseQuery
  {
    if(is_null($this->connection()))
      throw new CruditesException('NO_CONNECTION');
    try
    {
      if(!$this->is_prepared())
        $this->prepared_statement = $this->connection()->prepare($this->statement());

      if($this->prepared_statement->execute($this->bindings()) !== false) // execute returns TRUE on success or FALSE on failure.
      {
        ++self::$executions;

        $this->is_executed(true);

        if($this->prepared_statement->errorCode() === self::STATE_SUCCESS)
        {
          $this->state = self::STATE_SUCCESS;
          // careful: https://www.php.net/manual/en/pdostatement.rowcount.php
          $this->row_count = $this->prepared_statement->rowCount();
        }
      }
    }
    catch (\PDOException $e)
    {
      throw (new CruditesException($e->getMessage()))->fromQuery($this);
    }
    // not doing anything with it.. let it blow
    // catch(\Exception $e)
    // {
    //   var_dump(get_class($e));
    //   var_dump($e);
    //   die;
    // }

    return $this;
  }

  //------------------------------------------------------------  Return
  public function ret($mode=null, $option=null)
  {
    if(!$this->is_executed())
    {
      try{$this->run();}
      catch(CruditesException $e){return false;}
    }
    if(!$this->is_success())
      return false;

    // if(is_null($mode)) // nothin was specified, it's probe-time
    // {
    //   if($this->has_table() && !is_null($class_name = $this->table()->map_class()))
    //   {
    //     $mode = \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE;
    //     $option = $class_name;
    //   }
    //   else
    //     $mode = \PDO::FETCH_ASSOC;
    // }
    return is_null($option) ? $this->prepared_statement->fetchAll($mode) : $this->prepared_statement->fetchAll($mode, $option);
  }

  //------------------------------------------------------------ Return:count
  public function count()
  {
    if(!$this->is_executed())
      $this->run();

    return $this->is_success() ? $this->row_count : null;
  }

  //------------------------------------------------------------  Status
  public function is_prepared()
  {
    return !is_null($this->prepared_statement) && false !== $this->prepared_statement;
  }

  public function is_executed($setter=null)
  {
    return is_null($setter) ? $this->executed === true : ($this->executed = $setter);
  }

  public function is_success()
  {
    return $this->state === self::STATE_SUCCESS;
  }

  public function error_info()
  {
    if($this->is_prepared())
      return $this->prepared_statement->errorInfo();

    return $this->connection->errorInfo();
  }
}
