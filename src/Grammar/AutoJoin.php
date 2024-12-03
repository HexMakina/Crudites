<?php

namespace HexMakina\Crudites\Grammar\Query;

use HexMakina\Crudites\Crudites;
use HexMakina\BlackBox\Database\QueryInterface;

class AutoJoin
{
    public static function join(QueryInterface $select, $other_table, $select_also = [], $relation_type = null): string
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
        if (!is_null($bonding_column = $select->table()->singleForeignKeyTo($other_table))) {
            $relation_type = ($relation_type ?? $bonding_column->isNullable()) !== '' && ($relation_type ?? $bonding_column->isNullable()) !== '0' ? 'LEFT OUTER' : 'INNER';
            // $joins []= [$bonding_column->tableName(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];
            $joins [] = [$select->tableAlias(), $bonding_column->name(), $other_table_alias ?? $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];
        } elseif (!is_null($bonding_column = $other_table->singleForeignKeyTo($select->table()))) {
            $relation_type ??= 'LEFT OUTER';
            $joins [] = [$select->tableLabel(), $bonding_column->foreignColumnName(), $other_table_alias ?? $other_table->name(), $bonding_column->name()];
        } else {
            $bondable_tables = self::joinableTables($select);
            if (isset($bondable_tables[$other_table_name])) {
                $bonding_columns = $bondable_tables[$other_table_name];
                if (count($bonding_columns) === 1) {
                    $bonding_column = current($bonding_columns);
                    $other_table_alias ??= $bonding_column->foreignTableAlias();

                    $bonding_table_label = array_search($bonding_column->tableName(), $select->joinedTables(), true);
                    if ($bonding_table_label === false) {
                        $bonding_table_label = $bonding_column->tableName();
                    }

                    $joins = [[$bonding_table_label, $bonding_column->name(), $other_table_alias, $bonding_column->foreignColumnName()]];
                    $relation_type ??= ($bonding_column->isNullable()) ? 'LEFT OUTER' : 'INNER';
                }
            } elseif (($intersections = array_intersect_key($other_table->foreignKeysByTable(), $bondable_tables)) !== []) {
                $other_table_alias ??= $other_table->name();
                foreach ($intersections as $table_name => $bonding_column) {
                    if (count($bonding_column) !== 1 || count($other_table->foreignKeysByTable()[$table_name]) !== 1) {
                        break;
                    }


                    $joins = [];

                    $bonding_column = current($bonding_column);
                    $joins [] = [$other_table_alias, $bonding_column->name(), $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];

                    $bonding_column = current($bondable_tables[$table_name]);
                    $joins [] = [$bonding_column->tableName(), $bonding_column->name(), $bonding_column->foreignTableAlias(), $bonding_column->foreignColumnName()];

                    // $relation_type = $relation_type ?? (($parent_column->isNullable() || $bonding_column->isNullable()) ? 'LEFT OUTER' : 'INNER');
                    $relation_type ??= ($bonding_column->isNullable()) ? 'LEFT OUTER' : 'INNER';
                }
            }
        }

        if (!empty($joins)) {
            
            $select->join([$other_table_name, $other_table_alias], $joins, $relation_type);
            $select->addJoinedTable($other_table_name, $other_table_alias);


            if (!empty($select_also)) {
                $select->selectAlso($select_also);
            }
        }

        return $other_table_alias;
    }

    /**
     * @return mixed[]
     */
    private static function joinableTables(QueryInterface $select): array
    {
        $joinable_tables = $select->table()->foreignKeysByTable();
        foreach ($select->joinedTables() as $join_table) {
            $joinable_tables += Crudites::database()->table($join_table)->foreignKeysByTable();
        }

        return $joinable_tables;
    }

    public static function eager(QueryInterface $select, $table_aliases = []): void
    {
        
        if (isset($table_aliases[$select->table()->name()])) {
            $select->tableAlias($table_aliases[$select->table()->name()]);
        }

        foreach ($select->table()->foreignKeysByTable() as $foreign_table_name => $fk_columns) {
            $foreign_table = Crudites::database()->table($foreign_table_name);

        

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

                    $foreign_table_alias = $single_fk ? $foreign_table_alias : $foreign_table_alias . '_' . $fk_column->name();

                    // auto select non nullable columns
                }
     
                foreach ($foreign_table->columns() as $col) {

                    // if($col->isNullable())
                    //     continue;

                    $select_also[$foreign_table_alias.'_'.$col] = [$foreign_table_alias, "$col"];
                }

                self::join($select, [$foreign_table, $foreign_table_alias], $select_also);
            }
        }
    }
}
