<?php

namespace HexMakina\Crudites\Queries;

/**
 * Represents a predicate with a value for use in SQL queries.
 *
 * @package HexMakina\Crudites\Queries
 */

class PredicateValue extends Predicate
{
    private $value;

    public function __construct($column, string $operator, $value, string $label=null)
    {
        parent::__construct($column, $operator);
        $this->value = $value;
        
        $this->binding_label = $label;
        $this->bindings = [$this->bindingLabel() => $this->value];
    }

    /**
     * Returns the right-hand side of the predicate.
     *
     * @return string The right-hand side of the predicate.
     */
    protected function right(): string
    {
        return sprintf(':%s', $this->bindingLabel());
    }
}
