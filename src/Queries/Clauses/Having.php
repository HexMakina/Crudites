<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Grammar;
use HexMakina\Crudites\Queries\Predicates\Predicate;

class Having extends Grammar
{
    private string $conditions;
    private array $bindings = [];

    public function __construct($column, string $operator, $value)
    {
        $this->conditions = $this->process($column, $operator, $value);
    }

    public function add($column, string $operator, $value): self
    {
        $this->conditions .= ' AND ' . $this->process($column, $operator, $value);

        return $this;
    }

    public function __toString()
    {
        return empty($this->conditions) ? '' : 'HAVING ' . $this->conditions;
    }

    private function process($column, string $operator, $value): string
    {
        $predicate = new Predicate($column, $operator);
        $predicate->withValue($value, __CLASS__ . '_' . count($this->bindings));

        $this->bindings = array_merge($this->bindings, $predicate->bindings());
        return $predicate->__toString();
    }
}
