<?php

namespace HexMakina\Crudites\Grammar;

/**
 * An abstract class representing a SQL predicate.
 * Instantiate and __toString().
 *
 * @package HexMakina\Crudites\Grammar\Query
 */
class Predicate extends Grammar
{
    protected $left;
    protected ?string $operator = null;
    protected $right = null;

    protected ?string $bind_label = null;
    
    protected array $bindings = [];

    /**
     * Predicate constructor.
     * The constructor takes a left side expression, an operator, and a right side expression.
     * The left and right side expressions can be either strings or arrays.
     * If an array is provided, the first element is the table name and the second element is the column name.
     * The constructor also adds backticks to the column names.
     * 
     *
     * @param mixed $left, left side expression
     * @param string $operator, operator to use
     * @param mixed $right, right side expression
     */
    public function __construct($left, string $operator = null, $right = null)
    {
        $this->left = $left;
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
        $left = is_array($this->left) ? self::identifier($this->left) : ($this->left ?? '');
        $right = is_array($this->right) ? self::identifier($this->right) : ($this->right ?? '');
        return trim(sprintf('%s %s %s', $left, $this->operator ?? '', $right));
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


    public function withValue($value, string $bind_prefix = null): self
    {
        $this->bindings[$this->bindLabel($bind_prefix)] = $value;
        $this->right = ':' . $this->bindLabel($bind_prefix);
        return $this;
    }

    public function withValues(array $values, string $bind_prefix)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('PREDICATE_VALUES_ARE_EMPTY');
        }

        $bind_label = $this->bindLabel($bind_prefix);
        foreach ($values as $index => $val) {
            $this->bindings[sprintf('%s_%d',$bind_label, $index)] = $val;
        }

        $this->operator = 'IN';
        $this->right = '(:' . implode(',:', array_keys($this->bindings)) . ')';

        return $this;
    }

    public function isNotEmpty(): self
    {
        $res = is_array($this->left) ? self::identifier($this->left) : $this->left;
        $this->left = sprintf("(%s IS NOT NULL AND %s <> '')", $res, $res);
        $this->operator = null;
        $this->right = null;

        return $this;
    }

    public function isEmpty(): self
    {
        $res = is_array($this->left) ? self::identifier($this->left) : $this->left;
        $this->left = sprintf("(%s IS NULL OR %s = '')", $res, $res);
        $this->operator = null;
        $this->right = null;

        return $this;
    }


    /**
     * Gets the binding label for the predicate.
     *
     * @return string The binding label for the predicate.
     */
    private function bindLabel(string $prefix = null): string
    {
        if ($this->bind_label !== null)
            return $this->bind_label;

        if (is_string($this->right)) {
            $this->bind_label = $this->right;
        } elseif (is_array($this->left)) {
            $this->bind_label = implode('_', $this->left);
        } else {
            $this->bind_label = $this->left;
        }

        if ($prefix !== null)
            $this->bind_label = $prefix . '_' . $this->bind_label;

        return $this->bind_label;
    }
}
