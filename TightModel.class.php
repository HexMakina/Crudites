<?php

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Interfaces\{TableManipulationInterface,ModelInterface,TraceableInterface,SelectInterface};

abstract class TightModel extends TableToModel implements ModelInterface, TraceableInterface
{

  use TraitIntrospector;

  public function __toString(){ return static::class_short_name().' #'.$this->get_id();}

  public function traceable() : bool
  {
    return true;
  }

  public function traces() : array
  {
    return [];
  }

  public function immortal() : bool
  {
    return self::IMMORTAL_BY_DEFAULT;
  }

  public function extract(ModelInterface $extract_model, $ignore_nullable=false)
  {
    $extraction_class = get_class($extract_model);

    $extraction_table = $extraction_class::table();
    foreach($extraction_table->columns() as $column_name => $column)
    {
      $probe_name = $extraction_class::table_alias().'_'.$column_name;

      if(!is_null($probe_res = $this->get($probe_name)))
        $extract_model->set($column_name, $probe_res);
      elseif(!$column->is_nullable() && $ignore_nullable===false)
        return null;
    }

    return $extract_model;
  }

  public function copy()
  {
    $class = get_called_class();
    $clone = new $class();

    foreach($class::table()->columns() as $column_name => $column)
    {
      if(!is_null($column->default()))
        continue;
      if($column->is_auto_incremented())
        continue;

      $clone->set($column_name, $this->get($column_name));
    }

    unset($clone->created_by);
    return $clone;
  }

  public function toggle($prop_name)
  {
    parent::toggle_boolean(static::table(), $prop_name, $this->get_id());
  }


  public function validate() : array
  {
    return []; // no errors
  }

  public function before_save() : array {return [];}

  public function after_save(){

    return true;
  }

  // return array of errors
  public function save($operator_id, $tracer=null) : ?array
  {
    try
    {
      if(!empty($errors=$this->search_and_execute_trait_methods('before_save')))
        return $errors;

      if(!empty($errors=$this->before_save()))
        return $errors;

      if(!empty($errors=$this->validate())) // Model level validation
        return $errors;

      //1 tight model *always* match a single table row
      $table_row = $this->to_table_row($operator_id);


      if($table_row->is_altered()) // someting to save ?
      {
        if(!empty($validation_errors=$table_row->persist())) // validate and persist
        {
          $errors = [];
          foreach($validation_errors as $column_name => $err)
            $errors[sprintf('MODEL_%s_FIELD_%s', static::model_type(), $column_name)] = 'CRUDITES_'.$err;

          return $errors;
        }

        // reload row
        $table_row = static::table()->restore($table_row->export());

        // update model
        $this->import($table_row->export());
      }

      if(!is_null($tracer) && $this->traceable())
        $tracer->trace($table_row->last_alter_query(), $operator_id, $this->get_id());

      $this->search_and_execute_trait_methods('after_save');
      $this->after_save();
    }
    catch (\Exception $e)
    {
      return [$e->getMessage()];
    }

    return [];
  }

  // returns false on failure or last executed delete query
  public function before_destroy() : bool
  {
    if($this->is_new() || $this->immortal())
      return false;

    $this->search_and_execute_trait_methods(__FUNCTION__);

    return true;
  }

  public function after_destroy()
  {
    $this->search_and_execute_trait_methods(__FUNCTION__);
  }

  public function destroy($operator_id, $tracer=null) : bool
  {
    if($this->before_destroy() === false)
      return false;

    $table_row = static::table()->restore(get_object_vars($this));

    if($table_row->wipe() === false)
      return false;

    if(!is_null($tracer) && $this->traceable())
      $tracer->trace($table_row->last_query(), $operator_id, $this->get_id());

    $this->after_destroy();

    return true;
  }

  //------------------------------------------------------------  Data Retrieval
  public static function query_retrieve($filters=[], $options=[]) : SelectInterface
  {
    $class = get_called_class();
    $table = $class::table();

    $Query = $table->select(null, $class::table_alias());


    if(!isset($options['eager']) || $options['eager'] !== false)
    {
      $Query->eager();
    }


    foreach($table->columns() as $column_name => $column)
    {
      if(isset($filters[$column_name]) && is_string($filters[$column_name]))
        $Query->aw_eq($column_name, $filters[$column_name]);
    }

    if(is_subclass_of($event = new $class(), '\HexMakina\kadro\Models\Interfaces\EventInterface'))
    {
      if(!empty($filters['date_start']))
        $Query->aw_gte($event->event_field(), $filters['date_start'], $Query->table_label(), ':filter_date_start');

      if(!empty($filters['date_stop']))
        $Query->aw_lte($event->event_field(), $filters['date_stop'], $Query->table_label(), ':filter_date_stop');

      if(empty($options['order_by']))
        $Query->order_by([$event->event_field(), 'DESC']);
    }

    if(isset($filters['content'])) $Query->aw_filter_content($filters['content']);

    if(isset($filters['ids']))
    {
      if(empty($filters['ids']))
        $Query->and_where('1=0'); // TODO: this is a new low.. find another way to cancel query
      else $Query->aw_numeric_in('id', $filters['ids']);
    }

    if(isset($options['order_by'])) // TODO commenting required about the array situation
    {
      $order_by = $options['order_by'];

      if(is_string($order_by))
        $Query->order_by($order_by);

      elseif(is_array($order_by))
        foreach($options['order_by'] as $order_by)
        {
          if(!isset($order_by[2]))
            array_unshift($order_by, '');

          list($order_table, $order_field, $order_direction) = $order_by;
          $Query->order_by([$order_table ?? '', $order_field, $order_direction]);
        }
    }

    if(isset($options['limit']) && is_array($options['limit']))
      $Query->limit($options['limit'][1], $options['limit'][0]);

    return $Query;
  }

