<?php

namespace HexMakina\Crudites\Grammar\Query;

use HexMakina\Crudites\Grammar\Clause\Where;

class Delete extends Query
{
    public function __construct(string $table, array $strict_conditions)
    {
        if (empty($strict_conditions)) {
            throw new \InvalidArgumentException('DELETE_USED_AS_TRUNCATE');
        }

        $this->table = $table;
        $this->add((new Where())->andFields($strict_conditions, $table, '='));
    }

    public function statement(): string
    {
        return sprintf('DELETE FROM `%s` %s ', $this->table, $this->clause(Where::WHERE));
    }

    public function bindings(): array
    {
        return $this->clause(Where::WHERE)->bindings();
    }
}
