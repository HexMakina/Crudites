<?php
namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\{CruditesException,Crudites};
use \HexMakina\Crudites\Interfaces\TableManipulationInterface;

trait ClauseJoin
{
  protected $joined_tables = [];

  abstract public function table(TableManipulationInterface $setter = null) : TableManipulationInterface;
  abstract public function table_name();
  abstract public function table_alias($setter = null);
  abstract public function table_label($table_name=null);
  abstract public function select_also();
  abstract public function field_label($field, $table_name=null);
  abstract public function add_binding($k, $v);
  abstract public function join_raw($sql);

  public function add_tables($setter)
  {
    $this->joined_tables = array_merge($this->joined_tables, is_array($setter) ? $setter : [$setter]);
    return $this;
  }

  public function eager($table_aliases=[])
  {
    if(isset($table_aliases[$this->table_name()]))
      $this->table_alias($table_aliases[$this->table_name()]);

    foreach($this->table()->foreign_keys_by_table() as $foreign_table_name => $fk_columns)
    {
      $foreign_table = Crudites::inspect($foreign_table_name);

      $single_fk = count($fk_columns) === 1; //assumption
      foreach($fk_columns as $fk_column)
      {

        $select_also = [];

        // TODO this sucks.. 'created_by' & 'kadro_operator' have *NOTHING* to do in SelectJoin, must create mecanism for such exception
        if($fk_column->foreign_table_name() == 'kadro_operator' && $fk_column->name() == 'created_by')
        {
          continue; // dont load the log information
          // $foreign_table_alias = 'creator';
          // $select_also=['id','name'];
        }
        else
        {
          $m=[];
          if(preg_match('/(.+)_('.$fk_column->foreign_column_name().')$/', $fk_column->name(), $m))
          {
            $foreign_table_alias = $m[1];
          }
          else
          {
            $foreign_table_alias = $foreign_table_name;
          }
          $foreign_table_alias = $single_fk === true ? $foreign_table_alias : $foreign_table_alias.'_'.$fk_column->name();

          // auto select non nullable columns
        }

        if(empty($select_also))
          foreach($foreign_table->columns() as $col)
            if(!$col->is_hidden())
              $select_also []= "$col";

        $this->auto_join([$foreign_table, $foreign_table_alias], $select_also);
      }
    }
  }


  public function join($table_names, $joins, $join_type='')
  {
    list($join_table_name,$join_table_alias) = self::process_param_table_names($table_names);

    if(preg_match('/^(INNER|LEFT|RIGHT|FULL)(\sOUTER)?/i', $join_type) !== 1)
      $join_type = '';

    $this->join_raw($this->generate_join($join_type, $join_table_name, $join_table_alias, $joins));

    return $this;
  }

