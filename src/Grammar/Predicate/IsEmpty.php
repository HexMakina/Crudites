<?php

namespace HexMakina\Crudites\Grammar\Predicate;

/**
 * Class IsEmpty
 * 
 * Allows to check if a column is empty, meaning it is either NULL or an empty string.
 * 
 */
class IsEmpty extends Predicate
{
    public function __toString()
    {
        $res = self::backtick($this->column);
        return sprintf("(%s IS NULL OR %s = '')", $res, $res);
    }
}
