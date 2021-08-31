<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\Interfaces\TableManipulationInterface;
use \HexMakina\Crudites\Interfaces\SelectInterface;
use \HexMakina\Crudites\CruditesException;

class Select extends BaseQuery implements SelectInterface
{
    use ClauseJoin;
    use ClauseWhere;

    protected $selection = [];
    protected $table_alias = null;
    protected $join = [];

    protected $group = [];
    protected $having = [];
    protected $order = [];

    protected $limit = null;
    protected $limit_number = null;
    protected $limit_offset = 0;


    public function __construct($select_fields = null, TableManipulationInterface $table = null, $table_alias = null)
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

    public function table_label($forced_value = null)
    {
        return $forced_value ?? $this->table_alias ?? $this->table_name();
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

    public function select_also($setter)
    {
        $this->selection = array_merge($this->selection, is_array($setter) ? $setter : [$setter]);
        return $this;
    }

    public function select_less($column_alias)
    {

        foreach ($column_alias as $alias) {
            foreach ($this->selection as $i => $column_and_alias) {
                if (strpos($column_and_alias, $alias) === 0) {
                    unset($this->selection[$i]);
                    break;
                }
            }
        }
        return $this;
    }

    private function add_part($group, $part)
    {
        $this->$group = $this->$group ?? [];
        array_push($this->$group, $part);
        return $this;
    }

    public function join_raw($sql)
    {
        return $this->add_part('join', $sql);
    }

    public function table_alias($setter = null)
    {
        if (!is_null($setter)) {
            $this->table_alias = $setter;
        }

        return $this->table_alias ?? $this->table_name();
    }

    public function group_by($clause)
    {
        if (is_string($clause)) {
            $this->add_part('group', $this->field_label($clause, $this->table_label()));
        } elseif (is_array($clause)) {
            if (isset($clause[1])) { // 0: table, 1: field
                $this->add_part('group', $this->field_label($clause[1], $clause[0]));
            } else { // 0: field
                $this->add_part('group', $this->field_label($clause[0], null));
            }
        }

        return $this;
    }

    public function having($condition)
    {
        return $this->add_part('having', $condition);
    }

    public function order_by($clause)
    {
        if (is_string($clause)) {
            $this->add_part('order', $clause);
        } elseif (is_array($clause) && count($clause) > 1) {
            if (isset($clause[2])) { // 0:table, 1:field, 2:direction
                $this->add_part('order', sprintf('%s %s', $this->field_label($clause[1], $clause[0]), $clause[2]));
            } elseif (isset($clause[1])) { // 0: field, 1: direction
                $this->add_part('order', sprintf('%s %s', $this->field_label($clause[0], $this->table_label()), $clause[1]));
            }
        }

        return $this;
    }

    public function limit($number, $offset = null)
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

        $this->table_alias = $this->table_alias ?? '';

        $query_fields = empty($this->selection) ? ['*'] : $this->selection;

        $ret = PHP_EOL . 'SELECT ' . implode(', ' . PHP_EOL, $query_fields);
        $ret .= PHP_EOL . sprintf(' FROM `%s` %s ', $this->table_name(), $this->table_alias);

        if (!empty($this->join)) {
            $ret .= PHP_EOL . ' ' . implode(PHP_EOL . ' ', $this->join);
        }

        $ret .= $this->generate_where();

        foreach (['group' => 'GROUP BY', 'having' => 'HAVING', 'order' => 'ORDER BY'] as $part => $prefix) {
            if (!empty($this->$part)) {
                $ret .= PHP_EOL . " $prefix " . implode(', ', $this->$part);
            }
        }

        if (!empty($this->limit_number)) {
            $offset = $this->limit_offset ?? 0;
            $number = $this->limit_number;
            $ret .= PHP_EOL . " LIMIT $offset, $number";
        }

        return $ret;
    }

    //------------------------------------------------------------ SELECT:FETCHING RESULT

    public function ret_obj($c = null)
    {
        return is_null($c) ? $this->ret(\PDO::FETCH_OBJ) : $this->ret(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $c);
    }

    public function ret_num()
    {
        return $this->ret(\PDO::FETCH_NUM);
    } //ret:
    public function ret_ass()
    {
        return $this->ret(\PDO::FETCH_ASSOC);
    } //ret: array indexed by column name
    public function ret_col()
    {
        return $this->ret(\PDO::FETCH_COLUMN);
    } //ret: all values of a single column from the result set
    public function ret_par()
    {
        return $this->ret(\PDO::FETCH_KEY_PAIR);
    }
    public function ret_key()
    {
        return $this->ret(\PDO::FETCH_UNIQUE);
    }
}
