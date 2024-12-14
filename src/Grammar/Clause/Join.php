<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Predicate;

/**
 * LEFT JOIN, two ways
 * (new Join('cbx_order', 'Orders'))->on('user_id', 'User', 'id')->type('LEFT');
 * (new Join('cbx_order', 'Orders'))->on('user_id', 'User', 'id')->left();

 * INNER JOIN, with helper, before on
 * (new Join('cbx_order', 'Orders'))->inner()->on('user_id', 'User', 'id');
 */

class Join extends Clause
{
    protected string $type;
    protected string $table;
    protected string $alias;

    protected ?string $column;
    protected ?string $referenced_table;
    protected ?string $referenced_column;

    public function __construct(string $table, string $alias = null)
    {
        $this->type = '';
        $this->table = $table;
        $this->alias = $alias ?? $table;
        
        $this->column = null;
        $this->referenced_table = null;
        $this->referenced_column = null;
    }

    public function add($nothing): self
    {
        return $this;
    }

    public function on(string $column, string $join_table, string $join_column): self
    {
        $this->column = $column;
        $this->referenced_table = $join_table;
        $this->referenced_column = $join_column;

        return $this;
    }

    public function type(string $join_type): self
    {
        $this->type = $join_type;

        return $this;
    }

    public function __toString(): string
    {
        if(isset($this->column, $this->referenced_table, $this->referenced_column)) {
            $on = (string)(new Predicate([$this->alias, $this->column], '=', [$this->referenced_table, $this->referenced_column]));
        }
        
        return trim(sprintf('%s JOIN `%s` `%s` ON %s', $this->type, $this->table, $this->alias, $on ?? ''));
    }

    /**
     * @return string the name of the clause
     */
    public function name(): string
    {
        return self::JOIN;
    }

    /**
     * @return string the alias of the table
     */
    public function alias(): string
    {
        return $this->alias;
    }

    /**
     * @return string the table name
     */
    public function table(): string
    {
        return $this->table;
    }

    public function column(): ?string
    {
        return $this->column;
    }
    /**
     * @return array [table, column] the referenced table and column
     */
    public function referenced(): ?array
    {
        if(isset($this->referenced_table, $this->referenced_column)) {
            return [$this->referenced_table, $this->referenced_column];
        }

        return null;
    }
}