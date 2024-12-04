<?php

namespace HexMakina\Crudites\Grammar;

class Grammar
{
    /**
     * Adds backticks to a reference.
     *
     * @param $reference The reference to add backticks to.
     * @return string The reference with backticks added.
     */

    public static function rawOrBacktick($reference): string
    {
        return is_string($reference) ? $reference : self::identifier($reference);
    }

    public static function identifier($reference): string
    {
        if (is_array($reference)) {
            $tick = $reference[0];
            if (isset($reference[1])) {
                $tick = $tick . '`.`' . $reference[1];
            }
            $reference = $tick;
        }

        return sprintf('`%s`', $reference);
    }
}
