<?php

namespace HexMakina\Crudites\Grammar;

class Grammar
{
    /**
     * Surrounds a string with backticks by default
     * @param string|array $reference : a string or an array with 2 elements, the first being the table name and the second the column name
     * @param string $tick : the character to use for surrounding the string
     * @return string : the string surrounded by the tick character
     * 
     */
    public static function identifier($reference, $tick='`'): string
    {
        if (is_array($reference)) {
            $identifier = $reference[0];
            if (isset($reference[1])) {
                $identifier = $identifier.$tick . '.' . $tick.$reference[1];
            }
            $reference = $identifier;
        }

        return sprintf('`%s`', $reference);
    }
}
