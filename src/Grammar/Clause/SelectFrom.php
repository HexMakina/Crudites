<?php

namespace HexMakina\Crudites\Grammar\Clause;


class SelectFrom extends Clause
{

    /**
     * he problem is that the class SelectFrom is missing the SELECT constant and the selected and backtick methods. To fix this, you need to add these missing elements.
     */

    protected $columns;
    protected $table;
    protected $alias;

    public function __construct(string $table, string $alias = null)
    {
        $this->table = $table;
        $this->alias = $alias;
    }

    public function name(): string
    {
        return Clause::SELECT;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    // public function add($selected, string $alias = null): self
    // {
    //     $selected = self::selected($selected);

    //     if ($alias !== null) {
    //         $selected .= ' AS ' . self::identifier($alias);
    //     }

    //     return $this->addRaw($selected);
    // }

    // public function addRaw(string $raw): self
    // {
    //     $this->columns[] = $raw;
    //     return $this;
    // }

    public function all(string $alias = null): self
    {
        return $this->addRaw(sprintf('`%s`.*', $alias ?? $this->alias ?? $this->table));
    }

    public function __toString()
    {
        if (empty($this->columns)) {
            $this->all();
        }

        $schema = self::identifier($this->table);
        if (!empty($this->alias)) {
            $schema .= ' AS ' . self::identifier($this->alias);
        }

        $columns = implode(',', $this->columns);

        return sprintf('SELECT %s FROM %s', $columns, $schema);
    }
}
