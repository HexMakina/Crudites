<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Grammar;


class OrderBy extends Grammar
{
    private string $columns;

    public function __construct(string|array $selected, string $direction)
    {

        $this->columns = $this->format($selected, $direction);
    }

    public function add(string|array $selected, string $direction): self
    {
        $this->columns .= ', ' . $this->format($selected, $direction);
        return $this;
    }

    public function __toString()
    {
        return empty($this->columns) ? '' : 'ORDER BY ' . $this->columns;
    }

    private function format(string|array $selected, string $direction): string
    {
        return Grammar::selected($selected) . ' ' . $direction;
    }
}