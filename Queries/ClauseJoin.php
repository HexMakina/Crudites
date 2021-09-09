<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\Crudites;
use HexMakina\BlackBox\Database\TableManipulationInterface;

trait ClauseJoin
{
    protected $joined_tables = [];

    abstract public function table(TableManipulationInterface $setter = null): TableManipulationInterface;
    abstract public function tableName();
    abstract public function tableAlias($setter = null);
    abstract public function tableLabel($table_name = null);
    abstract public function selectAlso($setter);
    abstract public function backTick($field, $table_name = null);
    abstract public function addBinding($k, $v);
    abstract public function join_raw($sql);

    public function add_tables($setter)
    {
        $this->joined_tables = array_merge($this->joined_tables, is_array($setter) ? $setter : [$setter]);
        return $this;
    }

    public function eager($table_aliases = [])
    {
        if (isset($table_aliases[$this->tableName()])) {
            $this->tableAlias($table_aliases[$this->tableName()]);
        }

        foreach ($this->table()->foreignKeysByTable() as $foreign_table_name => $fk_columns) {
            $foreign_table = Crudites::inspect($foreign_table_name);

            $single_fk = count($fk_columns) === 1; //assumption
            foreach ($fk_columns as $fk_column) {
                $select_also = [];

                // TODO this sucks hard.. 'created_by' & 'kadro_operator' have *NOTHING* to do in SelectJoin, must create mecanism for such exception
                if ($fk_column->foreignTableName() == 'kadro_operator' && $fk_column->name() == 'created_by') {
                    continue; // dont load the log information
                } else {
                    $m = [];
                    if (preg_match('/(.+)_(' . $fk_column->foreignColumnName() . ')$/', $fk_column->name(), $m)) {
                        $foreign_table_alias = $m[1];
                    } else {
                        $foreign_table_alias = $foreign_table_name;
                    }
                    $foreign_table_alias = $single_fk === true ? $foreign_table_alias : $foreign_table_alias . '_' . $fk_column->name();

                    // auto select non nullable columns
                }

                if (empty($select_also)) {
                    foreach ($foreign_table->columns() as $col) {
                        // if (!$col->isHidden()) {
                            $select_also [] = "$col";
                        // }
                    }
                }

                $this->auto_join([$foreign_table, $foreign_table_alias], $select_also);
            }
        }
    }


    public function join($table_names, $joins, $join_type = '')
    {
        list($join_table_name,$join_table_alias) = self::process_param_table_names($table_names);

        if (preg_match('/^(INNER|LEFT|RIGHT|FULL)(\sOUTER)?/i', $join_type) !== 1) {
            $join_type = '';
        }

        $this->join_raw($this->generate_join($join_type, $join_table_name, $join_table_alias, $joins));

        return $this;
    }

