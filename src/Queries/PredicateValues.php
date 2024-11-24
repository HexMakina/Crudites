<?php

namespace HexMakina\Crudites\Queries;

class PredicateValues extends Predicate
{
    public function __construct($column, $operator, array $values, string $binding_prefix)
    {
        parent::__construct($column, $operator);

        foreach ($values as $index => $val) {
            $this->bindings[sprintf('%s_%s_%d', $binding_prefix, $this->bindingLabel(), $index)] = $val;
        }
    }

    protected function right(): string
    {
        $binding_names = array_keys($this->getBindings());
        return '(:'.implode(',:', $binding_names).')';
    }

    public function getBindings(): array
    {
        return $this->bindings ?? [];
    }
}
