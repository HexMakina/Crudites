<?php

namespace HexMakina\Crudites\Table;

use HexMakina\BlackBox\Database\ColumnTypeInterface;

class Column implements \HexMakina\BlackBox\Database\ColumnInterface
{

    private string $name;

    private string $table_name;

    private ?ColumnTypeInterface $ColumnType = null;

    private bool $index = false;

    private bool $primary = false;

    private bool $auto_incremented = false;

    private bool $foreign = false;

    private ?string $foreign_table_name = null;

    private ?string $foreign_column_name = null;

    private ?string $unique_name = null;

    private ?string $unique_group_name = null;

    private ?string $default_value = null;

    private bool $nullable = false;

    public function __construct(mixed $table, string $name, array $specs)
    {
        $this->table_name = is_string($table) ? $table : $table->name();
        $this->name = $name;

        $this->ColumnType = new ColumnType($specs['Type']);

        $this->default_value = $specs['Default'] ?? null;

        if (isset($specs['Null'])) {
            $this->isNullable($specs['Null'] !== 'NO');
        }

        if (isset($specs['Key'])) {
            $this->isPrimary($specs['Key'] === 'PRI');
            $this->isIndex(true);
        }

        if (isset($specs['Extra'])) {
            $this->isAutoIncremented($specs['Extra'] === 'auto_increment');
        }
    }

    //------------------------------------------------------------  getters:field:info
    public function __toString(): string
    {
        return $this->name;
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);

        foreach (array_keys($dbg) as $k) {
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

    public function type(): ?ColumnTypeInterface
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
        if (!is_null($this->default_value) && ($this->type()->isInteger() || $this->type()->isBoolean())) {
            return (int)$this->default_value;
        }

        return $this->default_value;
    }

    public function isPrimary(bool $setter = null): bool
    {
        return is_bool($setter) ? ($this->primary = $setter) : $this->primary;
    }

    public function isForeign(bool $setter = null): bool
    {
        return is_bool($setter) ? ($this->foreign = $setter) : $this->foreign;
    }

    public function isIndex(bool $setter = null): bool
    {
        return is_bool($setter) ? ($this->index = $setter) : $this->index;
    }

    public function isAutoIncremented(bool $setter = null): bool
    {
        return is_bool($setter) ? ($this->auto_incremented = $setter) : $this->auto_incremented;
    }

    public function isNullable(bool $setter = null): bool
    {
        return is_bool($setter) ? ($this->nullable = $setter) : $this->nullable;
    }

    public function uniqueName(string $setter = null)
    {
        return ($this->unique_name = ($setter ?? $this->unique_name));
    }

    public function uniqueGroupName(string $setter = null)
    {
        return ($this->unique_group_name = ($setter ?? $this->unique_group_name));
    }

    public function setForeignTableName(?string $setter): void
    {
        $this->foreign_table_name = $setter;
    }

    public function foreignTableName(): ?string
    {
        return $this->foreign_table_name;
    }

    public function foreignTableAlias(): ?string
    {
        $ret = $this->foreignTableName();
        if (preg_match('/(.+)_(' . $this->foreignColumnName() . ')$/', $this->name(), $m)) {
            return $m[1];
        }

        return $ret;
    }

    public function setForeignColumnName(?string $setter): void
    {
        $this->foreign_column_name = $setter;
    }

    public function foreignColumnName(): ?string
    {
        return $this->foreign_column_name;
    }

    public function validateValue($field_value = null)
    {
        if ($this->isAutoIncremented() || $this->type()->isBoolean()) {
            $ret = true;
        } elseif (is_null($field_value)) {
            $ret = ($this->isNullable() || !is_null($this->default())) ? true : 'ERR_FIELD_REQUIRED';
        } else {
          // nothing found on the Column level, lets check for Typing error
            $ret = $this->type()->validateValue($field_value);
        }

        return $ret;
    }
}
