<?php

namespace HexMakina\Crudites;

use \HexMakina\Crudites\CruditesException;
use \HexMakina\Crudites\Interfaces\ModelInterface;

trait RelationManyToMany
{
  abstract public static function model_type() : string;

  public function has_models() : bool
  {
    $Query = static::inspect(static::otm('t'))->select([static::model_type().'_id'])->aw_eq(static::otm('k'), $this->get_id());
    return static::count($Query) > 0;
  }

  //------------------------------------------------------------  Data Relation
  // returns true on success, error message on failure
  public static function set_many($linked_models, ModelInterface $m)
  {

    $ids = [];
    if($first = current($linked_models))
    {
      $id_name = $first->get_id('name');
      foreach($linked_models as $m)
        $ids[]=$m->get($id_name); // TODO change this to get_primary(null|'name'|'value')
    }
    return static::set_many_by_ids($ids, $m);
  }

  // returns true on success, error message on failure
  public static function set_many_by_ids($linked_ids, ModelInterface $m)
  {
    $join_info = static::otm();

    $j_table = static::inspect($join_info['t']);
    $j_table_key = $join_info['k'];

    if(empty($j_table) || empty($j_table_key))
      throw new \CruditesException('ERR_JOIN_INFO');

    $assoc_data = ['model_id' => $m->get_id(), 'model_type' => get_class($m)::model_type()];

    $j_table->connection()->transact();
    try
    {
      $res = $j_table->delete($assoc_data)->run();
      if(!$res->is_success())
        throw new CruditesException('QUERY_FAILED');

      if(!empty($linked_ids))
      {
        $join_data = $assoc_data;

        $Query = $j_table->insert($join_data);

        foreach($linked_ids as $linked_id)
        {
          $Query->values([$j_table_key => $linked_id]);
          $res = $Query->run();

          if(!$res->is_success())
            throw new CruditesException('QUERY_FAILED');
        }
      }
      $j_table->connection()->commit();

    }
    catch(\Exception $e)
    {
      $j_table->connection()->rollback();
      return $e->getMessage();
    }
    return true;
  }

  public static function otm($k=null)
  {
    $type = static::model_type();
    $d = ['t' => $type.'s_models', 'k' => $type.'_id', 'a' => $type.'s_otm'];
    return is_null($k) ? $d : $d[$k];
  }

}
