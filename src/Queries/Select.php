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
        return $forced_value ?? $this->table_alias ?? $this->tableName();
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

    public function selectAlso($setter): self
    {
        if (is_null($setter))
            return $this->columns;


        if (is_string($setter))
            $setter = explode(',', $setter);


        if (!is_array($setter))
            $setter = [];

        foreach ($setter as $alias => $columnInfo) {
            
            $table = $this->tableLabel();
            $column = null;
            
            if (is_int($alias)) {
                $column = $columnInfo;
                $alias = null;

            } elseif (is_array($columnInfo)) {
                if (isset($columnInfo[1])) {
                    [$table, $column] = $columnInfo;
                } else {
                    $table = -1;
                    $column = $columnInfo[0];
                }
            }
            else {
                $column = $columnInfo;
            }

            $this->addColumn($column, $alias, $table);
        }

        return $this;
    }

    public function addColumn(string $name, string $alias = null, string $table = null)
    {
        if(empty($table))
            $table = -1;

        $this->columns[$table][] = empty($alias) ? $name : [$name, $alias];
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
     * @param $clause, cannot be empty, must be an array or a string
     * 
     * if array, it must adhere to this format
     *       [0] => field
     *       [1] => direction (null, default ASC)
     *       [2] => table (null, default tableLabel())
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

    public function generate(): string
    {
        if (is_null($this->table)) {
            throw new CruditesException('NO_TABLE');
        }

        $this->table_alias ??= '';

        $ret = PHP_EOL . 'SELECT ' . implode(', ' . PHP_EOL, $this->generateSelectColumns());

        $ret .= PHP_EOL . sprintf(' FROM `%s`', $this->tableLabel());
        if ($this->tableName() !== $this->table_alias)
            $ret .= ' ' . $this->table_alias;

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
        $query_fields = $this->columns();

        $ticked = [];

        foreach ($query_fields as $table => $columns) {

            if(in_array('*', $columns)) {
                $ticked [] = sprintf('`%s`.*', $this->tableLabel(), '*');
                continue;
            }
            elseif(is_int($table)){
                // GROUP_CONCAT, SUM, COUNT, etc
                foreach($columns as $columnInfo){
                    $ticked [] = sprintf('%s AS `%s`', $columnInfo[0], $columnInfo[1]);
            }
            }
            else{
                foreach($columns as $columnInfo){
                    // do we have an alias ?
                    if(is_array($columnInfo)){
                        [$column, $alias] = $columnInfo;
                        $ticked [] = sprintf('%s AS `%s`', $this->backTick($column, $table), $alias);

                    }
                    else{
                        $ticked [] = $this->backTick($columnInfo, $table);
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
