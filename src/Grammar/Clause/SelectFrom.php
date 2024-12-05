<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Deck;

class SelectFrom extends Clause
{
    private Deck $deck;
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

    public function add($selected, string $alias = null): self
    {
        $selected = is_array($selected) ? self::identifier($selected) : $selected;

        if ($alias !== null) {
            $selected .= ' AS ' . self::identifier($alias);
        }

        return $this->addRaw($selected);
    }

    public function addRaw(string $raw): self
    {
        if (!isset($this->deck))
            $this->deck = new Deck($raw);
        else
            $this->deck->addRaw($raw);
        
            return $this;
    }

    public function all(): self
    {
        $this->addRaw('*');
        return $this;
    }

    public function __toString(): string
    {
        if (!isset($this->deck) || $this->deck->empty()) {
            $this->all();
        }

        $schema = self::identifier($this->table);
        if (!empty($this->alias)) {
            $schema .= ' ' . self::identifier($this->alias);
        }

        return sprintf('SELECT %s FROM %s', $this->deck ?? '*', $schema);
    }
}
