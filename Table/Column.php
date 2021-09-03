<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Crudites\Interfaces\ColumnTypeInterface;

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
        $this->processSpecs($specs);
    }

    private function processSpecs($specs)
    {
      $this->ColumnType = new ColumnType($specs['Type']);
      
      $this->setDefaultValue($specs['Default'] ?? null);

      if(isset($specs['Null'])){
        $this->isNullable($specs['Null'] !== 'NO');
      }

      if(isset($specs['Key'])){
        $this->isPrimary($specs['Key'] === 'PRI');
        $this->isIndex(true);
      }

      if(isset($specs['Extra'])){
        if ($specs['Extra'] === 'auto_increment') {
          $this->isAutoIncremented(true);
        } else {
          $this->setExtra($v);
        }
      }
    }

    //------------------------------------------------------------  getters:field:info
    public function __toString()
    {
        return $this->name;
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);

        foreach ($dbg as $k => $v) {
            if (!isset($dbg[$k])) {
                unset($dbg[$k]);
            }
        }

        return $dbg;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): ColumnTypeInterface
    {
        return $this->ColumnType;
    }
    public function tableName(): string
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

        if (!is_null($this->default_value) && ($this->type()->isInteger() || $this->type()->isBoolean())) {
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

    public function isPrimary($setter = null): bool
    {
        return is_bool($setter) ? ($this->primary = $setter) : $this->primary;
    }

    public function isForeign($setter = null): bool
    {
        return is_bool($setter) ? ($this->foreign = $setter) : $this->foreign;
    }

    public function isIndex($setter = null): bool
    {
        return is_bool($setter) ? ($this->index = $setter) : $this->index;
    }

    public function isAutoIncremented($setter = null): bool
    {
        return is_bool($setter) ? ($this->auto_incremented = $setter) : $this->auto_incremented;
    }

    public function isNullable($setter = null): bool
    {
        return is_bool($setter) ? ($this->nullable = $setter) : $this->nullable;
    }

    public function isHidden(): bool
    {
        switch ($this->name()) {
            case 'created_by':
            case 'created_on':
            case 'password':
                return true;
        }
        return false;
    }

    public function uniqueName($setter = null)
    {
        return ($this->unique_name = ($setter ?? $this->unique_name));
    }

    public function uniqueGroupName($setter = null)
    {
        return ($this->unique_group_name = ($setter ?? $this->unique_group_name));
    }

    public function setForeignTableName($setter)
    {
        $this->foreign_table_name = $setter;
    }

    public function foreignTableName(): string
    {
        return $this->foreign_table_name;
    }

    public function foreignTableAlias(): string
    {
        $ret = $this->foreignTableName();
        if (preg_match('/(.+)_(' . $this->foreignColumnName() . ')$/', $this->name(), $m)) {
            $ret = $m[1];
        }

        return $ret;
    }

    public function setForeignColumnName($setter)
    {
        $this->foreign_column_name = $setter;
    }

    public function foreignColumnName(): string
    {
        return $this->foreign_column_name;
    }

    public function validateValue($field_value = null)
    {
        $ret = false;

        if ($this->isAutoIncremented() || $this->type()->isBoolean()) {
            $ret = true;
        }
        elseif (is_null($field_value)) {
            $ret = ($this->isNullable() || !is_null($this->default())) ? true : 'ERR_FIELD_REQUIRED';
        }
        else{
          // nothing found on the Column level, lets check for Typing error
          $ret = $this->type()->validateValue($field_value);
        }

        return $ret;
    }
}
