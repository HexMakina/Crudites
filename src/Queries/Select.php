<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\Crudites\CruditesException;

class Select extends BaseQuery implements SelectInterface
{
    use ClauseJoin;
    use ClauseWhere;

    protected array $selection = [];

    protected array $join = [];

    protected array $group = [];

    protected array $having = [];

    protected array $order = [];

    protected $limit;

    protected $limit_number;

    protected $limit_offset = 0;


    public function __construct($select_fields = null, TableInterface $table = null, $table_alias = null)
    {
        $this->table = $table;
        $this->table_alias = $table_alias;

        if (is_null($select_fields)) {
            $this->selection = ['*'];
        } elseif (is_string($select_fields)) {
            $this->selection = explode(',', $select_fields);
        } elseif (is_array($select_fields)) {
            $this->selection = $select_fields;
        }
    }

    public function tableLabel($forced_value = null)
    {
        return $forced_value ?? $this->table_alias ?? $this->tableName();
    }

    public function columns($setter = null)
    {
        if (is_null($setter)) {
            return $this->selection;
        }

        if (is_array($setter)) {
            $this->selection = $setter;
        }

        return $this;
    }

    public function selectAlso($setter): self
    {
        $this->selection = array_merge($this->selection, is_array($setter) ? $setter : [$setter]);
        return $this;
    }

    public function groupBy($clause): self
    {
        if (is_string($clause)) {
            $this->addPart('group', $this->backTick($clause, $this->tableLabel()));
        } elseif (is_array($clause)) {
            if (isset($clause[1])) { // 0: table, 1: field
                $this->addPart('group', $this->backTick($clause[1], $clause[0]));
            } else { // 0: field
                $this->addPart('group', $this->backTick($clause[0], null));
            }
        }

        return $this;
    }

    public function having($condition)
    {
        return $this->addPart('having', $condition);
    }

    public function orderBy($clause): self
    {
        if (is_string($clause)) {
            $this->addPart('order', $clause);
        } elseif (is_array($clause) && count($clause) > 1) {
            if (isset($clause[2])) { // 0:table, 1:field, 2:direction
                $this->addPart(
                    'order',
                    sprintf('%s %s', $this->backTick($clause[1], $clause[0]), $clause[2])
                );
            } elseif (isset($clause[1])) { // 0: field, 1: direction
                $this->addPart(
                    'order',
                    sprintf('%s %s', $this->backTick($clause[0], $this->tableLabel()), $clause[1])
                );
            }
        }

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

        $query_fields = empty($this->selection) ? ['*'] : $this->selection;

        $ret = PHP_EOL . 'SELECT ' . implode(', ' . PHP_EOL, $query_fields);
        $ret .= PHP_EOL . sprintf(' FROM `%s` %s ', $this->tableName(), $this->table_alias);

        if (!empty($this->join)) {
            $ret .= PHP_EOL . ' ' . implode(PHP_EOL . ' ', $this->join);
        }

        $ret .= $this->generateWhere();

        foreach (['group' => 'GROUP BY', 'having' => 'HAVING', 'order' => 'ORDER BY'] as $part => $prefix) {
            if (!empty($this->$part)) {
                $ret .= PHP_EOL . sprintf(' %s ', $prefix) . implode(', ', $this->$part);
            }
        }

        if (!empty($this->limit_number)) {
            $offset = $this->limit_offset ?? 0;
            $number = $this->limit_number;
            $ret .= PHP_EOL . sprintf(' LIMIT %s, %s', $offset, $number);
        }

        return $ret;
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
