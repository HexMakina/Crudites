<?php

namespace HexMakina\Crudites\Grammar\Clause;


class GroupBy extends Clause
{
    private string $columns;

    public function __construct(string|array $selected)
    {
        $this->columns = self::selected($selected);
    }

    public function add(string|array $selected): self
    {
        $this->columns .= ', ' . self::selected($selected);
        return $this;
    }

    public function __toString()
    {
        return empty($this->columns) ? '' : 'GROUP BY ' . $this->columns;
    }

    public function name(): string
    {
        return self::GROUP;
    }
}
