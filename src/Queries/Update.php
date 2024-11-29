<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\Queries\Clauses\Where;
use HexMakina\Crudites\Queries\Clauses\Set;


class Update extends Query
{
    public function __construct(string $table, array $alterations, array $conditions)
    {
        if (empty($alterations) || empty($conditions)) {
            throw new \InvalidArgumentException('EMPTY_ALTERATIONS_OR_CONDITIONS');
        }

        $this->table = $table;
        
        $this->add(new Set($alterations));

        $clause = new Where($table);
        $clause->andFields($conditions, $table, '=');
        $this->add($clause);
    }

    public function statement(): string
    {
        return sprintf('UPDATE `%s` %s %s;', $this->table, $this->clause(Set::SET), $this->clause(Where::WHERE));
    }
}
