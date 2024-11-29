<?php

namespace HexMakina\Crudites\Grammar\Clause;

class Limit extends Clause
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

    public function name(): string
    {
        return self::LIMIT;
    }
}