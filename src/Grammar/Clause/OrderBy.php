<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\DeckOrderBy;
use HexMakina\Crudites\Grammar\Grammar;

class OrderBy extends Clause
{
    private Deck $deck;

    public function __construct($selected, string $direction)
    {
        $this->deck = new DeckOrderBy($selected, $direction);
    }

    public function add($selected, string $direction): self
    {
        $this->deck->add($selected, $direction);
        return $this;
    }

    public function __toString()
    {
        return 'ORDER BY ' . $this->deck;
    }


    public function name(): string
    {
        return self::ORDER;
    }
}