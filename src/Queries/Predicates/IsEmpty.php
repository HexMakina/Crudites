<?php

namespace HexMakina\Crudites\Queries\Predicates;

/**
 * Class IsEmpty
 * 
 * Allows to check if a column is empty, meaning it is either NULL or an empty string.
 * 
 * @package HexMakina\Crudites\Queries\Predicates
 */
class IsEmpty extends Predicate
{
    public function __toString()
    {
        $res = self::backtick($this->column);
        return sprintf("(%s IS NULL OR %s = '')", $res, $res);
    }
}
