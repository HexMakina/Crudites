<?php

namespace HexMakina\Crudites\Queries\Predicates;

/**
 * Represents a predicate with multiple values for use in SQL queries.
 */
class WithValues extends Predicate
{
    public function __construct($column, $operator, array $values, string $binding_prefix)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('PREDICATE_VALUES_ARE_EMPTY');
        }
        
        parent::__construct($column, $operator);

        foreach ($values as $index => $val) {
            $this->bindings[sprintf('%s_%s_%d', $binding_prefix, $this->bindLabel(), $index)] = $val;
        }
    }

    protected function right(): string
    {
        $binding_names = array_keys($this->getBindings());
        return '(:'.implode(',:', $binding_names).')';
    }
}
