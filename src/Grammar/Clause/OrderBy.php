<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\DeckOrderBy;

class OrderBy extends Clause
{
    private DeckOrderBy $deck;

    public function __construct($selected, string $direction)
    {
        $this->deck = new DeckOrderBy($selected, $direction);
    }

    public function add(...$args): self
    {
        list($selected, $direction) = $args;
        $this->deck->add($selected, $direction);
        return $this;
    }

    public function asc($selected): self
    {
        return $this->add([$selected, 'ASC']);
    }

    public function desc($selected): self
    {
        return $this->add([$selected, 'DESC']);
    }

    public function __toString(): string
    {
        return 'ORDER BY ' . $this->deck;
    }


    public function name(): string
    {
        return self::ORDER;
    }
}
