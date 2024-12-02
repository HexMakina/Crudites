<?php

namespace HexMakina\Crudites\Grammar\Query;


use HexMakina\Crudites\CruditesException;

// use HexMakina\Crudites\Grammar\Clause\SelectFrom;
use HexMakina\Crudites\Grammar\Deck;
use HexMakina\Crudites\Grammar\Clause\Clause;
use HexMakina\Crudites\Grammar\Grammar;

class Select extends Query
{
    private ?Deck $deck = null;
    public function __construct(array $columns, string $table, $table_alias = null)
    {
        
        $this->table = $table;
        $this->table_alias = $table_alias;
        // die('vefore selftform');
        // $this->add(new SelectFrom($table, $table_alias));
        // die('SELECT');
        $this->selectAlso($columns);
    }

    public function statement(): string
    {
        if ($this->table === null) {
            throw new CruditesException('NO_TABLE');
        }

        $schema = Grammar::backtick($this->table);
        if (!empty($this->alias)) {
            $schema .= ' AS ' . Grammar::backtick($this->alias);
        }

        $ret = sprintf('SELECT %s FROM %s', $this->deck, $schema);

        foreach (
            [
                Clause::JOINS,
                Clause::WHERE,
                Clause::GROUP,
                Clause::HAVING,
                Clause::ORDER,
                Clause::LIMIT
            ] as $clause
        ) {
            if($this->clause($clause) === null)
                continue;

            $ret .= PHP_EOL . $this->clause($clause);
        }

        return $ret;
    }
    
    public function tableLabel($forced_value = null)
    {
        return $forced_value ?? $this->table_alias ?? $this->table;
    }

    /**
     * Adds additional columns to the SELECT statement.
     *
     * @param array $setter An array of column names to be added to the SELECT statement.
     *
     *   $setter = [
     *       'column_alias' => 'column',
     *       2 => 'column',
     *       'table_column_alias' => ['table', 'column'],
     *       5 => ['table', 'column'],
     *       'function_alias' => ['GROUP_CONCAT(..)'],
     *       6 => ['GROUP_CONCAT(..)'],
     *   ];
     * 
     * @return self Returns the current instance of the Select class.
     */
    public function selectAlso(array $setter): self
    {
        if (empty($setter))
            throw new \InvalidArgumentException('EMPTY_SETTER_ARRAY');

        foreach ($setter as $alias => $column) {

            if (is_int($alias)) {
                $alias = null;
            }
            if(!isset($this->deck)){
                $this->deck = new Deck($column, $alias);
            } else {
                $this->deck->add($column, $alias);
            }
        }

        return $this;
    }
}