  public function auto_join($other_table, $select_also=[], $relation_type=null)
  {
    $other_table_alias = null;

    if(is_array($other_table))
      list($other_table, $other_table_alias) = $other_table;
    else
      $other_table_alias = $other_table->name();

    $other_table_name = $other_table->name();

    $joins = [];

    // 1. ? this->table.other_table_id -> $other_table.id
    // 2. ? this_table.id -> $other_table.this_table_id)
    // if(count($bonding_column = $this->table()->foreign_keys_by_table()[$other_table_name] ?? []) === 1)
    if(!is_null($bonding_column = $this->table()->single_foreign_key_to($other_table)))
    {
      $relation_type = $relation_type ?? $bonding_column->is_nullable() ? 'LEFT OUTER' : 'INNER';
      // $joins []= [$bonding_column->table_name(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreign_table_alias(), $bonding_column->foreign_column_name()];
      $joins []= [$this->table_alias(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreign_table_alias(), $bonding_column->foreign_column_name()];
    }
    // elseif(count($bonding_column = $other_table->foreign_keys_by_table()[$this->table()->name()] ?? []) === 1)
    elseif(!is_null($bonding_column = $other_table->single_foreign_key_to($this->table())))
    {
      // vd(__FUNCTION__.' : '.$other_table.' has fk to '.$this->table());
      $relation_type = $relation_type ?? 'LEFT OUTER';
      $joins []= [$this->table_label(), $bonding_column->foreign_column_name(), $other_table_alias ?? $other_table->name(), $bonding_column->name()];
    }
    else
    {
      $bondable_tables = $this->joinable_tables();
      if(isset($bondable_tables[$other_table_name]))
      {
        $bonding_columns = $bondable_tables[$other_table_name];
        if(count($bonding_columns) === 1)
        {
          $bonding_column = current($bonding_columns);
          $other_table_alias = $other_table_alias ?? $bonding_column->foreign_table_alias();

          $bonding_table_label = array_search($bonding_column->table_name(), $this->joined_tables());
          if($bonding_table_label === false)
            $bonding_table_label = $bonding_column->table_name();

          $joins = [[$bonding_table_label, $bonding_column->name(), $other_table_alias, $bonding_column->foreign_column_name()]];
          $relation_type = $relation_type ?? (($bonding_column->is_nullable()) ? 'LEFT OUTER' : 'INNER');
        }
      }
      elseif(count($intersections = array_intersect_key($other_table->foreign_keys_by_table(), $bondable_tables)) > 0)
      {
        $other_table_alias = $other_table_alias ?? $other_table->name();
        foreach($intersections as $table_name => $bonding_column)
        {
          if(count($bonding_column) !== 1 || count($other_table->foreign_keys_by_table()[$table_name]) !== 1)
            break;
          // vd($this->table_name() . " $other_table_name");
          // vd($bonding_column);

          $joins = [];

          $bonding_column = current($bonding_column);
          $joins []= [$other_table_alias, $bonding_column->name(), $bonding_column->foreign_table_alias(), $bonding_column->foreign_column_name()];

          // vd($other_table_alias);
          $bonding_column = current($bondable_tables[$table_name]);
          $joins []= [$bonding_column->table_name(), $bonding_column->name(), $bonding_column->foreign_table_alias(), $bonding_column->foreign_column_name()];

          // $relation_type = $relation_type ?? (($parent_column->is_nullable() || $bonding_column->is_nullable()) ? 'LEFT OUTER' : 'INNER');
          $relation_type = $relation_type ?? (($bonding_column->is_nullable()) ? 'LEFT OUTER' : 'INNER');
        }
      }
    }

    // vd($relation_type.' '.json_encode($joins));
    if(!empty($joins))
    {
      // vd('ottojoin: '.$this->table()->name().' with '.$other_table_name.' as '.$other_table_alias);
      $this->join([$other_table_name, $other_table_alias], $joins, $relation_type);
      $this->add_tables([$other_table_alias => $other_table_name]);


      // if(is_null($select_also) empty($select_also))
      //   $select_also=[$other_table_alias.'.*'];
      if(!empty($select_also))
        foreach($select_also as $select_field)
        {
          if(is_null($other_table->column("$select_field")))
            $computed_selection = "$select_field"; // table column does not exist, no nood to prefix
          else
            $computed_selection = "$other_table_alias.$select_field as ".$other_table_alias."_$select_field";

          // vd($computed_selection);
          $this->select_also($computed_selection);
        }
    }

    return $other_table_alias;
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
        $this->add_binding($bind_label, $value);

        $join_parts []= $this->field_label($field, $table) . ' = ' . $bind_label;
      }
    }
    return sprintf('%s JOIN `%s` %s ON %s', $join_type, $join_table_name, $join_table_alias, implode(' AND ', $join_parts));
  }

  private function joined_tables()
  {
    return $this->joined_tables;
  }

  private function joinable_tables() : array
  {
    $joinable_tables = $this->table()->foreign_keys_by_table();
    foreach($this->joined_tables() as $join_table)
    {
      $joinable_tables += Crudites::inspect($join_table)->foreign_keys_by_table();
    }

    return $joinable_tables;
  }

  private static function process_param_table_names($table_names) : array
  {
    if(is_array($table_names) && isset($table_names[1]))
      return $table_names;

    if(is_array($table_names) && !isset($table_names[1]))
      $table_names = current($table_names);

    if(is_string($table_names))
      return [$table_names, $table_names];

    throw new CruditesException('INVALID_PARAM_TABLE_NAMES');
  }
}
