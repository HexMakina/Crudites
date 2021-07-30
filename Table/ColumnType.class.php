<?php

namespace HexMakina\Crudites\Table;

class ColumnType
{
  const TYPE_BOOLEAN = 'boolean';
  const TYPE_INTEGER = 'integer';
  const TYPE_FLOAT = 'float';

  const TYPE_TEXT = 'text';
  const TYPE_STRING = 'char';

  const TYPE_DATETIME = 'datetime';
  const TYPE_DATE = 'date';
  const TYPE_TIMESTAMP = 'timestamp';
  const TYPE_TIME = 'time';
  const TYPE_YEAR = 'year';

  const TYPE_ENUM = 'enum';

  static private $types_rx = [
    self::TYPE_BOOLEAN => 'tinyint\(1\)|boolean', // is_boolean MUST be tested before is_integer
    self::TYPE_INTEGER => 'int\([\d]+\)|int unsigned|int',
    self::TYPE_FLOAT => 'float|double',
    self::TYPE_ENUM => 'enum\(\'(.+)\'\)',

    self::TYPE_YEAR => '^year',
    self::TYPE_DATE => '^date$',
    self::TYPE_DATETIME => '^datetime$',
    self::TYPE_TIMESTAMP => '^timestamp$',
    self::TYPE_TIME => '^time$',

    self::TYPE_TEXT => '.*text',
    self::TYPE_STRING => 'char\((\d+)\)$'
  ];

  private $column = null;
  private $name = null;

  private $enum_values = null;
  private $length = null;


  public function __construct($column, $specs_type)
  {
    foreach(self::$types_rx as $type => $rx)
    {
      if(preg_match("/$rx/i", $specs_type, $m) === 1)
      {
        $this->name = $type;

        if($this->is_enum())
          $this->enum_values = explode('\',\'',$m[1]);
        elseif(preg_match('/([\d]+)/', $v, $m) === 1)
          $this->length = (int)$m[0];
        break;
      }
    }
  }

  public function is_text() : bool        {  return $this->name === self::TYPE_TEXT;}
  public function is_string() : bool      {  return $this->name === self::TYPE_STRING;}

  public function is_boolean() : bool     {  return $this->name === self::TYPE_BOOLEAN;}
  public function is_integer() : bool     {  return $this->name === self::TYPE_INTEGER;}
  public function is_float() : bool       {  return $this->name === self::TYPE_FLOAT;}

  public function is_enum() : bool        {  return $this->name === self::TYPE_ENUM;}

  public function is_year() : bool        {  return $this->name === self::TYPE_YEAR;}
  public function is_date() : bool        {  return $this->name === self::TYPE_DATE;}
  public function is_time() : bool        {  return $this->name === self::TYPE_TIME;}
  public function is_timestamp() : bool   {  return $this->name === self::TYPE_TIMESTAMP;}
  public function is_datetime() : bool    {  return $this->name === self::TYPE_DATETIME;}

  public function is_date_or_time() : bool
  {
    return in_array($this->name, [self::TYPE_DATE, self::TYPE_TIME, self::TYPE_TIMESTAMP, self::TYPE_DATETIME]);
  }

  public function enum_values()
  {
    return $this->enum_values ?? [];
  }

  public function length()
  {
    return $this->length ?? -1;
  }


  public function validate_value($field_value)
  {
    $ret = true;

    if($this->is_date_or_time())
    {
      if(date_create($field_value) === false)
        $ret = 'ERR_FIELD_FORMAT';
    }
    elseif($this->is_year())
    {
      if(preg_match('/^[0-9]{4}$/', $field_value) !== 1)
        $ret = 'ERR_FIELD_FORMAT';
    }
    elseif($this->is_string())
    {
      if($this->length() < strlen($field_value))
        $ret = 'ERR_FIELD_TOO_LONG';
    }
    elseif($this->is_integer() || $this->is_float())
    {
      if(!is_numeric($field_value))
        $ret = 'ERR_FIELD_FORMAT';
    }
    elseif($this->is_enum())
    {
      if(!in_array($field_value, $this->enum_values()))
        $ret = 'ERR_FIELD_VALUE_RESTRICTED_BY_ENUM';
    }
    else
    {
      throw new CruditesException('FIELD_TYPE_UNKNOWN');
    }

    return $ret;
  }
}
