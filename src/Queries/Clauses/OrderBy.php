<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Grammar;


class OrderBy extends Grammar
{
    private $columns;

    public function __construct($column, string $direction = null)
    {

        $this->columns = [];
        $this->add($column, $direction);
    }

    public function add($column, string $direction = null): self
    {
        return $this->addRaw(self::backtick($column) . ' ' . $direction);
    }

    public function addRaw(string $clause): self
    {
        $this->columns[] = $clause;
        return $this;
    }

    public function __toString()
    {
        return empty($this->columns) ? '' : 'ORDER BY ' . implode(', ', $this->columns);
    }
}