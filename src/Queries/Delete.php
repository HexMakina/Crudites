<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\Queries\Clauses\Where;

class Delete extends Query
{
    protected $table;
    protected $where;

    public function __construct(string $table, array $strict_conditions)
    {
        if (empty($strict_conditions)) {
            throw new CruditesException('DELETE_USED_AS_TRUNCATE');
        }

        $this->table = $table;
        $this->where = (new Where($table))->andFields($strict_conditions, $table, '=');
    }

    public function statement(): string
    {
        return sprintf('DELETE FROM `%s` %s ', $this->table, $this->where);
    }

    public function bindings(): array
    {
        return $this->where->bindings();
    }
}
