<?php

namespace HexMakina\Crudites\Queries\Predicates;

use HexMakina\Crudites\Queries\Grammar;

/**
 * An abstract class representing a SQL predicate.
 * Instantiate and __toString().
 *
 * @package HexMakina\Crudites\Queries
 */
class Predicate extends Grammar
{
    /**
     * @var mixed The column involved in the predicate.
     */
    protected $column = null;

    /**
     * @var string The operator used in the predicate.
     */
    protected $operator = null;

    protected $right = null;


    /**
     * @var string|null The label used for binding parameters. 
     */
    protected $bind_label = null;

    /**
     * @var array The bindings for the predicate
     */
    protected $bindings = [];

    /**
     * Predicate constructor.
     *
     * @param mixed $column The column involved in the predicate.
     * @param string $operator The operator used in the predicate.
     */
    public function __construct($column, string $operator = null, string $right = null)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->right = $right;
    }

    /**
     * Converts the predicate to a string.
     *
     * @return string The string representation of the predicate.
     */
    public function __toString()
    {
        return sprintf('%s %s %s', $this->left(), $this->operator ?? '', $this->right());
    }

    /**
     * Gets the left-hand side of the predicate.
     *
     * @return string The left-hand side of the predicate.
     */
    protected function left(): string
    {
        return self::backtick($this->column);
    }

    /**
     * Gets the right-hand side of the predicate.
     * By default, this is the binding label.
     *
     * @return string The right-hand side of the predicate.
     */
    
    protected function right(): string
    {
        return $this->right ?? $this->bindLabel();
    }

    /**
     * Gets the bindings for the predicate.
     *
     * @return array The bindings for the predicate.
     */
    public function bindings(): array
    {
        return $this->bindings ?? [];
    }

    /**
     * Gets the binding label for the predicate.
     *
     * @return string The binding label for the predicate.
     */
    public function bindLabel(string $prefix = ''): string
    {
        if($this->bind_label === null){
            $label = is_array($this->column) ? $this->column : [$this->column];
            array_unshift($label, $prefix);
            $this->bind_label = implode('_', $label);

        }
            
        return $this->bind_label;
    }

    public function withValue($value, string $bind_prefix = null): self
    {
        $bind_label = $this->bindLabel($bind_prefix);
        
        $this->right = sprintf(':%s', $bind_label);
        $this->bindings[$bind_label] = $value;
        
        return $this;
    }

    public function withValues(array $values, string $bind_prefix)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('PREDICATE_VALUES_ARE_EMPTY');
        }

        $bind_label = $this->bindLabel();
        foreach ($values as $index => $val) {
            $this->bindings[sprintf('%s_%s_%d', $bind_prefix, $bind_label, $index)] = $val;
        }
        
        $this->operator = 'IN';
        $this->right = '(:'.implode(',:', array_keys($this->bindings)).')';

        return $this;
    }

    public function withColumn($column): self
    {
        $this->right = self::backtick($column);
        return $this;
    }

}