    public function auto_join($other_table, $select_also = [], $relation_type = null)
    {
        $other_table_alias = null;

        if (is_array($other_table)) {
            list($other_table, $other_table_alias) = $other_table;
        } else {
            $other_table_alias = $other_table->name();
        }

        $other_table_name = $other_table->name();

        $joins = [];

        // 1. ? this->table.other_table_id -> $other_table.id
        // 2. ? this_table.id -> $other_table.this_table_id)
        // if(count($bonding_column = $this->table()->foreignKeysByTable()[$other_table_name] ?? []) === 1)
        if (!is_null($bonding_column = $this->table()->singleForeignKeyTo($other_table))) {
            $relation_type = $relation_type ?? $bonding_column->isNullable() ? 'LEFT OUTER' : 'INNER';
            // $joins []= [$bonding_column->tableName(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];
            $joins [] = [$this->tableAlias(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];
        }
        // elseif(count($bonding_column = $other_table->foreignKeysByTable()[$this->table()->name()] ?? []) === 1)
        elseif (!is_null($bonding_column = $other_table->singleForeignKeyTo($this->table()))) {
            // vd(__FUNCTION__.' : '.$other_table.' has fk to '.$this->table());
            $relation_type = $relation_type ?? 'LEFT OUTER';
            $joins [] = [$this->tableLabel(), $bonding_column->foreignColumnName(), $other_table_alias ?? $other_table->name(), $bonding_column->name()];
        } else {
            $bondable_tables = $this->joinable_tables();
            if (isset($bondable_tables[$other_table_name])) {
                $bonding_columns = $bondable_tables[$other_table_name];
                if (count($bonding_columns) === 1) {
                    $bonding_column = current($bonding_columns);
                    $other_table_alias = $other_table_alias ?? $bonding_column->foreignTableAlias();

                    $bonding_table_label = array_search($bonding_column->tableName(), $this->joined_tables());
                    if ($bonding_table_label === false) {
                        $bonding_table_label = $bonding_column->tableName();
                    }

                    $joins = [[$bonding_table_label, $bonding_column->name(), $other_table_alias, $bonding_column->foreignColumnName()]];
                    $relation_type = $relation_type ?? (($bonding_column->isNullable()) ? 'LEFT OUTER' : 'INNER');
                }
            } elseif (count($intersections = array_intersect_key($other_table->foreignKeysByTable(), $bondable_tables)) > 0) {
                $other_table_alias = $other_table_alias ?? $other_table->name();
                foreach ($intersections as $table_name => $bonding_column) {
                    if (count($bonding_column) !== 1 || count($other_table->foreignKeysByTable()[$table_name]) !== 1) {
                        break;
                    }
                    // vd($this->tableName() . " $other_table_name");
                    // vd($bonding_column);

                    $joins = [];

                    $bonding_column = current($bonding_column);
                    $joins [] = [$other_table_alias, $bonding_column->name(), $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];

                    // vd($other_table_alias);
                    $bonding_column = current($bondable_tables[$table_name]);
                    $joins [] = [$bonding_column->tableName(), $bonding_column->name(), $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];

                    // $relation_type = $relation_type ?? (($parent_column->isNullable() || $bonding_column->isNullable()) ? 'LEFT OUTER' : 'INNER');
                    $relation_type = $relation_type ?? (($bonding_column->isNullable()) ? 'LEFT OUTER' : 'INNER');
                }
            }
        }

        // vd($relation_type.' '.json_encode($joins));
        if (!empty($joins)) {
            // vd('ottojoin: '.$this->table()->name().' with '.$other_table_name.' as '.$other_table_alias);
            $this->join([$other_table_name, $other_table_alias], $joins, $relation_type);
            $this->add_tables([$other_table_alias => $other_table_name]);


            // if(is_null($select_also) empty($select_also))
            //   $select_also=[$other_table_alias.'.*'];
            if (!empty($select_also)) {
                foreach ($select_also as $select_field) {
                    if (is_null($other_table->column("$select_field"))) {
                        $computed_selection = "$select_field"; // table column does not exist, no nood to prefix
                    } else {
                        $computed_selection = "$other_table_alias.$select_field as " . $other_table_alias . "_$select_field";
                    }

                    // vd($computed_selection);
                    $this->selectAlso($computed_selection);
                }
            }
        }

        return $other_table_alias;
    }

    protected function generate_join($join_type, $join_table_name, $join_table_alias = null, $join_fields)
    {
        $join_table_alias = $join_table_alias ?? $join_table_name;

        $join_parts = [];
        foreach ($join_fields as $join_cond) {
            if (isset($join_cond[3])) { // 4 joins param -> t.f = t.f
                list($table, $field, $join_table, $join_table_field) = $join_cond;
                $join_parts [] = $this->backTick($field, $table) . ' = ' . $this->backTick($join_table_field, $join_table);
            } elseif (isset($join_cond[2])) { // 3 joins param -> t.f = v
                list($table, $field, $value) = $join_cond;
                $bind_label = ':loj_' . $join_table_alias . '_' . $table . '_' . $field;
                $this->addBinding($field, $value, null, $bind_label);

                $join_parts [] = $this->backTick($field, $table) . ' = ' . $bind_label;
            }
        }
        return sprintf('%s JOIN `%s` %s ON %s', $join_type, $join_table_name, $join_table_alias, implode(' AND ', $join_parts));
    }

    private function joined_tables()
    {
        return $this->joined_tables;
    }

    private function joinable_tables(): array
    {
        $joinable_tables = $this->table()->foreignKeysByTable();
        foreach ($this->joined_tables() as $join_table) {
            $joinable_tables += Crudites::inspect($join_table)->foreignKeysByTable();
        }

        return $joinable_tables;
    }

    private static function process_param_table_names($table_names): array
    {
        if (is_array($table_names) && isset($table_names[1])) {
            return $table_names;
        }

        if (is_array($table_names) && !isset($table_names[1])) {
            $table_names = current($table_names);
        }

        if (is_string($table_names)) {
            return [$table_names, $table_names];
        }

        throw new CruditesException('INVALID_PARAM_TABLE_NAMES');
    }
}
