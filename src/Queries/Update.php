<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;

class Update extends Query
{
    use ClauseJoin;
    use ClauseWhere;

    private array $alterations = [];

    public function __construct(string $table, $alterations = [], $conditions = [])
    {
        // Check if the given data is a non-empty array, and throw an exception if it is not
        if (empty($alterations)) {
            throw new CruditesException(__CLASS__.'_DATA_INVALID_OR_MISSING');
        }

        $this->table = $table;

        $binding_names = [];
        foreach ($alterations as $field_name => $value) {
            $binding_names[$field_name] = $this->addBinding($field_name, $value, $this->table);
            $this->alterations[] = $this->backTick($field_name) . ' = ' . $binding_names[$field_name];
        }

        if (!empty($conditions)) {
            if (is_array($conditions)) {
                $this->whereFieldsEQ($conditions);
            } elseif (is_string($conditions)) {
                $this->where($conditions);
            }
        }
    }

    public function statement(): string
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
        return sprintf('UPDATE `%s` SET %s %s;', $this->table, $set, $where);
    }
}
