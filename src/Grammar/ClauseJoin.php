<?php

namespace HexMakina\Crudites\Grammar\Query;

use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableInterface;

trait ClauseJoin
{
    protected $joined_tables = [];

    abstract public function backTick($field, $table_name = null);

    abstract public function addBinding($field, $value, $table_name, $bind_label = null): string;


    /**
     * Adds a joined table to the query.
     *
     * @param string $join_table_name The name of the table to join.
     * @param string $join_table_alias The alias for the joined table.
     * @throws \InvalidArgumentException If the alias is already allocated for a different table.
     */
    public function addJoinedTable($join_table_name, $join_table_alias)
    {
        if(!isset($this->joined_tables[$join_table_alias])){
            $this->joined_tables[$join_table_alias] = $join_table_name;
        }
        elseif ($this->joined_tables[$join_table_alias] !== $join_table_name){
            throw new \InvalidArgumentException(sprintf(__FUNCTION__.'(): ALIAS `%s` ALREADY ALLOCATED FOR TABLE  `%s`', $join_table_alias, $join_table_name));
        }
    }


    public function join($table_names, $joins, $join_type = '')
    {
        list($join_table_name,$join_table_alias) = self::processParamTableNames($table_names);

        if (preg_match('#^(INNER|LEFT|RIGHT|FULL)(\sOUTER)?#i', $join_type) !== 1) {
            $join_type = '';
        }
        $this->addJoinedTable($join_table_name, $join_table_alias);

        $raw_join = $this->generateJoin($join_type, $join_table_name, $join_table_alias, $joins);
        $this->joinRaw($raw_join);

        return $this;
    }

    public function joinRaw($sql)
    {
        return $this->addClause('join', $sql);
    }

    protected function generateJoin($join_type, $join_table_name, $join_table_alias = null, $join_fields = []): string
    {
        $join_table_alias ??= $join_table_name;

        $join_parts = [];
        foreach ($join_fields as $join_field) {
            $table = array_shift($join_field);
            $field = array_shift($join_field);
            
            if (!isset($table, $field, $join_field[0])) {
                throw new \InvalidArgumentException('INVALID_JOIN_FIELDS');
            }

            $right_operand = null;
            // 4 join params -> t.f = t.f
            if (isset($join_field[1])) {
                $right_operand = $this->backTick($join_field[1], $join_field[0]);

            } 
            // 3 join params -> t.f = v
            else{ 
                $value = $join_field[0];
                // join bind label

                $bind_label = sprintf(':jbl_%s_%s', $join_table_alias, $field);
                // vd($bind_label, 'bind_label');
                $this->addBinding($field, $value, $join_table_alias, $bind_label);

                $right_operand = $bind_label;
            }
      

            $join_parts[] = sprintf('%s = %s', $this->backTick($field, $table), $right_operand);
        }

        return sprintf('%s JOIN `%s` %s ON %s', $join_type, $join_table_name, $join_table_alias, implode(' AND ', $join_parts));
    }

    /**
     * @return mixed[]
     */
    public function joinedTables(): array
    {
        return $this->joined_tables;
    }

    /**
     * @return mixed[]|string[]
     */
    private static function processParamTableNames($table_names): array
    {
        // it's an array with two indexes, all fine
        if (is_array($table_names) && isset($table_names[1]) && !isset($table_names[2])) {
            return $table_names;
        }

        if (is_array($table_names) && !isset($table_names[1])) {
            $table_names = current($table_names);
        }

        if (is_string($table_names)) {
            return [$table_names, $table_names];
        }

        throw new \InvalidArgumentException('INVALID_PARAM_TABLE_NAMES');
    }
}
