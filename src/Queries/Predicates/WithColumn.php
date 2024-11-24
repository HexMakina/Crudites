<?php

namespace HexMakina\Crudites\Queries\Predicates;

/**
 * Represents a predicate that compares two columns in a SQL query.
 */
class WithColumn extends Predicate
{
    /**
     * @var string|array The right column to be compared.
     */
    private $right_column;

    /**
     * PredicateColumn constructor.
     *
     * @param string|array $left_column The left column to be compared.
     * @param string $operator The operator used for comparison (e.g., '=', '>', '<').
     * @param string|array $right_column The right column to be compared.
     */
    public function __construct($left_column, $operator, $right_column)
    {
        parent::__construct($left_column, $operator);
        $this->right_column = $right_column;
    }

    /**
     * Returns the right column wrapped in backticks for SQL syntax.
     *
     * @return string The right column wrapped in backticks.
     */
    protected function right(): string
    {
        return self::backtick($this->right_column);
    }
}
