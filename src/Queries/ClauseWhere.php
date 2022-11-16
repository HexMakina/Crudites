<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\TableInterface;

trait ClauseWhere
{
    public static $OP_AND = 'AND';
    public static $OP_OR = 'OR';

    public static $OP_GT = '>';
    public static $OP_LT = '<';
    public static $OP_EQ = '=';
    public static $OP_GTE = '>=';
    public static $OP_LTE = '<=';
    public static $OP_NEQ = '<>';
    public static $OP_LIKE = 'LIKE';
    public static $OP_NLIKE = 'NOT LIKE';


    public static $WHERE_LIKE_PRE = '%TERM';
    public static $WHERE_LIKE_POST = 'TERM%';
    public static $WHERE_LIKE_BOTH = '%TERM%';

    protected $where = null;

    abstract public function table(TableInterface $setter = null): TableInterface;
    abstract public function tableLabel($table_name = null);
    abstract public function backTick($field, $table_name = null);
    abstract public function addBinding($field, $value, $table_name = null, $bind_label = null): string;

    public function where($where_condition)
    {
        $this->where = $this->where ?? [];

        $this->where[] = "($where_condition)";

        return $this;
    }

    public function whereEqualOrNull($field, $value, $table_name = null, $bindname = null)
    {
        $bind_name = $this->addBinding($field, $value, $table_name, $bindname);
        $field_name = $this->backTick($field, $table_name);

        return $this->where("($field_name = $bind_name OR $field_name IS NULL)");
    }

    public function whereEQ($field, $value, $table_name = null, $bindname = null)
    {
               return $this->whereBindField($table_name, $field, self::$OP_EQ, $value, $bindname);
    }

    public function whereGT($field, $value, $table_name = null, $bindname = null)
    {
               return $this->whereBindField($table_name, $field, self::$OP_GT, $value, $bindname);
    }

    public function whereLT($field, $value, $table_name = null, $bindname = null)
    {
               return $this->whereBindField($table_name, $field, self::$OP_LT, $value, $bindname);
    }

    public function whereGTE($field, $value, $table_name = null, $bindname = null)
    {
               return $this->whereBindField($table_name, $field, self::$OP_GTE, $value, $bindname);
    }

    public function whereLTE($field, $value, $table_name = null, $bindname = null)
    {
              return $this->whereBindField($table_name, $field, self::$OP_LTE, $value, $bindname);
    }

    public function whereNotEQ($field, $value, $table_name = null, $bindname = null)
    {
          return $this->whereBindField($table_name, $field, self::$OP_NEQ, $value, $bindname);
    }

    public function wherePrimary($pk_values)
    {
        $pks = $this->table()->primaryKeysMatch($pk_values);

        if (empty($pks)) {
            $this->where('1=0');
        } else {
            $this->whereFieldsEQ($pks);
        }

        return $this;
    }

    public function whereLike($field, $prep_value, $table_name = null, $bindname = null)
    {
        return $this->whereBindField($table_name, $field, self::$OP_LIKE, $prep_value, $bindname);
    }
    public function whereNotLike($field, $prep_value, $table_name = null, $bindname = null)
    {
        return $this->whereBindField($table_name, $field, self::$OP_NLIKE, $prep_value, $bindname);
    }


    public function whereFieldsEQ($assoc_data, $table_name = null)
    {
        $table_name = $this->tableLabel($table_name);
        foreach ($assoc_data as $field => $value) {
            $this->whereBindField($table_name, $field, self::$OP_EQ, $value);
        }

        return $this;
    }

    private function whereBindField($table_name, $field, $operator, $value, $bind_name = null)
    {
        $bind_name = $this->addBinding($field, $value, $table_name, $bind_name);
        return $this->whereField($field, "$operator $bind_name", $table_name);
    }

    public function whereNumericIn($field, $values, $table_name = null)
    {
        if (is_array($values) && !empty($values)) {
            return $this->whereField($field, sprintf(' IN (%s)', implode(',', $values)), $table_name);
        }

        return $this;
    }

    public function whereStringIn($field, $values, $table_name = null)
    {
        if (is_array($values) && !empty($values)) {
            $count_values = count($values);
            $in = '';
            foreach ($values as $i => $v) {
                // TODO dirty patching. mathematical certainty of uniqueness needed
                $placeholder_name = ':' . $table_name . '_' . $field . '_awS_in_' . $count_values . '_' . $i;
                $this->addBinding($field, $v, null, $placeholder_name);
                $in .= "$placeholder_name,";
            }
            // $this->whereField($field, sprintf(" IN ('%s')", implode("','", $values)), $table_name);
            $this->whereField($field, sprintf(" IN (%s)", rtrim($in, ',')), $table_name);
        }
        return $this;
    }

    public function whereIsNull($field, $table_name = null)
    {
        return $this->whereField($field, 'IS NULL', $table_name);
    }

    public function whereField($field, $condition, $table_name = null)
    {
        $table_field = $this->backTick($field, $table_name);
        return $this->where("$table_field $condition");
    }

    public function whereNotEmpty($field, $table_name = null)
    {
        $table_field = $this->backTick($field, $table_name);
        return $this->where("($table_field IS NOT NULL AND $table_field <> '') ");
    }

    public function whereEmpty($field, $table_name = null)
    {
        $table_field = $this->backTick($field, $table_name);
        return $this->where("($table_field IS NULL OR $table_field = '')");
    }

    /**
     * @param array $filters_content  with 2 indexes: 'term', the search string, 'fields', the search fields
     * @param $search_table     String to filter
     * @param $filters_operator Object, inclusive or exclusive search
     */

     // sub array filters[$content]
    public function whereFilterContent($filters_content, $search_table = null, $filters_operator = null)
    {
        if (!isset($filters_content['term']) || !isset($filters_content['fields'])) {
            return $this;
        }

        if (is_null($search_table)) {
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
                $content_wc [] = "$search_field = '$search_term' "; // TODO bindthis
            } else // %%
            {
                $pattern = str_replace('TERM', $search_term, $search_mode);
                $content_wc [] = " $search_field LIKE '$pattern' "; // TODO bindthis
            }
        }

        if (!empty($content_wc)) {
            $operator = self::validWhereOperator($filters_operator, self::$OP_OR);
            $content_wc = implode(" $operator ", $content_wc);

            $this->where(" ($content_wc) ");
        }
    }
    // //------------------------------------------------------------  FIELDS
    protected static function validWhereOperator($operator, $default)
    {
        $operator = strtoupper("$operator");
        $choices = [self::$OP_AND, self::$OP_OR];

        if (in_array($operator, $choices) === true) {
            return $operator;
        }

        if (in_array($default, $choices) === true) {
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
