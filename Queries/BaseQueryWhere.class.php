<?php

namespace HexMakina\Crudites\Queries;

// abstraction for select, update & delete
abstract class BaseQueryWhere extends BaseQuery
{
  const AND = 'AND';
  const OR = 'OR';

  const WHERE_LIKE_PRE = '%TERM';
  const WHERE_LIKE_POST = 'TERM%';
	const WHERE_LIKE_BOTH = '%TERM%';

	const GT = '>';
	const LT = '<';
	const EQ = '=';
	const GTE = '>=';
	const LTE = '<=';
	const NEQ = '<>';
	const LIKE = 'LIKE';
	const NLIKE = 'NOT LIKE';

	protected $where = null;

	public function and_where($where_condition, $where_bindings=[])
	{
		$this->where = $this->where ?? [];

		$this->where[]= "($where_condition)";

		if(!empty($where_bindings))
			$this->bindings = array_merge($this->bindings, $where_bindings);

		return $this;
	}


  public function aw_eq_or_null($field, $value, $table_name=null, $bindname=null)
	{
		$bind_name = $this->bind_name($table_name, $field, $value, $bindname);
		$field_name = $this->field_label($field, $table_name);

		return $this->and_where("($field_name = $bind_name OR $field_name IS NULL)");
	}

  public function aw_eq($field, $value, $table_name=null, $bindname=null){ 							return $this->aw_bind_field($table_name, $field, self::EQ, $value, $bindname);}
  public function aw_gt($field, $value, $table_name=null, $bindname=null){	 						return $this->aw_bind_field($table_name, $field, self::GT, $value, $bindname);}
  public function aw_lt($field, $value, $table_name=null, $bindname=null){	 						return $this->aw_bind_field($table_name, $field, self::LT, $value, $bindname);}

	public function aw_gte($field, $value, $table_name=null, $bindname=null){	 						return $this->aw_bind_field($table_name, $field, self::GTE, $value, $bindname);}
	public function aw_lte($field, $value, $table_name=null, $bindname=null){  						return $this->aw_bind_field($table_name, $field, self::LTE, $value, $bindname);}
	public function aw_not_eq($field, $value, $table_name=null, $bindname=null){  				return $this->aw_bind_field($table_name, $field, self::NEQ, $value, $bindname);}

	public function aw_primary($pk_values)
	{
		$pks = $this->table()->primary_keys_match($pk_values);

		if(empty($pks))
			$this->and_where('1=0');
		else
			$this->aw_fields_eq($pks);

		return $this;
	}

	public function aw_like($field, $prep_value, $table_name=null, $bindname=null){  			return $this->aw_bind_field($table_name, $field, self::LIKE, $prep_value, $bindname);}
	public function aw_not_like($field, $prep_value, $table_name=null, $bindname=null){  	return $this->aw_bind_field($table_name, $field, self::NLIKE, $prep_value, $bindname);}


	public function aw_fields_eq($assoc_data, $table_name=null)
  {
		$table_name = $table_name ?? $this->table_alias ?? $this->table->name();
    foreach($assoc_data as $field => $value)
			$this->aw_bind_field($table_name, $field, self::EQ, $value);

		return $this;
  }

	private function aw_bind_field($table_name, $field, $operator, $value, $bind_name = null)
	{
    $bind_name = $this->bind_name($table_name, $field, $value, $bind_name);
		return $this->aw_field($field, "$operator $bind_name", $table_name);
	}

  public function aw_numeric_in($field, $values, $table_name=null)
  {
		if(is_array($values) && !empty($values))
			return $this->aw_field($field, sprintf(' IN (%s)', implode(',', $values)), $table_name);

		return $this;
  }

  public function aw_string_in($field, $values, $table_name=null)
  {
		if(is_array($values) && !empty($values))
    {
			$count_values = count($values);
			$in = '';
			foreach($values as $i => $v)
			{
				$placeholder_name = ':'.$table_name.'_'.$field.'_awS_in_'.$count_values.'_'.$i; // TODO dirty patching. mathematical certainty needed
				$this->bindings[$placeholder_name] = $v;
				$in .= "$placeholder_name,";
			}
      // $this->aw_field($field, sprintf(" IN ('%s')", implode("','", $values)), $table_name);
      $this->aw_field($field, sprintf(" IN (%s)", rtrim($in,',')), $table_name);
    }
    return $this;
  }

  public function aw_is_null($field, $table_name=null)
	{
		return $this->aw_field($field, 'IS NULL', $table_name);
	}

	public function aw_field($field, $condition, $table_name=null)
	{
		$table_field = $this->field_label($field, $table_name);
		return $this->and_where("$table_field $condition");
	}

  public function aw_not_empty($field, $table_name=null)
	{
		$table_field = $this->field_label($field, $table_name);
		return $this->and_where("($table_field IS NOT NULL AND $table_field <> '') ");
	}

  public function aw_empty($field, $table_name=null)
	{
		$table_field = $this->field_label($field, $table_name);
		return $this->and_where("($table_field IS NULL OR $table_field = '')");
	}

	/**
	* @param array $filters_content with 2 indexes: 'term', the search string, 'fields', the search fields
	* @param RelationalTable $search_table table to filter
	* @param $filters_operator inclusive or exclusive search
	*/
	public function aw_filter_content($filters_content, $search_table=null, $filters_operator = null) // sub array filters[$content]
	{
		if(!isset($filters_content['term']) || !isset($filters_content['fields']))
			return $this;

		if(is_null($search_table))
			$search_table = $this->table_label();

		$search_term = trim($filters_content['term']);
		if($search_term !== '')
		{
			$content_wc = [];
			foreach($filters_content['fields'] as $search_field => $search_mode)
			{
				if(is_numeric($search_field))
				{
					$search_field = $search_mode;
					$search_mode = self::WHERE_LIKE_BOTH;
				}
				$search_field = $this->field_label($search_field, $search_table);
				switch($search_mode)
				{
					case self::WHERE_LIKE_PRE:
					case self::WHERE_LIKE_POST:
					case self::WHERE_LIKE_BOTH:
						$pattern = str_replace('TERM', $search_term, $search_mode);
						$content_wc []= " $search_field LIKE '$pattern' ";
					break;

					case self::EQ:
						$content_wc []= "$search_field = '$search_term' ";
					break;
				}
			}

			if(!empty($content_wc))
			{
				$operator = self::valid_operator($filters_operator, self::OR);
				$content_wc = implode(" $operator ", $content_wc);

		    $this->and_where(" ($content_wc) ", []);
			}
		}
	}
	// //------------------------------------------------------------  FIELDS
	protected static function valid_operator($operator, $default)
	{
		$operator = strtoupper("$operator");
    $choices = [self::AND, self::OR];

		if(in_array($operator, $choices) === true)
			return $operator;

		if(in_array($default, $choices) === true)
			return $default;

		throw new \Exception('ERR_INVALID_QUERY_OPERATOR');
	}

	protected function generate_where()
	{
    if(!empty($this->where))
    {
      return PHP_EOL .' WHERE '. implode(PHP_EOL.' AND ', $this->where);
    }
	}
}
