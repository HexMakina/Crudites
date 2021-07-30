<?php

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Queries\BaseQuery;

// TODO ? move to Models\Abilities
trait Traceability // YOUR MOTHER IS A TRACER!
{
  public function traceable() : bool
  {
    return true;
  }

  abstract public function get_id();
  abstract public static function table_name();

  public function track($query_code, int $query_by) : bool
  {
    if(!$this->traceable())
      return true;

    $trace = [];
    $trace['query_type'] = $query_code;
    $trace['query_table'] = get_class($this)::table_name();
    $trace['query_id'] = $this->get_id();
    $trace['query_by'] = $query_by;

    // TODO transactions
    try{
      $res = self::tracking_table()->insert($trace)->run();
      if($res->is_success() && $query_code === BaseQuery::CODE_DELETE) // removes all traces of update
      {
        $trace['query_type'] = BaseQuery::CODE_UPDATE;
        unset($trace['query_by']);
        self::tracking_table()->delete($trace)->run();
      }
      return true;
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  public function traces($sort='DESC')
  {
    $q = self::tracking_table()->select()->aw_fields_eq(['query_table' => get_class($this)::table_name(), 'query_id' => $this->get_id()])->order_by(['query_on', $sort]);
    return $q->ret_ass();
  }

  private static function tracking_table()
  {
    return Crudites::inspect('kadro_action_logger');
  }
}
