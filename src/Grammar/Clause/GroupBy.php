<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Deck;

class GroupBy extends Clause
{
    private Deck $deck;

    /**
     * GroupBy constructor.
     *
     * @param mixed $selected The selected columns or expressions for the GROUP BY clause.
     */
    public function __construct($selected)
    {
        $this->deck = new Deck($selected);
    }

    public function add($selected): self
    {
        $this->deck->add($selected);
        return $this;
    }

    public function __toString(): string
    {
        return 'GROUP BY ' . $this->deck;
    }

    public function name(): string
    {
        return self::GROUP;
    }
}
