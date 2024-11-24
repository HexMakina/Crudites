<?php

namespace HexMakina\Crudites\Queries\Predicates;

/**
 * An abstract class representing a SQL predicate.
 * Instantiate and __toString().
 *
 * @package HexMakina\Crudites\Queries
 */
class Predicate
{
    /**
     * @var mixed The column involved in the predicate.
     */
    protected $column = null;

    /**
     * @var string The operator used in the predicate.
     */
    protected $operator = null;

    /**
     * @var string|null The label used for binding parameters. 
     */
    protected $binding_label = null;

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
    public function __construct($column, string $operator = null)
    {
        $this->column = $column;
        $this->operator = $operator;
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
        return $this->bindingLabel();
    }

    /**
     * Gets the bindings for the predicate.
     *
     * @return array The bindings for the predicate.
     */
    public function getBindings(): array
    {
        return $this->bindings ?? [];
    }

    /**
     * Gets the binding label for the predicate.
     *
     * @return string The binding label for the predicate.
     */
    public function bindingLabel(): string
    {
        return $this->binding_label ?? is_array($this->column) ? implode('_', $this->column) : $this->column;
    }

    /**
     * Adds backticks to a reference.
     *
     * @param mixed $reference The reference to add backticks to.
     * @return string The reference with backticks added.
     */
    protected static function backtick($reference): string
    {
        if (is_array($reference)) {
            return sprintf('`%s`.`%s`', array_shift($reference), array_shift($reference));
        }
        return sprintf('`%s`', $reference);
    }


}
