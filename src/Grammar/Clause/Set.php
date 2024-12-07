<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Predicate;

class Set extends Clause
{
    protected string $alterations = '';

    public function __construct(array $alterations)
    {
        foreach ($alterations as $field_name => $value) {
            $predicate = (new Predicate([$field_name], '='))->withValue($value, 'set_'.$field_name);
            
            $this->alterations .= $predicate->__toString().',';
            $this->bindings = array_merge($this->bindings, $predicate->bindings());
        }
    }

    public function __toString(): string
    {
        return 'SET ' . rtrim($this->alterations, ',');
    }

    public function name(): string
    {
        return self::SET;
    }
}