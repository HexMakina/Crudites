<?php
/*
 * Tracer
 *
 */

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Interfaces\{TracerInterface, TableManipulationInterface, QueryInterface};

class Tracer implements TracerInterface
{
  private static $query_codes = [
    'insert' => self::CODE_CREATE,
    'select' => self::CODE_SELECT,
    'update' => self::CODE_UPDATE,
    'delete' => self::CODE_DELETE
  ];

  private $tracing_table = null;

  public function __construct(TableManipulationInterface $tracing_table)
  {
    $this->tracing_table = $tracing_table;
  }

  public function tracing_table() : TableManipulationInterface
  {
    return $this->tracing_table;
  }

  public function query_code($sql_statement) : string
  {
    $first_five = strtolower(substr($sql_statement, 0,6));

    if(!isset(self::$query_codes[$first_five]))
      throw new \Exception('KADRO_ONLY_TRACES_CRUD');

    return self::$query_codes[$first_five];
  }

  public function trace(QueryInterface $q, $operator_id, $model_id) : bool
  {
    $trace = [];
    $trace['query_type'] = $this->query_code($q->statement());
    $trace['query_table'] = $q->table_name();
    $trace['query_id'] = $model_id;
    $trace['query_by'] = $operator_id;

    try{
      $this->tracing_table()->connection()->transact();
      $this->tracing_table()->insert($trace)->run();

      $res = self::tracking_table()->insert($trace)->run();
      // if we delete a record, we remove all traces of update
      if($res->is_success() && $trace['query_type'] === self::CODE_DELETE)
      {
        $trace['query_type'] = self::CODE_UPDATE;
        unset($trace['query_by']);
        $this->tracing_table()->delete($trace)->run();
      }
      $this->tracing_table()->connection()->commit();
      return true;
    }
    catch(\Exception $e)
    {
      $this->tracing_table()->connection()->rollback();
      return false;
    }
  }

  // ----------------------------------------------------------- CRUD Tracking:get for one model
  public function history($table_name, $table_pk, $sort='DESC') : array
  {
    $res = [];
  //   $table_alias = 'logladdy';
  //   $q = $this->tracing_table()->select(["$table_alias.*", 'name'], $table_alias);
  //   $q->join([User::table_name(), 'u'], [[$table_alias,'query_by', 'u','id']], 'INNER');
  //   $q->aw_fields_eq(['query_table' => $table, 'query_id' => $id], $table_alias);
  //
  //   $q->order_by(['query_on', $sort]);
  //   $q->run();
  //   $res = $q->ret_ass();
  //
    return $res;
  }

  // ----------------------------------------------------------- CRUD Tracking:get for many models
  public function traces($options=[])
  {

    if(!isset($options['limit']) || empty($options['limit']))
      $limit = 1000;
    else
      $limit = intval($options['limit']);

    // TODO SELECT field order can't change without adapting the result parsing code (foreach $res)
    $select_fields = ['SUBSTR(query_on, 1, 10) AS working_day', 'query_table', 'query_id',  'GROUP_CONCAT(DISTINCT query_type, "-", query_by) as action_by'];
    $q = $this->tracing_table()->select($select_fields);
    $q->order_by(['', 'working_day', 'DESC']);
    $q->order_by([$this->tracing_table()->name(), 'query_table', 'DESC']);
    $q->order_by([$this->tracing_table()->name(), 'query_id', 'DESC']);

    $q->group_by('working_day');
    $q->group_by('query_table');
    $q->group_by('query_id');
    $q->having("action_by NOT LIKE '%D%'");
    $q->limit($limit);

    foreach($options as $o => $v)
    {
          if(preg_match('/id/', $o))                    $q->aw_eq('query_id', $v);
      elseif(preg_match('/tables/', $o))                $q->aw_string_in('query_table', is_array($v) ? $v : [$v]);
      elseif(preg_match('/table/', $o))                 $q->aw_eq('query_table', $v);
      elseif(preg_match('/(type|action)/', $o))         $q->aw_string_in('query_type', is_array($v) ? $v : [$v]);
      elseif(preg_match('/(date|query_on)/', $o))       $q->aw_like('query_on', "$v%");
      elseif(preg_match('/(oper|user|query_by)/', $o))  $q->aw_eq('query_by', $v);
    }

    try{
      $q->run();
    }
    catch(\Exception $e)
    {
      return false;
    }

    $res = $q->ret_num(); // ret num to list()
    // ddt($res);
    $ret = [];

    foreach($res as $r)
    {
      list($working_day, $class, $instance_id, $logs) = $r;

      if(!isset($ret[$working_day]))
        $ret[$working_day] = [];
      if(!isset($ret[$working_day][$class]))
        $ret[$working_day][$class] = [];

      $ret[$working_day][$class][$instance_id] = $logs;
    }
    return $ret;
  }
}
