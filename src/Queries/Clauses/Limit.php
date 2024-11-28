<?php

namespace HexMakina\Crudites\Queries\Clauses;

class Limit
{
    private string $clause;
    
    public function __construct(int $number, int $offset = 0)
    {
        $this->clause = sprintf('LIMIT %s OFFSET %s', $number, $offset);
    }

    public function __toString()
    {
        return $this->clause;
    }
}