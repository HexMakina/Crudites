<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Grammar;

class GroupBy extends Grammar
{
    private string $columns;

    public function __construct(string|array $selected)
    {
        $this->columns = Grammar::selected($selected);
    }

    public function add(string|array $selected): self
    {
        $this->columns .= ', ' . Grammar::selected($selected);
        return $this;
    }

    public function __toString()
    {
        return empty($this->columns) ? '' : 'GROUP BY ' . $this->columns;
    }
}
