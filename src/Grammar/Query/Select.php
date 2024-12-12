<?php

namespace HexMakina\Crudites\Grammar\Query;


use HexMakina\Crudites\Grammar\Grammar;
use HexMakina\Crudites\Grammar\Deck;

use HexMakina\Crudites\Grammar\Clause\Clause;
use HexMakina\Crudites\Grammar\Clause\Where;
use HexMakina\Crudites\Grammar\Clause\Joins;
use HexMakina\Crudites\Grammar\Clause\Join;
use HexMakina\Crudites\Grammar\Clause\GroupBy;
use HexMakina\Crudites\Grammar\Clause\OrderBy;
use HexMakina\Crudites\Grammar\Clause\Limit;

class Select extends Query
{
    // decks handle the list of columns and expressions to be selected
    private ?Deck $deck = null;

    public function __construct(array $selection, string $table, $table_alias = null)
    {
        $this->table = $table;
        $this->alias = $table_alias;
        $this->selectAlso($selection);
    }

    public function statement(): string
    {
        $schema = Grammar::identifier($this->table());
        if (!empty($this->alias())) {
            $schema .= ' ' . Grammar::identifier($this->alias());
        }

        $ret = sprintf('SELECT %s FROM %s', $this->deck, $schema);

        foreach (
            [
                Clause::JOINS,
                Clause::JOIN,
                Clause::WHERE,
                Clause::GROUP,
                Clause::HAVING,
                Clause::ORDER,
                Clause::LIMIT
            ] as $clause
        ) {
            if($this->clause($clause) === null)
                continue;

            $ret .= ' ' . $this->clause($clause);
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

    public function where(array $predicates): Where
    {
        $where = new Where($predicates);
        $this->add($where);
        return $where;
    }

    public function join(string $table, ?string $alias=null): Join
    {
        $join = new Join($table, $alias);

        if($this->clause(Clause::JOINS) === null){
            $joins = new Joins([$join]);
            $this->add($joins);
        }
        else{
            $this->clause(Clause::JOINS)->add($join);
        }

        return $join;
    }

    public function groupBy($selected): GroupBy
    {
        $group = new GroupBy($selected);
        $this->add($group);
        return $group;
    }

    public function orderBy($selected, string $direction): OrderBy
    {
        $order = new OrderBy($selected, $direction);
        $this->add($order);
        return $order;
    }

    public function limit(int $number, int $offset = 0): Limit
    {
        $limit = new Limit($number, $offset);
        $this->add($limit);
        return $limit;
    }
}
