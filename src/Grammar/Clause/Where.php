<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Predicate\Predicate;

class Where extends Clause
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

    public function bindings(): array
    {
        return $this->bindings;
    }

    public function __toString(): string
    {
        if (empty($this->and)) {
            return '';
        }

        return PHP_EOL . ' WHERE ' . implode(PHP_EOL . ' AND ', $this->and);
    }

    public function name(): string
    {
        return self::WHERE;
    }

    public function andRaw(string $condition, $bindings = [])
    {
        $this->and[] = $condition;

        if (!empty($bindings)) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }

        return $this;
    }

    public function andPredicate(Predicate $predicate)
    {
        return $this->andRaw($predicate->__toString(), $predicate->bindings());
    }



    public function andIsNull(string $field, $table_name = null)
    {
        $table = $table_name ?: $this->default_table;
        return $this->andRaw(new Predicate([$table, $field], 'IS NULL'));
    }

    public function andFields(array $assoc_data, $table_name = null, $operator = '=')
    {
        foreach ($assoc_data as $field => $value) {
            $column = $table_name === null ? $field : [$table_name, $field];
            $predicate = (new Predicate($column, $operator))->withValue($value, __FUNCTION__);

            $this->andPredicate($predicate);
        }

        return $this;
    }

    public function andIn(string $field, array $values, $table_name = null)
    {
        return $this->andPredicate(
            (new Predicate($table_name === null ? $field : [$table_name, $field]))
                ->withValues($values, __FUNCTION__)
        );
    }
}
