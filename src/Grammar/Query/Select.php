<?php

namespace HexMakina\Crudites\Queries;


use HexMakina\Crudites\CruditesException;

use HexMakina\Crudites\Grammar\Clause\SelectFrom;
use HexMakina\Crudites\Grammar\Clause\Clause;

class Select extends Query
{
    protected $columns;
    protected SelectFrom $selectFrom;

    public function __construct($columns = null, string $table = null, $table_alias = null)
    {
        $this->table = $table;
        $this->table_alias = $table_alias;
        $this->add(new SelectFrom($table, $table_alias));
        $this->columns($columns);
    }

    public function statement(): string
    {
        if ($this->table === null) {
            throw new CruditesException('NO_TABLE');
        }

        $ret = '';
        foreach (
            [
                Clause::SELECT,
                Clause::JOINS,
                Clause::WHERE,
                Clause::GROUP,
                Clause::HAVING,
                Clause::ORDER,
                Clause::LIMIT
            ] as $clause
        ) {

            $ret .= PHP_EOL . $this->clause($clause);
        }

        return $ret;
    }
    
    public function tableLabel($forced_value = null)
    {
        return $forced_value ?? $this->table_alias ?? $this->table;
    }

    public function columns($setter = null): array
    {
        if ($setter !== null) {
            $this->columns = [];
            $this->selectAlso($setter);
        }

        return $this->columns ?? [];
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

            $this->selectFrom->add($column, $alias);
        }

        return $this;
    }


}
