<?php

namespace HexMakina\Crudites\Queries;

class Grammar
{
    /**
     * Adds backticks to a reference.
     *
     * @param mixed $reference The reference to add backticks to.
     * @return string The reference with backticks added.
     */
    public static function backtick($reference): string
    {
        if (is_array($reference)) {
            return sprintf('`%s`.`%s`', array_shift($reference), array_shift($reference));
        }
        return sprintf('`%s`', $reference);
    }

}
