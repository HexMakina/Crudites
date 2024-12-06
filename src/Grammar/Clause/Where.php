<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Predicate;

class Where extends Clause
{
    protected array $and = [];

    public function __construct(array $predicates = null)
    {
        if ($predicates !== null){
            foreach ($predicates as $predicate) {
                if (is_string($predicate))
                    $this->and($predicate);
                else
                    $this->and($predicate, $predicate->bindings());
            }
        }
    }

    public function __toString(): string
    {
        if (empty($this->and)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $this->and);
    }

    public function name(): string
    {
        return self::WHERE;
    }

    public function and(string $predicate, $bindings = [])
    {
        $this->and[] = $predicate;

        if (!empty($bindings)) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }

        return $this;
    }

    public function andPredicate(Predicate $predicate)
    {
        return $this->and($predicate->__toString(), $predicate->bindings());
    }


    public function andIsNull($expression)
    {
        return $this->and(new Predicate($expression, 'IS NULL'));
    }

    public function andFields(array $assoc_data, $table_name = null, $operator = '=')
    {
        foreach ($assoc_data as $field => $value) {
            $column = $table_name === null ? [$field] : [$table_name, $field];
            $predicate = (new Predicate($column, $operator))->withValue($value, __FUNCTION__);

            $this->and($predicate, $predicate->bindings());
        }

        return $this;
    }

    public function andIn($expression, array $values)
    {
        return $this->andPredicate((new Predicate($expression, 'IN'))->withValues($values, __FUNCTION__));
    }
}
