<?php

namespace HexMakina\Crudites\Grammar\Clause;

class SelectFrom extends Clause
{
    protected $columns;

    protected $table;
    protected $alias;

    public function __construct(string $table, string $alias = null)
    {
        $this->columns = [];
        $this->table = $table;
        $this->alias = $alias;
    }

    public function name(): string
    {
        return self::SELECT;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function add($selected, string $alias = null): self
    {
        $selected = self::selected($selected);

        if ($alias !== null) {
            $selected .= ' AS ' . self::backtick($alias);
        }

        return $this->addRaw($selected);
    }

    public function addRaw(string $raw): self
    {
        $this->columns[] = $raw;
        return $this;
    }

    public function all(string $alias = null): self
    {
        return $this->addRaw(sprintf('`%s`.*', $alias ?? $this->alias ?? $this->table));
    }

    public function __toString()
    {
        if (empty($this->columns)) {
            $this->all();
        }

        $schema = self::backtick($this->table);
        if (!empty($this->alias)) {
            $schema .= ' AS ' . self::backtick($this->alias);
        }

        $columns = implode(',', $this->columns);

        return sprintf('SELECT %s FROM %s', $columns, $schema);
    }
}
