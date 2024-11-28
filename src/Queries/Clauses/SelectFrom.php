<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Grammar;

class SelectFrom extends Grammar
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

    public function table(): string
    {
        return $this->table;
    }

    public function alias(): string
    {
        return $this->alias;
    }
    

    public function add(string $selected, string $alias = null): self
    {
        if (!empty($alias)) {
            $selected .= ' AS ' . $alias;
        }

        $this->columns[] = $selected;
        return $this;
    }

    public function all(string $alias = null): self
    {
        $this->columns[] = sprintf('`%s`.*', $alias ?? $this->alias ?? $this->table);
        return $this;
    }

    public function addColumn($column, string $alias = null): self
    {
        return $this->add(self::backtick($column), $alias);
    }

    public function addFunction(string $aggregate, string $alias = null): self
    {
        return $this->add($aggregate, $alias);
    }


    public function __toString()
    {
        $columns = empty($this->columns) ? '*' : implode(',', $this->columns);
        
        $schema = self::backtick($this->table);
        if(!empty($this->alias))
        {
            $schema .= ' AS ' . self::backtick($this->alias);
        }

        return sprintf('SELECT %s FROM %s', $columns, $schema);
    }
}
