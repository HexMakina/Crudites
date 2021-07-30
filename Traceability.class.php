<?php

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Queries\BaseQuery;

// return $Query->is_create() ? 'CRUDITES_INSTANCE_CREATED' :
//       ($Query->is_retrieve() ? 'CRUDITES_INSTANCE_RETRIEVED' :
//       ($Query->is_update() ? 'CRUDITES_INSTANCE_UPDATED' :
//       ($Query->is_delete() ? 'CRUDITES_INSTANCE_DESTROYED' : 'ERR_UNKOWN_QUERY_TYPE')));

// TODO ? move to Models\Abilities
trait Traceability // YOUR MOTHER'S A TRACER!
{
	public function traceable() : bool
	{
		return true;
	}

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
        $res = self::tracking_table()->delete($trace)->run();
      }
      return true;
    }
    catch(\Exception $e)
    {
      return false;
    }

    return false;
  }

  public function traces($sort='DESC')
  {
    $q = self::tracking_table()->select()->aw_fields_eq(['query_table' => get_class($this)::table_name(), 'query_id' => $this->get_id()])->order_by(['query_on', $sort]);
    // vd($q);
    return $q->ret_ass();
  }

  private static function tracking_table()
  {
    return Crudites::inspect('kadro_action_logger');
  }

}
