<?php

namespace HexMakina\Crudites\Grammar;

class Grammar
{

    /**
     * Changes a column parameter to a string.
     *
     * @param mixed $column The column parameter to change.
     * @return string The column parameter as a string.
     */
    protected static function selected($column): string
    {
        return is_string($column) ? $column : self::backtick($column);
    }

    /**
     * Adds backticks to a reference.
     *
     * @param string|array $reference The reference to add backticks to.
     * @return string The reference with backticks added.
     */
    public static function backtick(string|array $reference): string
    {
        if (is_array($reference)) {
            return sprintf('`%s`.`%s`', array_shift($reference), array_shift($reference));
        }
        return sprintf('`%s`', $reference);
    }

}
