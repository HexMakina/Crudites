<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableManipulationInterface;

class Update extends BaseQuery
{
    use ClauseWhere;

    private $alterations = [];

    public function __construct(TableManipulationInterface $table, $update_data = [], $conditions = [])
    {
        $this->table = $table;
        $this->connection = $table->connection();

        if (!empty($update_data)) {
            $this->addBindings($update_data);
        }

        if (!empty($conditions)) {
            if (is_array($conditions)) {
                $this->whereFieldsEQ($conditions);
            } elseif (is_string($conditions)) {
                $this->where($conditions);
            }
        }
    }

    public function addBindings($update_data): array
    {
        $binding_names = [];
        foreach ($update_data as $field_name => $value) {
            $column = $this->table->column($field_name);
            if (is_null($column)) {
                continue;
            }

            if ($value === '' && $column->isNullable()) {
                $value = null;
            } elseif (empty($value) && $column->type()->isBoolean()) { //empty '', 0, false
                $value = 0;
            }
            $binding_names[$field_name] = $this->addBinding($field_name, $value);
            $this->alterations [] = $this->backTick($field_name) . ' = ' . $binding_names[$field_name];
        }
        return $binding_names;
    }

    public function generate(): string
    {
        if (empty($this->alterations)) {
            throw new CruditesException('UPDATE_NO_ALTERATIONS');
        }

        // prevents haphazrdous generation of massive update query, must use statement setter for such jobs
        if (empty($this->where)) {
            throw new CruditesException('UPDATE_NO_CONDITIONS');
        }
        $set = implode(', ', $this->alterations);
        $where = $this->generateWhere();
        $ret = sprintf('UPDATE `%s` SET %s %s;', $this->table->name(), $set, $where);
        return $ret;
    }
}
