<?php

namespace HexMakina\Crudites\Grammar;

class DeckOrderBy extends Deck
{

    // Deck uses AS for alias, DeckOrderBy uses ASC/DESC for direction
    protected function format(string|array $aggregate, string $direction = null): string
    {
        $ret = is_string($aggregate) ? $aggregate : self::backtick($aggregate);
        $ret .= ' ' . in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';

        return $ret;
    }
}
