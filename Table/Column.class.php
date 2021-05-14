<?php

namespace HexMakina\Crudites\Table;

class Column implements \HexMakina\Crudites\Interfaces\TableColumnInterface
{
  const TYPE_BOOLEAN = 'boolean';
  const TYPE_INTEGER = 'integer';

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
    self::TYPE_INTEGER => 'int\([\d]+\)|int unsigned',
    self::TYPE_ENUM => 'enum\(\'(.+)\'\)',

    self::TYPE_YEAR => '^year',
    self::TYPE_DATE => '^date$',
    self::TYPE_DATETIME => '^datetime$',
    self::TYPE_TIMESTAMP => '^timestamp$',
    self::TYPE_TIME => '^time$',

    self::TYPE_TEXT => '.*text',
    self::TYPE_STRING => 'char\((\d+)\)$'
  ];

  private $name = null;

  private $table_name = null;

  private $type = null;
  private $type_length = null;

  private $index = false;

  private $primary = false;
  private $auto_incremented = false;

  private $foreign = false;
  private $foreign_table_name = null;
  private $foreign_column_name = null;

  private $unique_name = null;
  private $unique_group_name = null;

  private $default_value = null;
  private $enum_values = null; // enums
  private $nullable = false;

  private $extra = null;

  public function __construct($table, $name, $specs=null)
  {
    $this->table_name = is_string($table) ? $table : $table->name();
    $this->name = $name;
// vdt("name: $name");
    $this->import_describe($specs);
  }

  //------------------------------------------------------------  getters:field:info
  public function __toString()
  {
    return $this->name;
  }

	public function __debugInfo()
	{
    $dbg = get_object_vars($this);

		foreach($dbg as $k => $v)
			if(!isset($dbg[$k]))
				unset($dbg[$k]);

		return $dbg;
	}

  public function name() : string
  {
    return $this->name;
  }

  public function table_name() : string
  {
    return $this->table_name;
  }

  /**
  * @return mixed the default value of a field
  * @return int for integer and boolean fields
  * @return null where no default is set
  */
  public function default()
  {
    $ret = $this->default_value;

    if(!is_null($this->default_value) && ($this->is_integer() || $this->is_boolean()))
      $ret = (int)$ret;

    return $ret;
  }

  public function setDefaultValue($v)
  {
    $this->default_value = $v;
  }

  public function setExtra($v)
  {
    $this->extra = $v;
  }

  public function is_primary($setter=null) : bool
  {
    return is_bool($setter)? ($this->primary = $setter) : $this->primary;
  }

  public function is_foreign($setter=null) : bool
  {
    return is_bool($setter) ? ($this->foreign = $setter) : $this->foreign;
  }

  public function unique_name($setter=null)
  {
    return ($this->unique_name = ($setter ?? $this->unique_name));
  }

  public function unique_group_name($setter=null)
  {
    return ($this->unique_group_name = ($setter ?? $this->unique_group_name));
  }

  public function setForeignTableName($setter){    $this->foreign_table_name = $setter;}
  public function foreign_table_name() : string
  {
    return $this->foreign_table_name;
  }

  public function foreign_table_alias() : string
  {
    $ret = $this->foreign_table_name();
		if(preg_match('/(.+)_('.$this->foreign_column_name().')$/', $this->name(), $m))
			$ret = $m[1];

    return $ret;
  }

  public function setForeignColumnName($setter){    $this->foreign_column_name = $setter;}
  public function foreign_column_name() : string{  return $this->foreign_column_name;}


  public function is_index($setter=null) : bool
  {
    return is_bool($setter) ? ($this->index = $setter) : $this->index;
  }

  public function is_auto_incremented($setter=null) : bool
  {
    return is_bool($setter) ? ($this->auto_incremented = $setter) : $this->auto_incremented;
  }

  public function is_nullable($setter=null) : bool
  {
    return is_bool($setter) ? ($this->nullable = $setter) : $this->nullable;
  }

  //------------------------------------------------------------  getters:field:info:type
  public function type($setter=null)
  {
    return is_null($setter) ? $this->type : ($this->type=$setter);
  }

  public function length($setter=null) : int
  {
    return is_null($setter) ? ($this->type_length ?? -1) : ($this->type_length=$setter);
  }

  public function is_text() : bool      {  return $this->type === self::TYPE_TEXT;}
  public function is_string() : bool    {  return $this->type === self::TYPE_STRING;}

  public function is_boolean() : bool  {  return $this->type === self::TYPE_BOOLEAN;}
  public function is_integer() : bool  {  return $this->type === self::TYPE_INTEGER;}
  public function is_enum() : bool     {  return $this->type === self::TYPE_ENUM;}

  public function is_year() : bool        {  return $this->type === self::TYPE_YEAR;}
  public function is_date() : bool        {  return $this->type === self::TYPE_DATE;}
  public function is_time() : bool        {  return $this->type === self::TYPE_TIME;}
  public function is_timestamp() : bool   {  return $this->type === self::TYPE_TIMESTAMP;}
  public function is_datetime() : bool    {  return $this->type === self::TYPE_DATETIME;}

  public function is_date_or_time() : bool
  {
    return in_array($this->type, [self::TYPE_DATE, self::TYPE_TIME, self::TYPE_TIMESTAMP, self::TYPE_DATETIME]);
  }

  public function enum_values($setter=null) : array
  {
    if(!is_null($setter))
      $this->enum_values = $setter;

    return $this->enum_values ?? [];
  }

  public function import_describe($specs)
  {
    // vd($specs);
    foreach($specs as $k => $v)
    {
      switch($k)
      {
        case 'Type':
          foreach(self::$types_rx as $type => $rx)
          {
            if(preg_match("/$rx/i", $v, $m) === 1)
            {
              $this->type($type);

              if($this->is_enum())
                $this->enum_values(explode('\',\'',$m[1]));
              elseif(preg_match('/([\d]+)/', $v, $m) === 1)
                $this->length((int)$m[0]);
              break;
            }
          }
        break;

        case 'Null':          $this->is_nullable($v !== 'NO');
        break;

        case 'Key':           $this->is_primary($v === 'PRI');
                              $this->is_index(true);
        break;

        case 'Default':       $this->setDefaultValue($v);
        break;

        case 'Extra':
          if($v === 'auto_increment')
            $this->is_auto_incremented(true);
          else
            $this->setExtra($v);
        break;
      }
    }
    return $this;
  }

  public function is_hidden()
  {
    switch($this->name())
    {
      case 'created_by':
      case 'created_on':
      case 'password':
        return true;
    }
    return false;
  }
}

?>
