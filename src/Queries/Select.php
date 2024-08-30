<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\{TableInterface, SelectInterface};
use HexMakina\Crudites\CruditesException;

class Select extends PreparedQuery implements SelectInterface
{
    use ClauseJoin;
    use ClauseWhere;

    protected $columns;
    protected $limit;

    protected $limit_number;

    protected $limit_offset = 0;


    public function __construct($columns = null, TableInterface $table = null, $table_alias = null)
    {
        $this->table = $table;
        $this->table_alias = $table_alias;

        $this->columns($columns);
    }

    public function tableLabel($forced_value = null)
    {
        return $forced_value ?? $this->table_alias ?? $this->table()->name();
    }

    public function columns($setter = null): array
    {
        if (!is_null($setter))
        {
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

            $table = null;

            // no alias provided, $setter = [0 => 'firstname']
            if (is_int($alias)) { 
                $alias = null;
            }
            
            if (is_array($column)) {

                if (isset($column[1])) {
                    [$table, $column] = $column;
                } 
                else {
                    $table = '';
                    $column = $column[0];
                }
            }
            else {
                $column = $column;
            }

            $this->addColumn($column, $alias, $table ?? $this->tableLabel());
        }

        return $this;
    }

    public function addColumn(string $name, string $alias = null, string $table = null)
    {
        if(empty($table))
            $table = -1;

        if(empty($alias))
            $alias = $name;

        $this->columns[$table][$alias] = $name;
        // $this->columns[$table][] = empty($alias) ? $name : [$name, $alias];
    }

    public function groupBy($clause): self
    {
        $groupBy = null;
        if (is_string($clause)) {
            $groupBy = $this->backTick($clause, $this->tableLabel());
        } elseif (is_array($clause)) {
            if (isset($clause[1])) { // 0: table, 1: field            
                $groupBy = $this->backTick($clause[1], $clause[0]);
            } else { // 0: field            
                $groupBy = $this->backTick($clause[0], null);
            }
        }

        return $this->addClause('group', $groupBy);
    }

    public function having($condition)
    {
        return $this->addClause('having', $condition);
    }

    /**
    /**
     * Selects data from the database based on the given clause.
     *
     * @param array|string $clause The clause used to filter the data. If an array is provided, it will be stringified and backticked.
     * 
     * array structure:
     *       [0] => column
     *       [1] => direction (optional, default ASC)
     *       [2] => table (optional, default tableLabel())
     * 
     */ 
    public function orderBy($clause): self
    {
        if(empty($clause) || (!is_array($clause) && !is_string($clause)))
            throw new \InvalidArgumentException('ORDER_BY_INVALID_CLAUSE');

        if (is_array($clause)) {

            $column     = $clause[0];
            $direction  = $clause[1] ?? 'ASC';
            $table      = $clause[2] ?? $this->tableLabel();
            
            $clause =  sprintf('%s %s', $this->backTick($column, $table), $direction);
        }

        $this->addClause('orderBy', $clause);

        return $this;
    }

    public function limit($number, $offset = null): self
    {
        $this->limit_number = $number;
        $this->limit_offset = $offset;

        return $this;
    }

    public function statement(): string
    {
        if (is_null($this->table)) {
            throw new CruditesException('NO_TABLE');
        }

        $this->table_alias ??= '';

        $ret = PHP_EOL . 'SELECT ' . implode(', ' . PHP_EOL, $this->generateSelectColumns());

        $ret .= PHP_EOL . sprintf(' FROM `%s`', $this->table()->name());
        if ($this->table()->name() !== $this->tableLabel())
            $ret .= ' ' . $this->tableLabel();

        if (!empty($this->clause('join'))) {
            $ret .= PHP_EOL . ' ' . implode(PHP_EOL . ' ', $this->clause('join'));
        }

        $ret .= $this->generateWhere();


        foreach (['group' => 'GROUP BY', 'having' => 'HAVING', 'orderBy' => 'ORDER BY'] as $part => $prefix) {
            if (!empty($this->clause($part))) {
                $ret .= PHP_EOL . sprintf(' %s ', $prefix) . implode(', ', $this->clause($part));
            }
        }

        if (!empty($this->limit_number)) {
            $ret .= PHP_EOL . sprintf(' LIMIT %s OFFSET %s', $this->limit_number, $this->limit_offset ?? 0);
        }


        return $ret;
    }

    private function generateSelectColumns()
    {
        $ticked = [];

        foreach ($this->columns() as $table => $columns) {

            if(in_array('*', $columns)) {
                $ticked [] = sprintf('`%s`.*', $this->tableLabel(), '*');
                continue;
            }
            elseif(is_int($table)){
                // GROUP_CONCAT, SUM, COUNT, etc
                foreach($columns as $alias => $columnInfo){
                    $ticked [] = sprintf('%s AS `%s`', $columnInfo, $alias);
                }
            }
            else{
                foreach($columns as $alias => $name){
                    // do we have an alias ?
                    if($alias !== $name){
                        $ticked [] = sprintf('%s AS `%s`', $this->backTick($name, $table), $alias);
                    }
                    else{
                        $ticked [] = $this->backTick($name, $table);
                    }
                }
            }
        }

        return $ticked;
    }
    
    //------------------------------------------------------------ SELECT:FETCHING RESULT

    public function retObj($c = null)
    {
        return is_null($c) ? $this->ret(\PDO::FETCH_OBJ) : $this->ret(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $c);
    }

    public function retNum()
    {
        return $this->ret(\PDO::FETCH_NUM);
    }

    //ret:
    public function retAss()
    {
        return $this->ret(\PDO::FETCH_ASSOC);
    }

    //ret: array indexed by column name
    public function retCol()
    {
        return $this->ret(\PDO::FETCH_COLUMN);
    }

    //ret: all values of a single column from the result set
    public function retPar()
    {
        return $this->ret(\PDO::FETCH_KEY_PAIR);
    }

    public function retKey()
    {
        return $this->ret(\PDO::FETCH_UNIQUE);
    }
}
