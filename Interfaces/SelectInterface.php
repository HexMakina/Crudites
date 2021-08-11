<?php

namespace HexMakina\Crudites\Interfaces;

interface SelectInterface extends QueryInterface
{
    public function table_alias($setter = null); // TODO should only be a setter
    public function table_label($forced_value = null);

    public function columns($setter = null);
    public function select_also($setter);

    public function group_by($clause);
    public function having($condition);
    public function order_by($clause);
    public function limit($number, $offset = null);

    public function ret_obj($c = null);
    public function ret_num();
    public function ret_ass();
    public function ret_col();
    public function ret_par();
    public function ret_key();

    public function and_where($where_condition, $where_bindings = []);

    public function aw_primary($pk_values);

    public function aw_eq_or_null($field, $value, $table_name = null, $bindname = null);
    public function aw_eq($field, $value, $table_name = null, $bindname = null);
    public function aw_gt($field, $value, $table_name = null, $bindname = null);
    public function aw_lt($field, $value, $table_name = null, $bindname = null);

    public function aw_gte($field, $value, $table_name = null, $bindname = null);
    public function aw_lte($field, $value, $table_name = null, $bindname = null);
  
    public function aw_fields_eq($assoc_data, $table_name = null);

    public function aw_like($field, $prep_value, $table_name = null, $bindname = null);
    public function aw_not_like($field, $prep_value, $table_name = null, $bindname = null);

    public function aw_numeric_in($field, $values, $table_name = null);
    public function aw_string_in($field, $values, $table_name = null);

    public function aw_empty($field, $table_name = null);
    public function aw_not_empty($field, $table_name = null);
    public function aw_is_null($field, $table_name = null);

    public function aw_field($field, $condition, $table_name = null);

    public function aw_filter_content($filters_content, $search_table = null, $filters_operator = null);
}
