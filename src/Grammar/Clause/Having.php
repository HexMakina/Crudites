<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Predicate;

class Having extends Clause
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

    public function name(): string
    {
        return self::HAVING;
    }

    private function process($column, string $operator, $value): string
    {
        $predicate = new Predicate($column, $operator);
        $predicate->withValue($value, __CLASS__ . '_' . count($this->bindings));

        $this->bindings = array_merge($this->bindings, $predicate->bindings());
        return $predicate->__toString();
    }
}
