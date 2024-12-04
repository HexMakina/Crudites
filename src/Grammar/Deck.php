<?php

namespace HexMakina\Crudites\Grammar;

class Deck extends Grammar
{
    // all the columns, functions and expressions to be aggregated
    private string $aggregates;

    /**
     * If the aggregate is a string, it will not be altered (functions, expressions, etc)
     * If the aggregate is an array, 
     *  - [column], will be backticked as `column`
     *  - [table, column], will be backticked as `table`.`column`
     * @param $aggregate, 
     * @param string $alias, if set, will be backticked
     * 
     */
    public function __construct($aggregate, string $alias = null)
    {
        $this->aggregates = $this->format($aggregate, $alias);
    }

    public function add($aggregate, string $alias = null): self
    {
        return $this->addRaw($this->format($aggregate, $alias));
    }

    public function addRaw(string $raw): self
    {
        $this->aggregates .= ',' . $raw;
        return $this;
    }

    public function __toString(): string
    {
        return $this->aggregates;
    }


    protected function format($aggregate, string $alias = null): string
    {
        $ret = is_string($aggregate) ? $aggregate : self::identifier($aggregate);

        if ($alias !== null) {
            $ret .= ' AS ' . self::identifier($alias);
        }

        return $ret;
    }
}
