<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Crudites\Interfaces\TableManipulationInterface;

trait ClauseWhere
{
    // const AND = 'AND';
    // const OR = 'OR';

    // const WHERE_LIKE_PRE = '%TERM';
    // const WHERE_LIKE_POST = 'TERM%';
    // const WHERE_LIKE_BOTH = '%TERM%';

    // const GT = '>';
    // const LT = '<';
    // const EQ = '=';
    // const GTE = '>=';
    // const LTE = '<=';
    // const NEQ = '<>';
    // const LIKE = 'LIKE';
    // const NLIKE = 'NOT LIKE';

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

    abstract public function table(TableManipulationInterface $setter = null) : TableManipulationInterface;
    abstract public function table_label($table_name = null);
    abstract public function bind_name($table_name, $field, $value, $bind_label = null);
    abstract public function field_label($field, $table_name = null);
    abstract public function add_binding($k, $v);


    public function and_where($where_condition, $where_bindings = [])
    {
        $this->where = $this->where ?? [];

        $this->where[]= "($where_condition)";

        foreach ($where_bindings as $k => $v) {
            $this->add_binding($k, $v);
        }

        return $this;
    }


    public function aw_eq_or_null($field, $value, $table_name = null, $bindname = null)
    {
        $bind_name = $this->bind_name($table_name, $field, $value, $bindname);
        $field_name = $this->field_label($field, $table_name);

        return $this->and_where("($field_name = $bind_name OR $field_name IS NULL)");
    }

    public function aw_eq($field, $value, $table_name = null, $bindname = null)
    {
               return $this->aw_bind_field($table_name, $field, self::$OP_EQ, $value, $bindname);
    }
    public function aw_gt($field, $value, $table_name = null, $bindname = null)
    {
               return $this->aw_bind_field($table_name, $field, self::$OP_GT, $value, $bindname);
    }
    public function aw_lt($field, $value, $table_name = null, $bindname = null)
    {
               return $this->aw_bind_field($table_name, $field, self::$OP_LT, $value, $bindname);
    }

    public function aw_gte($field, $value, $table_name = null, $bindname = null)
    {
               return $this->aw_bind_field($table_name, $field, self::$OP_GTE, $value, $bindname);
    }
    public function aw_lte($field, $value, $table_name = null, $bindname = null)
    {
              return $this->aw_bind_field($table_name, $field, self::$OP_LTE, $value, $bindname);
    }
    public function aw_not_eq($field, $value, $table_name = null, $bindname = null)
    {
          return $this->aw_bind_field($table_name, $field, self::$OP_NEQ, $value, $bindname);
    }

    public function aw_primary($pk_values)
    {
        $pks = $this->table()->primary_keys_match($pk_values);

        if (empty($pks)) {
            $this->and_where('1=0');
        } else {
            $this->aw_fields_eq($pks);
        }

        return $this;
    }

    public function aw_like($field, $prep_value, $table_name = null, $bindname = null)
    {
        return $this->aw_bind_field($table_name, $field, self::$OP_LIKE, $prep_value, $bindname);
    }
    public function aw_not_like($field, $prep_value, $table_name = null, $bindname = null)
    {
        return $this->aw_bind_field($table_name, $field, self::$OP_NLIKE, $prep_value, $bindname);
    }


    public function aw_fields_eq($assoc_data, $table_name = null)
    {
        $table_name = $this->table_label($table_name);
        foreach ($assoc_data as $field => $value) {
            $this->aw_bind_field($table_name, $field, self::$OP_EQ, $value);
        }

        return $this;
    }

    private function aw_bind_field($table_name, $field, $operator, $value, $bind_name = null)
    {
        $bind_name = $this->bind_name($table_name, $field, $value, $bind_name);
        return $this->aw_field($field, "$operator $bind_name", $table_name);
    }

    public function aw_numeric_in($field, $values, $table_name = null)
    {
        if (is_array($values) && !empty($values)) {
            return $this->aw_field($field, sprintf(' IN (%s)', implode(',', $values)), $table_name);
        }

        return $this;
    }

    public function aw_string_in($field, $values, $table_name = null)
    {
        if (is_array($values) && !empty($values)) {
            $count_values = count($values);
            $in = '';
            foreach ($values as $i => $v) {
                $placeholder_name = ':'.$table_name.'_'.$field.'_awS_in_'.$count_values.'_'.$i; // TODO dirty patching. mathematical certainty needed
                $this->add_binding($placeholder_name, $v);
                $in .= "$placeholder_name,";
            }
            // $this->aw_field($field, sprintf(" IN ('%s')", implode("','", $values)), $table_name);
            $this->aw_field($field, sprintf(" IN (%s)", rtrim($in, ',')), $table_name);
        }
        return $this;
    }

    public function aw_is_null($field, $table_name = null)
    {
        return $this->aw_field($field, 'IS NULL', $table_name);
    }

    public function aw_field($field, $condition, $table_name = null)
    {
        $table_field = $this->field_label($field, $table_name);
        return $this->and_where("$table_field $condition");
    }

    public function aw_not_empty($field, $table_name = null)
    {
        $table_field = $this->field_label($field, $table_name);
        return $this->and_where("($table_field IS NOT NULL AND $table_field <> '') ");
    }

    public function aw_empty($field, $table_name = null)
    {
        $table_field = $this->field_label($field, $table_name);
        return $this->and_where("($table_field IS NULL OR $table_field = '')");
    }

    /**
     * @param array $filters_content  with 2 indexes: 'term', the search string, 'fields', the search fields
     * @param $search_table     String to filter
     * @param $filters_operator Object, inclusive or exclusive search
     */
    public function aw_filter_content($filters_content, $search_table = null, $filters_operator = null) // sub array filters[$content]
    {
        if (!isset($filters_content['term']) || !isset($filters_content['fields'])) {
            return $this;
        }

        if (is_null($search_table)) {
            $search_table = $this->table_label();
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
            $search_field = $this->field_label($search_field, $search_table);

            if ($search_mode === self::$OP_EQ) {
                $content_wc []= "$search_field = '$search_term' "; // TODO bindthis
            } else // %%
            {
                $pattern = str_replace('TERM', $search_term, $search_mode);
                $content_wc []= " $search_field LIKE '$pattern' "; // TODO bindthis
            }
        }

        if (!empty($content_wc)) {
            $operator = self::valid_operator($filters_operator, self::$OP_OR);
            $content_wc = implode(" $operator ", $content_wc);

            $this->and_where(" ($content_wc) ", []);
        }
    }
    // //------------------------------------------------------------  FIELDS
    protected static function valid_operator($operator, $default)
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

    protected function generate_where()
    {
        if (!empty($this->where)) {
            return PHP_EOL .' WHERE '. implode(PHP_EOL.' AND ', $this->where);
        }
    }
}
