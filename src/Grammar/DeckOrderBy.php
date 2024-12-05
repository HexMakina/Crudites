<?php

namespace HexMakina\Crudites\Grammar;

class DeckOrderBy extends Deck
{

    // Deck uses AS for alias, DeckOrderBy uses ASC/DESC for direction
    protected function format($aggregate, string $direction = null): string
    {
        return (string)(new Predicate($aggregate, $direction));
    }
}
