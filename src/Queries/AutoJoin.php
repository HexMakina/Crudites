<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\QueryInterface;

class AutoJoin
{
    public static function join(QueryInterface $query, $other_table, $select_also = [], $relation_type = null): string
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
        if (!is_null($bonding_column = $query->table()->singleForeignKeyTo($other_table))) {
            $relation_type = $relation_type ?? $bonding_column->isNullable() ? 'LEFT OUTER' : 'INNER';
            // $joins []= [$bonding_column->tableName(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];
            $joins [] = [$query->tableAlias(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];
        } elseif (!is_null($bonding_column = $other_table->singleForeignKeyTo($query->table()))) {
            // vd(__FUNCTION__.' : '.$other_table.' has fk to '.$query->table());
            $relation_type = $relation_type ?? 'LEFT OUTER';
            $joins [] = [$query->tableLabel(), $bonding_column->foreignColumnName(), $other_table_alias ?? $other_table->name(), $bonding_column->name()];
        } else {
            $bondable_tables = self::joinableTables($query);
            if (isset($bondable_tables[$other_table_name])) {
                $bonding_columns = $bondable_tables[$other_table_name];
                if (count($bonding_columns) === 1) {
                    $bonding_column = current($bonding_columns);
                    $other_table_alias = $other_table_alias ?? $bonding_column->foreignTableAlias();

                    $bonding_table_label = array_search($bonding_column->tableName(), $query->joinedTables());
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
                    // vd($query->tableName() . " $other_table_name");
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
            // vd('ottojoin: '.$query->table()->name().' with '.$other_table_name.' as '.$other_table_alias);
            $query->join([$other_table_name, $other_table_alias], $joins, $relation_type);
            $query->addTables([$other_table_alias => $other_table_name]);


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
                    $query->selectAlso($computed_selection);
                }
            }
        }

        return $other_table_alias;
    }

    private static function joinableTables(QueryInterface $query): array
    {
        $joinable_tables = $query->table()->foreignKeysByTable();
        foreach ($query->joinedTables() as $join_table) {
            $joinable_tables += Crudites::inspect($join_table)->foreignKeysByTable();
        }

        return $joinable_tables;
    }
    public static function eager(QueryInterface $query, $table_aliases = [])
    {
        if (isset($table_aliases[$query->tableName()])) {
            $query->tableAlias($table_aliases[$query->tableName()]);
        }

        foreach ($query->table()->foreignKeysByTable() as $foreign_table_name => $fk_columns) {
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

                self::join($query, [$foreign_table, $foreign_table_alias], $select_also);
            }
        }
    }
}
