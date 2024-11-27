<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Predicates\{Predicate};

class Where
{
    protected string $default_table;
    protected array $and = [];
    protected array $bindings = [];

    public function __construct(string $default_table, array $predicates = [])
    {
        $this->default_table = $default_table;
        foreach ($predicates as $predicate) {
            $this->andPredicate($predicate);
        }
    }
    public function and(string $condition, $bindings = [])
    {
        $this->and ??= [];
        $this->and[] = $condition;

        if (!empty($bindings)) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }

        return $this;
    }

    public function andPredicate(Predicate $predicate)
    {
        return $this->and($predicate->__toString(), $predicate->getBindings());
    }

    public function bindings(): array
    {
        return $this->bindings;
    }

    public function __toString(): string
    {
        if (!empty($this->and)) {
            return PHP_EOL . ' WHERE ' . implode(PHP_EOL . ' AND ', $this->and);
        }

        return '';
    }

    public function andIsNull($field, $table_name = null)
    {
        return $this->andPredicate(new Predicate([$table_name ?? $this->default_table, $field], 'IS NULL'));
    }

    public function andFields($assoc_data, $table_name = null, $operator = '=')
    {
        foreach ($assoc_data as $field => $value) {
            $column = $table_name === null ? $field : [$table_name, $field];
            $predicate = (new Predicate($column,$operator))->withValue($value);

            $this->andPredicate($predicate);
        }

        return $this;
    }

    public function andIn($field, $values, $table_name = null)
    {
        return $this->andPredicate(
            (new Predicate($table_name === null ? $field : [$table_name, $field]))->withValues($values, __FUNCTION__));
    }
}
