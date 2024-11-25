<?php

namespace HexMakina\Crudites\Queries\Predicates;

/**
 * Represents a predicate with a value for use in SQL queries.
 */

class WithValue extends Predicate
{
    private $value;

    public function __construct($column, string $operator, $value, string $label=null)
    {
        parent::__construct($column, $operator);
        $this->value = $value;
        
        $this->bind_label = $label;
        $this->bindings = [$this->bindLabel() => $this->value];
    }

    /**
     * Returns the right-hand side of the predicate.
     *
     * @return string The right-hand side of the predicate.
     */
    protected function right(): string
    {
        return sprintf(':%s', $this->bindLabel());
    }
}
