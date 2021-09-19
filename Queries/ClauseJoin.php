<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\CruditesException;
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
    abstract public function addBinding($field, $value, $table_name = null, $bind_label = null): string;
    abstract public function joinRaw($sql);

    public function addTables($setter)
    {
        $this->joined_tables = array_merge($this->joined_tables, is_array($setter) ? $setter : [$setter]);
        return $this;
    }


    public function join($table_names, $joins, $join_type = '')
    {
        list($join_table_name,$join_table_alias) = self::processParamTableNames($table_names);

        if (preg_match('/^(INNER|LEFT|RIGHT|FULL)(\sOUTER)?/i', $join_type) !== 1) {
            $join_type = '';
        }

        $this->joinRaw($this->generateJoin($join_type, $join_table_name, $join_table_alias, $joins));

        return $this;
    }


    protected function generateJoin($join_type, $join_table_name, $join_table_alias = null, $join_fields = [])
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

    public function joinedTables(): array
    {
        return $this->joined_tables;
    }

    private static function processParamTableNames($table_names): array
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
