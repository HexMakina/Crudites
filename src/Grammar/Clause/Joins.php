<?php

namespace HexMakina\Crudites\Grammar\Clause;

class Joins extends Clause
{
    protected array $joins = [];
    protected array $joined_tables = [];

    public function __construct(array $joins = [])
    {
        foreach ($joins as $join) {
            $this->add($join);
        }
    }

    // add a Join object to the list of joins
    public function add(...$join): self
    {
        $join = array_pop($join);
        if (isset($this->joined_tables[$join->alias()]) && $this->joined_tables[$join->alias()] !== $join->table()) {
            $res = sprintf('JOIN %s WITH ALIAS %s ALREADY ALLOCATED FOR TABLE %s', $join->table(), $join->alias(), $this->joined_tables[$join->alias()]);
            throw new \Exception($res);
        }

        $this->joined_tables[$join->alias()] = $join->table();
        $this->joins[$join->alias()] = $join;

        return $this;
    }

    public function __toString(): string
    {
        return implode(' ', $this->joins);
    }

    public function name(): string
    {
        return self::JOINS;
    }

}