<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Predicates\Predicate;

/**
 * LEFT JOIN, two ways
 * (new Join('cbx_order', 'Orders'))->on('user_id', 'User', 'id')->type('LEFT');
 * (new Join('cbx_order', 'Orders'))->on('user_id', 'User', 'id')->left();

 * INNER JOIN, with helper, before on
 * (new Join('cbx_order', 'Orders'))->inner()->on('user_id', 'User', 'id');
 */
class Join
{
    protected string $type = null;
    protected string $table;
    protected string $alias;
    protected string $on = null;
    
    protected $bindings = [];


    public function __construct(string $table, string $alias = null)
    {
        $this->alias = $alias ?? $table;
        $this->table = $table;
    }

    // little helper for natural language
    public function __call($name, $arguments)
    {
        if (preg_match('#^(INNER|LEFT|RIGHT|FULL|CROSS|NATURAL)#i', $name) === 1) {
            $this->type = $name;
        }

        return $this;
    }

    public function type(string $join_type): self
    {
        $this->type = $join_type;

        return $this;
    }

    public function on($column, $join_table, $join_column): self
    {
        $this->on = '' . (new Predicate([$this->alias, $column], '=', [$join_table, $join_column]));

        return $this;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function __toString(): string
    {
        return sprintf('%s JOIN `%s` %s ON %s', $this->type ?? '', $this->table, $this->alias, $this->on);
    }
}