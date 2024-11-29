<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\Grammar\Predicate\{Predicate, IsNotEmpty, IsEmpty, WithValue, WithValues};
trait ClauseWhere
{
    public static $OP_AND = 'AND';

    public static $OP_OR = 'OR';

    public static $WHERE_LIKE_PRE = '%TERM';

    public static $WHERE_LIKE_POST = 'TERM%';

    public static $WHERE_LIKE_BOTH = '%TERM%';

    protected $where;

    abstract public function tableLabel($table_name = null);

    abstract public function backTick($field, $table_name = null);

    abstract public function addBinding($field, $value, $table_name, $bind_label = null): string;

    public function where($where_condition, $bindings = [])
    {
        $this->where ??= [];
        $this->where[] = sprintf('(%s)', $where_condition);

        if(!empty($bindings)){
            $this->bindings = array_merge($this->bindings, $bindings);
        }

        return $this;
    }

    private function wherePredicate(Predicate $predicate)
    {
        return $this->where($predicate->__toString(), $predicate->getBindings());
    }

    public function whereWithBind($where)
    {
        $this->where ??= [];
        $this->where[] = sprintf('(%s)', $where);
        return $this;
    }
    public function whereBindField($table_name, $field, $operator, $value, $bind_name = null)
    {
        $bind_name = $this->addBinding($field, $value, $table_name, $bind_name);
        return $this->whereField($field, sprintf('%s %s', $operator, $bind_name), $table_name);
    }


    public function whereEqualOrNull($field, $value, $table_name = null, $bindname = null)
    {
        $bind_name = $this->addBinding($field, $value, $table_name, $bindname);
        $field_name = $this->backTick($field, $table_name);

        return $this->where(sprintf('%s = %s OR %s IS NULL', $field_name, $bind_name, $field_name));
    }

    public function whereIn($field, $values, $table_name = null)
    {
        return $this->wherePredicate(new WithValues([$this->tableLabel($table_name), $field], 'IN', $values, 'AWIN'));
    }

    
    public function whereField($field, $condition, $table_name = null)
    {
        $table_field = $this->backTick($field, $table_name);
        return $this->where(sprintf('%s %s', $table_field, $condition));
    }

    public function whereIsNull($field, $table_name = null)
    {
        return $this->where(new Predicate($this->predicateColumn($field, $table_name), 'IS NULL'));
    }

    public function whereNotEmpty($field, $table_name = null)
    {
        return $this->where(new IsNotEmpty($this->predicateColumn($field, $table_name)));
    }

    public function whereEmpty($field, $table_name = null)
    {
        return $this->where(new IsEmpty($this->predicateColumn($field, $table_name)));
    }

    public function whereFieldsEQ($assoc_data, $table_name = null)
    {
        $table_name = $this->tableLabel($table_name);
        foreach ($assoc_data as $field => $value) {
            $p = new WithValue([$table_name, $field], '=', $value);
            $this->wherePredicate($p);
        }

        return $this;
    }

    public function whereEQ($field, $value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), '=', $value, $bindname);
        return $this->wherePredicate($p);
    }

    public function whereGT($field, $value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), '>', $value, $bindname);
        return $this->wherePredicate($p);
    }

    public function whereLT($field, $value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), '<', $value, $bindname);
        return $this->wherePredicate($p);
    }

    private function predicateColumn($column, $table=null): array
    {
        return [$this->tableLabel($table), $column];
    }
    public function whereGTE($field, $value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), '>=', $value, $bindname);
        return $this->wherePredicate($p);
    }

    public function whereLTE($field, $value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), '<=', $value, $bindname);
        return $this->wherePredicate($p);
    }

    public function whereNotEQ($field, $value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), '<>', $value, $bindname);
        return $this->wherePredicate($p);
    }

    public function whereLike($field, $prep_value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), 'LIKE', $prep_value, $bindname);
        return $this->wherePredicate($p);
    }

    public function whereNotLike($field, $prep_value, $table_name = null, $bindname = null)
    {
        $p = new WithValue($this->predicateColumn($field, $table_name), 'NOT LIKE', $prep_value, $bindname);
        return $this->wherePredicate($p);
    }

    /**
     * @param array $filters_content  with 2 indexes: 'term', the search string, 'fields', the search fields
     * @param $search_table     String to filter
     * @param $filters_operator Object, inclusive or exclusive search
     */

    // sub array filters[$content]
    public function whereFilterContent(array $filters_content, $search_table = null, $filters_operator = null)
    {
        if (!isset($filters_content['term'])) {
            return $this;
        }
        if (!isset($filters_content['fields'])) {
            return $this;
        }
        if ($search_table === null) {
            $search_table = $this->tableLabel();
        }

        $search_term = trim($filters_content['term']);
        if ($search_term === '') {
            return $this;
        }

        $content_wc = [];
        foreach ($filters_content['fields'] as $search_field => $search_mode) {
            if (is_numeric($search_field)) {
                $search_field = $search_mode;
                $search_mode = self::$WHERE_LIKE_BOTH;
            }

            $search_field = $this->backTick($search_field, $search_table);

            if ($search_mode === self::$OP_EQ) {
                $content_wc[] = sprintf('%s = \'%s\' ', $search_field, $search_term); // TODO bindthis
            } else // %%
            {
                $pattern = str_replace('TERM', $search_term, $search_mode);
                $content_wc[] = sprintf(' %s LIKE \'%s\' ', $search_field, $pattern); // TODO bindthis
            }
        }

        if (!empty($content_wc)) {
            $operator = self::validWhereOperator($filters_operator, self::$OP_OR);
            $content_wc = implode(sprintf(' %s ', $operator), $content_wc);

            $this->where(sprintf(' (%s) ', $content_wc));
        }
    }

    // //------------------------------------------------------------  FIELDS
    protected static function validWhereOperator($operator, $default)
    {
        $operator = strtoupper(sprintf('%s', $operator));
        $choices = [self::$OP_AND, self::$OP_OR];

        if (in_array($operator, $choices)) {
            return $operator;
        }

        if (in_array($default, $choices)) {
            return $default;
        }

        throw new \Exception('ERR_INVALID_QUERY_OPERATOR');
    }

    protected function generateWhere()
    {
        if (!empty($this->where)) {
            return PHP_EOL . ' WHERE ' . implode(PHP_EOL . ' AND ', $this->where);
        }
    }
}
