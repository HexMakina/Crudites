<?php

namespace HexMakina\Crudites\Table;

class Column implements \HexMakina\Crudites\Interfaces\TableColumnInterface
{

    private $name = null;

    private $table_name = null;

    private $ColumnType = null;

    private $index = false;

    private $primary = false;
    private $auto_incremented = false;

    private $foreign = false;
    private $foreign_table_name = null;
    private $foreign_column_name = null;

    private $unique_name = null;
    private $unique_group_name = null;

    private $default_value = null;

    private $nullable = false;

    private $extra = null;

    public function __construct($table, $name, $specs)
    {
        $this->table_name = is_string($table) ? $table : $table->name();
        $this->name = $name;
        $this->ColumnType = new ColumnType($specs['Type']);
        $this->import_describe($specs);
    }

  //------------------------------------------------------------  getters:field:info
    public function __toString()
    {
        return $this->name;
    }

    public function __debugInfo() : array
    {
        $dbg = get_object_vars($this);

        foreach ($dbg as $k => $v) {
            if (!isset($dbg[$k])) {
                unset($dbg[$k]);
            }
        }

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

        if (!is_null($this->default_value) && ($this->type()->is_integer() || $this->type()->is_boolean())) {
            $ret = (int)$ret;
        }

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

    public function is_primary($setter = null) : bool
    {
        return is_bool($setter)? ($this->primary = $setter) : $this->primary;
    }

    public function is_foreign($setter = null) : bool
    {
        return is_bool($setter) ? ($this->foreign = $setter) : $this->foreign;
    }

    public function unique_name($setter = null)
    {
        return ($this->unique_name = ($setter ?? $this->unique_name));
    }

    public function unique_group_name($setter = null)
    {
        return ($this->unique_group_name = ($setter ?? $this->unique_group_name));
    }

    public function setForeignTableName($setter)
    {
        $this->foreign_table_name = $setter;
    }
    public function foreign_table_name() : string
    {
        return $this->foreign_table_name;
    }

    public function foreign_table_alias() : string
    {
        $ret = $this->foreign_table_name();
        if (preg_match('/(.+)_('.$this->foreign_column_name().')$/', $this->name(), $m)) {
            $ret = $m[1];
        }

        return $ret;
    }

    public function setForeignColumnName($setter)
    {
        $this->foreign_column_name = $setter;
    }
    public function foreign_column_name() : string
    {
        return $this->foreign_column_name;
    }


    public function is_index($setter = null) : bool
    {
        return is_bool($setter) ? ($this->index = $setter) : $this->index;
    }

    public function is_auto_incremented($setter = null) : bool
    {
        return is_bool($setter) ? ($this->auto_incremented = $setter) : $this->auto_incremented;
    }

    public function is_nullable($setter = null) : bool
    {
        return is_bool($setter) ? ($this->nullable = $setter) : $this->nullable;
    }

  //------------------------------------------------------------  getters:field:info:type
  // public function type($setter=null)
  // {
  //   return is_null($setter) ? $this->type : ($this->type=$setter);
  // }
    public function type()
    {
        return $this->ColumnType;
    }

  // public function length($setter=null) : int
  // {
  //   return is_null($setter) ? ($this->type_length ?? -1) : ($this->type_length=$setter);
  // }

    public function import_describe($specs)
    {
        foreach ($specs as $k => $v) {
            switch ($k) {
                case 'Null':
                    $this->is_nullable($v !== 'NO');
                    break;

                case 'Key':
                    $this->is_primary($v === 'PRI');
                              $this->is_index(true);
                    break;

                case 'Default':
                    $this->setDefaultValue($v);
                    break;

                case 'Extra':
                    if ($v === 'auto_increment') {
                        $this->is_auto_incremented(true);
                    } else {
                        $this->setExtra($v);
                    }
                    break;
            }
        }
        return $this;
    }

    public function validate_value($field_value = null)
    {
        if ($this->is_auto_incremented()) {
            return true;
        }

        if ($this->type()->is_boolean()) {
            return true;
        }

        if (is_null($field_value)) {
            if ($this->is_nullable()) {
                return true;
            } elseif (is_null($this->default())) {
                return 'ERR_FIELD_REQUIRED';
            }
        }

      // nothing found on the Column level, lets check for Typing error
        return $this->type()->validate_value($field_value);
    }

    public function is_hidden()
    {
        switch ($this->name()) {
            case 'created_by':
            case 'created_on':
            case 'password':
                return true;
        }
        return false;
    }
}
