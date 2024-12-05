<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Predicate;

class Set extends Clause
{
    protected array $alterations = [];

    public function __construct(array $alterations)
    {
        foreach ($alterations as $field_name => $value) {
            $predicate = (new Predicate([$field_name], '='))->withValue($value, 'set');
            $this->alterations []= $predicate->__toString();
            $this->bindings += $predicate->bindings();
        }
    }

    public function __toString(): string
    {
        return 'SET ' . implode(', ', $this->alterations);
    }

    public function name(): string
    {
        return self::SET;
    }
}