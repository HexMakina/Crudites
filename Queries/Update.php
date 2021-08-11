<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\CruditesException;
use \HexMakina\Crudites\Interfaces\TableManipulationInterface;

class Update extends BaseQuery
{
    use ClauseWhere;

    private $alterations = [];

    public function __construct(TableManipulationInterface $table, $update_data = [], $conditions = [])
    {
        $this->table = $table;
        $this->connection = $table->connection();

        if (!empty($update_data)) {
            $this->values($update_data);
        }

        if (!empty($conditions)) {
            if (is_array($conditions)) {
                $this->aw_fields_eq($conditions);
            } elseif (is_string($conditions)) {
                $this->and_where($conditions);
            }
        }
    }

    public function values($update_data)
    {
        foreach ($update_data as $field_name => $value) {
            $column = $this->table->column($field_name);
            if (is_null($column)) {
                continue;
            }

            if ($value === '' && $column->is_nullable()) {
                $value = null;
            } elseif (empty($value) && $column->type()->is_boolean()) { //empty '', 0, false
                $value = 0;
            }

            $binding_name = $this->bind_label($field_name);
            $this->add_binding($binding_name, $value);
            $this->alterations []= $this->field_label($field_name)." = $binding_name";
        }
        return $this;
    }

    public function has_alterations()
    {
        return !empty($this->alterations);
    }

    public function generate() : string
    {
        if (empty($this->alterations)) {
            throw new CruditesException('UPDATE_NO_ALTERATIONS');
        }

        // prevents haphazrdous generation of massive update query, must use statement setter for such jobs
        if (empty($this->where)) {
            throw new CruditesException('UPDATE_NO_CONDITIONS');
        }

        $ret = sprintf('UPDATE `%s` SET %s %s;', $this->table->name(), implode(', ', $this->alterations), $this->generate_where());
        return $ret;
    }
}
