<?php

namespace HexMakina\Crudites;

class TightModelSelector
{

  private $model;
  private $model_class;
  private $model_table;
  private $statement;

  public function __construct(ModelInterface $m)
  {
    $this->model = $m;
    $this->model_class = get_class($m);
    $this->model_table = $this->model_class::table();
    $this->statement = $this->model_table->select();
  }

  public function model()
  {
    return $this->model;
  }

  public function class() : ModelInterface
  {
    return $this->class;
  }

  public function statement() : SelectInterface
  {
    return $this->statement;
  }

  public function select($filters=[], $options=[]) : SelectInterface
  {
    $this->statement()->table_alias($options['table_alias'] ?? null);

    if(!isset($options['eager']) || $options['eager'] !== false)
      $this->statement()->eager();

    if(isset($options['order_by']))
      $this->option_order_by($options['order_by'])

    if(isset($options['limit']) && is_array($options['limit'])) // TODO this doesn't need an array. limit function works it out itself
      $this->statement()->limit($options['limit'][1], $options['limit'][0]);

    $this->filter_with_fields($filters);

    if(is_subclass_of($this->model(), '\HexMakina\kadro\Models\Interfaces\EventInterface'))
    {
      $this->filter_event($filters['date_start'] ?? null, $filters['date_stop'] ?? null);
      $this->statement()->order_by([$event->event_field(), 'DESC']);
    }

    if(isset($filters['content']))
      $this->statement()->aw_filter_content($filters['content']);

    if(isset($filters['ids']))
      $this->filter_with_ids($filters['ids']);
  }

  public function option_order_by($order_by)
  {
    if(is_string($order_by))
      $this->statement()->order_by($order_by);

    elseif(is_array($order_by)) // TODO commenting required about the array situation
      foreach($options['order_by'] as $order_by)
      {
        if(!isset($order_by[2]))
          array_unshift($order_by, '');

        list($order_table, $order_field, $order_direction) = $order_by;
        $this->statement()->order_by([$order_table ?? '', $order_field, $order_direction]);
      }
  }

  public function filter_event($date_start=null, $date_stop=null)
  {
    if(!empty($date_start))
      $this->statement()->aw_gte($event->event_field(), $date_start, $this->statement()->table_label(), ':filter_date_start');

    if(!empty($date_stop))
      $this->statement()->aw_lte($event->event_field(), $date_stop, $this->statement()->table_label(), ':filter_date_stop');

    if(empty($options['order_by']))
      $this->statement()->order_by([$event->event_field(), 'DESC']);
  }

  public function filter_with_ids($ids)
  {
    if(empty($filters['ids']))
      $this->statement()->and_where('1=0'); // TODO: this is a new low.. find another way to cancel query
    else
      $this->statement()->aw_numeric_in('id', $filters['ids']);
  }

  public function filter_with_fields($filters, $filter_mode = 'aw_eq')
  {
    foreach($this->table->columns() as $column_name => $column)
    {
      if(isset($filters[$column_name]) && is_string($filters[$column_name]))
        $this->statement()->$filter_mode($column_name, $filters[$column_name]);
    }
  }
}
