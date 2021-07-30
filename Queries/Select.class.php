<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\Interfaces\{TableManipulationInterface};

class Select extends BaseQueryWhere
{
  protected $selection = [];
  protected $table_alias = null;
  protected $join = [];
  protected $joined_tables = [];
  protected $group = [];
  protected $having = [];
  protected $order = [];

  protected $limit = null;
  protected $limit_number = null;
  protected $limit_offset = 0;

  // public function fully_qualified_column_name($label, $table_alias=null)
  // {
  //   // TODO analyse $label format to create a FQName
  //   // TODO use function in constructor and select_also()
  //   return $label;
  // }

  public function __construct($select_fields=null, TableManipulationInterface $table=null, $table_alias=null)
  {
    $this->table = $table;
    $this->table_alias = $table_alias;

    if(is_null($select_fields))
      $this->selection = ['*'];
    elseif(is_string($select_fields))
      $this->selection = explode(',', $select_fields);
    elseif(is_array($select_fields))
      $this->selection = $select_fields;
  }

  // public function is_retrieve()
  // {
  //   return true;
  // }

  public function table_label($table_name=null)
  {
    return $table_name ?? $this->table_alias ?? $this->table_name();
  }

  public function columns($setter = null)
  {
    if(is_null($setter))
      return $this->selection;

    if(is_array($setter))
      $this->selection = $setter;

    return $this;
  }

  public function select_also($setter)
  {
    $this->selection = array_merge($this->selection, is_array($setter) ? $setter : [$setter]);
    return $this;
  }

  public function select_less($column_alias)
  {

    foreach($column_alias as $alias)
    {
      foreach($this->selection as $i => $column_and_alias)
      {
        if(strpos($column_and_alias, $alias) === 0)
        {
          unset($this->selection[$i]);
          break;
        }
      }
    }
    return $this;
  }

  public function add_tables($setter)
  {
    $this->joined_tables = array_merge($this->joined_tables, is_array($setter) ? $setter : [$setter]);
    return $this;
  }

  private function add_part($group, $part)
  {
    $this->$group = $this->$group ?? [];
    array_push($this->$group, $part);
    return $this;
  }

  public function join_raw($sql)
  {
    return $this->add_part('join', $sql);
  }

  public function table_alias($setter = null)
  {
    if(!is_null($setter))
      $this->table_alias = $setter;

    return $this->table_alias ?? $this->table_name();
  }

  public function group_by($clause)
  {
    if(is_string($clause))
    {
      $this->add_part('group', $this->field_label($clause, $this->table_label()));
    }
    elseif(is_array($clause))
    {
      if(isset($clause[1])) // 0: table, 1: field
        $this->add_part('group', $this->field_label($clause[1], $clause[0]));
      else // 0: field
        $this->add_part('group', $this->field_label($clause[0], null));
    }

    return $this;
  }

  public function having($condition)
  {
    return $this->add_part('having', $condition);
  }

  public function order_by($clause)
  {
    if(is_string($clause))
    {
      $this->add_part('order', $clause);
    }
    elseif(is_array($clause) && count($clause) > 1)
    {
      if(isset($clause[2])) // 0:table, 1:field, 2:direction
        $this->add_part('order', sprintf('%s %s', $this->field_label($clause[1], $clause[0]), $clause[2]));
      elseif(isset($clause[1])) // 0: field, 1: direction
        $this->add_part('order', sprintf('%s %s', $this->field_label($clause[0], $this->table_label()), $clause[1]));
    }

    return $this;
  }

  public function limit($number, $offset=null)
  {
    $this->limit_number = $number;
    $this->limit_offset = $offset;

    return $this;
  }

  public function generate() : string
  {
    if(is_null($this->table))
      throw new CruditesException('NO_TABLE');

    $this->table_alias = $this->table_alias ?? '';

    $query_fields = empty($this->selection) ? ['*'] : $this->selection;

    $ret = PHP_EOL . 'SELECT '.implode(', '.PHP_EOL, $query_fields);
    $ret.= PHP_EOL . sprintf(' FROM `%s` %s ', $this->table_name(), $this->table_alias);

    if(!empty($this->join))
      $ret.= PHP_EOL . ' '.implode(PHP_EOL.' ', $this->join);

    $ret .= $this->generate_where();

    foreach(['group' => 'GROUP BY', 'having' => 'HAVING', 'order' => 'ORDER BY'] as $part => $prefix)
      if(!empty($this->$part))
        $ret.= PHP_EOL . " $prefix " . implode(', ', $this->$part);

    if(!empty($this->limit_number))
    {
      $offset = $this->limit_offset ?? 0;
      $number = $this->limit_number;
      $ret.= PHP_EOL . " LIMIT $offset, $number";
    }

    return $ret;
  }

  protected function generate_join($join_type, $join_table_name, $join_table_alias=null, $join_fields)
  {
    $join_table_alias = $join_table_alias ?? $join_table_name;

    $join_parts = [];
    foreach($join_fields as $join_cond)
    {
      if(isset($join_cond[3])) // 4 joins param -> t.f = t.f
      {
        list($table, $field, $join_table, $join_table_field) = $join_cond;
        $join_parts []= $this->field_label($field, $table) . ' = ' . $this->field_label($join_table_field, $join_table);
      }
      elseif(isset($join_cond[2])) // 3 joins param -> t.f = v
      {
        list($table, $field, $value) = $join_cond;
        $bind_label = ':loj_'.$join_table_alias.'_'.$table.'_'.$field;
        $this->bindings[$bind_label] = $value;

        $join_parts []= $this->field_label($field, $table) . ' = ' . $bind_label;
      }
    }
    return sprintf('%s JOIN `%s` %s ON %s', $join_type, $join_table_name, $join_table_alias, implode(' AND ', $join_parts));
  }

  //------------------------------------------------------------ SELECT:FETCHING RESULT

  public function ret_obj($c=null)
  {
    return is_null($c) ? $this->ret(\PDO::FETCH_OBJ) : $this->ret(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $c);
  }

  public function ret_num(){    return $this->ret(\PDO::FETCH_NUM);} //ret:
  public function ret_ass(){    return $this->ret(\PDO::FETCH_ASSOC);} //ret: array indexed by column name
  public function ret_col(){    return $this->ret(\PDO::FETCH_COLUMN);} //ret: all values of a single column from the result set
  public function ret_par(){    return $this->ret(\PDO::FETCH_KEY_PAIR);}
  public function ret_key(){    return $this->ret(\PDO::FETCH_UNIQUE);}
}
