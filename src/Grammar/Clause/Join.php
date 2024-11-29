<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Grammar;
use HexMakina\Crudites\Grammar\Predicate\Predicate;

/**
 * LEFT JOIN, two ways
 * (new Join('cbx_order', 'Orders'))->on('user_id', 'User', 'id')->type('LEFT');
 * (new Join('cbx_order', 'Orders'))->on('user_id', 'User', 'id')->left();

 * INNER JOIN, with helper, before on
 * (new Join('cbx_order', 'Orders'))->inner()->on('user_id', 'User', 'id');
 */
²
class Join extends Grammar
{
    protected string $type;

    protected string $table;
    protected string $alias;

    protected string $on = null;
    
    public function __construct(string $table, string $alias = null)
    {
        $this->type = '';
        $this->table = $table;
        $this->alias = $alias ?? $table;
    }

    public function type(string $join_type): self
    {
        $this->type = $join_type;

        return $this;
    }

    public function on($column, $join_table, $join_column): self
    {
        $this->on = '' . (new Predicate([$this->alias, $column], '='))->withColumn([$join_table, $join_column]););
        

        return $this;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function __toString(): string
    {
        return sprintf('%s JOIN `%s` %s ON %s', $this->type, $this->table, $this->alias, $this->on);
    }
}