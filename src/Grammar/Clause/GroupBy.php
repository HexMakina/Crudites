<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Deck;

class GroupBy extends Clause
{
    private Deck $deck;

    public function __construct(string|array $selected)
    {
        $this->deck = new Deck($selected);
    }

    public function add(string|array $selected): self
    {
        $this->deck->add($selected);
        return $this;
    }

    public function __toString()
    {
        return 'GROUP BY ' . $this->deck;
    }

    public function name(): string
    {
        return self::GROUP;
    }
}