  public static function exists($arg1, $arg2=null)
  {
    try{
      return self::one($arg1, $arg2);
    }
    catch(CruditesException $e){
      return null;
    }
  }

  /* USAGE
   * one($primary_key_value)
   * one($unique_column, $value)
   */
  public static function one($arg1, $arg2=null)
  {
    $mixed_info = is_null($arg2)? $arg1 : [$arg1=>$arg2];

    $unique_identifiers = get_called_class()::table()->match_uniqueness($mixed_info);

    if(empty($unique_identifiers))
      throw new CruditesException('UNIQUE_IDENTIFIER_NOT_FOUND');

    $Query = static::query_retrieve([], ['eager' => true])->aw_fields_eq($unique_identifiers);
    switch(count($res = static::retrieve($Query)))
    {
      case 0: throw new CruditesException('INSTANCE_NOT_FOUND');
      case 1: return current($res);
      default: throw new CruditesException('SINGULAR_INSTANCE_ERROR');
    }
  }

  public static function any($field_exact_values, $options=[])
  {
    $Query = static::query_retrieve([], $options)->aw_fields_eq($field_exact_values);
    return static::retrieve($Query);
  }

  public static function filter($filters = [], $options = []) : array
  {
    return static::retrieve(static::query_retrieve($filters, $options));
  }

  public static function listing($filters = [], $options = []) : array
  {
    return static::retrieve(static::query_retrieve($filters, $options)); // listing as arrays for templates
  }

  // success: return PK-indexed array of results (associative array or object)
  public static function retrieve(SelectInterface $Query) : array
  {
    $ret = [];
    $pk_name = implode('_', array_keys($Query->table()->primary_keys()));

    if(count($pks = $Query->table()->primary_keys())>1)
    {
      $concat_pk = sprintf('CONCAT(%s) as %s', implode(',',$pks),$pk_name);
      $Query->select_also([$concat_pk]);
    }

    try{
      $Query->run();
    }
    catch(CruditesException $e)
    {
      return [];
    }

    if($Query->is_success())
      foreach($Query->ret_obj(get_called_class()) as $rec)
      {
        $ret[$rec->get($pk_name)] = $rec;
      }

    return $ret;
  }

  public static function get_many_by_AIPK($aipk_values)
  {
    if(!empty($aipk_values) && !is_null($AIPK = static::table()->auto_incremented_primary_key()))
      return static::retrieve(static::table()->select()->aw_numeric_in($AIPK, $aipk_values));

    return null;
  }



  //------------------------------------------------------------  Data Relation
  // returns true on success, error message on failure
  public function set_many($linked_models, $join_info)
  {
    $ids = [];
    if($first = current($linked_models))
    {
      $id_name = $first->get_id('name');
      foreach($linked_models as $m)
        $ids[]=$m->get($id_name); // TODO change this to get_primary(null|'name'|'value')
    }
    return $this->set_many_by_ids($ids, $join_info);
  }

  // returns true on success, error message on failure
  public function set_many_by_ids($linked_ids, $join_info)
  {
    $j_table = self::inspect($join_info['t']);
    $j_table_key = $join_info['k'];

    if(empty($j_table) || empty($j_table_key))
      throw new \Exception('ERR_JOIN_INFO');

    $model_type = get_class($this)::model_type();

    $assoc_data = ['model_id' => $this->get_id(), 'model_type' => $model_type];

    $transaction = $j_table->connection();
    $transaction->transact();
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
      $transaction->commit();
    }
    catch(\Exception $e)
    {
      $transaction->rollback();
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


  //------------------------------------------------------------  Introspection & Data Validation
  /** Cascade of table name guessing goes:
   * 1. Constant 'TABLE_ALIAS' defined in class
   * 2. lower-case class name
   * @throws CruditesException, if ever called from Crudites class, must be inherited call
   */
  public static function table_alias() : string
  {
    return defined(get_called_class().'::TABLE_ALIAS') ? static::TABLE_ALIAS : static::model_type();
  }

  public static function class_short_name()
  {
    return (new \ReflectionClass(get_called_class()))->getShortName();
  }

  public static function model_type() : string
  {
    return strtolower(self::class_short_name());
  }

  public static function select_also()
  {
    return ['*'];
  }
}
