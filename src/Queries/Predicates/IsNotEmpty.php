<?php

namespace HexMakina\Crudites\Queries\Predicates;

/**
 * Class IsNotEmpty
 * 
 * Allows to check if a column is not empty, meaning it is neither NULL nor an empty string.
 * 
 * @package HexMakina\Crudites\Queries\Predicates
 */

class IsNotEmpty extends Predicate
{
    public function __toString()
    {
        $res = self::backtick($this->column);
        return sprintf("(%s IS NOT NULL AND %s <> '')", $res, $res);
    }
}