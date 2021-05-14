<?php

namespace HexMakina\Crudites;

// use \HexMakina\Format\HTML\Form;

class TableToForm
{
  private static function compute_field_value($model, $field_name)
  {
    dd(__FUNCTION);
  }
  // private static function compute_field_value($model, $field_name)
  // {
  //   if(method_exists($model, $field_name))
  //     return $model->$field_name();
  // 
  //   if(property_exists($model, $field_name))
  //     return $model->$field_name;
  // 
  //   return '';
  // }

  public static function label($model, $field_name, $attributes=[])
  {
    dd(__FUNCTION);
  }
  
  // public static function label($model, $field_name, $attributes=[])
  // {
  //   $label_content = '';
  //   if(isset($attributes['label']))
  //   {
  //     $label_content = $attributes['label'];
  //     unset($attributes['label']);
  //   }
  //   else
  //   {
  //     $field_label = sprintf('MODEL_%s_FIELD_%s', get_class($model)::model_type(), $field_name);
  //     if(!defined("L::$field_label"))
  //     {
  //         $field_label = sprintf('MODEL_common_FIELD_%s', $field_name);
  //       if(!defined("L::$field_label"))
  //         $field_label = $field_name;
  //     }
  //     $label_content = L($field_label);
  //   }
  //   $ret = Form::label($field_name, $label_content);
  //   return $ret;
  // }
  // 
  public static function field($model, $field_name, $attributes=[], $errors=[]) : string
  {
    dd(__FUNCTION);
  }
  // 
  // public static function field($model, $field_name, $attributes=[], $errors=[]) : string
  // {
  //   $field_value = $attributes['value'] ?? self::compute_field_value($model, $field_name);
  //   unset($attributes['value']);
  // 
  //   $table = get_class($model)::table();
  // 
  //   if(is_null($table->column($field_name)))
  //     return Form::input($field_name, $field_value, $attributes, $errors);
  // 
  //   $ret = '';
  // 
  //   $field = $table->column($field_name);
  // 
  //   if(!$field->is_nullable())
  //   {
  //     $attributes[] = 'required';
  //   }
  // 
  //   if($field->is_auto_incremented())
  //   {
  //     $ret .= Form::hidden($field->name(), $field_value);
  //   }
  //   elseif($field->is_boolean())
  //   {
  //     $selected = $attributes['value'] ?? '';
  //     $ret .= Form::select($field->name(), [0 => 0, 1 => 1], $selected); //
  //   }
  //   elseif($field->is_integer())
  //   {
  //     $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
  //   }
  //   elseif($field->is_year())
  //   {
  //     $attributes['size'] = $attributes['maxlength'] = 4;
  //     $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
  //   }
  //   elseif($field->is_date_or_time())
  //   {
  //     $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
  //   }
  //   elseif($field->is_text())
  //   {
  //     $ret .= Form::textarea($field->name(), $field_value, $attributes, $errors);
  //   }
  //   elseif($field->is_enum())
  //   {
  //     throw new \Exception('ENUM IS NOT HANDLED BY OTTO-FORM');
  //   }
  //   elseif($field->is_string())
  //   {
  //     $max_length = $field->length();
  //     $attributes['size'] = $attributes['maxlength'] = $max_length;
  //     $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
  //   }
  //   else
  //   {
  //     $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
  //   }
  // 
  //   return $ret;
  // }

  public static function field_with_label($model, $field_name, $attributes=[]) : string
  {
    dd(__FUNCTION);
  }

  // public static function field_with_label($model, $field_name, $attributes=[]) : string
  // {
  //   return sprintf('%s %s', self::label($model, $field_name, $attributes), self::field($model, $field_name, $attributes));
  // }

  public static function fields($model, $group_class=null) : string
  {
    dd(__FUNCTION);
  }
  // public static function fields($model, $group_class=null) : string
  // {
  //   $table = get_class($model)::table();
  //   $ret = '';
  //   foreach($table->columns() as $field_name => $field)
  //   {
  //     $form_field = '';
  //     if($field->is_auto_incremented())
  //     {
  //       if(!$model->is_new())
  //         $form_field = self::field($model, $field_name);
  //     }
  //     else
  //     {
  //       $form_field = self::field_with_label($model, $field_name);
  //       if(!is_null($group_class))
  //         $form_field = new Element('div', $form_field, ['classgroup_class]);
  //     }
  //     $ret .= PHP_EOL.$form_field;
  //   }
  //   return $ret;
  // }
}

?>
