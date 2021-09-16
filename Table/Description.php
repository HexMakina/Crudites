<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\TableDescriptionInterface;
use HexMakina\BlackBox\Database\TableColumnInterface;

class Description implements TableDescriptionInterface
{
    protected $connection = null;

    protected $name = null;
    // protected $ORM_class_name = null;

    protected $columns = [];

    // auto_incremented_primary_key
    protected $aipk = null;

    protected $primary_keys = [];
    protected $foreign_keys_by_name = [];
    protected $foreign_keys_by_table = [];
    protected $unique_keys = [];

    public function __construct($table_name, ConnectionInterface $c)
    {
        $this->name = $table_name;
        $this->connection = $c;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function addColumn(TableColumnInterface $column)
    {
        $this->columns[$column->name()] = $column;

        if ($column->isPrimary()) {
            $this->addPrimaryKey($column);
            if ($column->isAutoIncremented()) {
                $this->autoIncrementedPrimaryKey($column);
            }
        }
    }

    public function addPrimaryKey(TableColumnInterface $column)
    {
        $this->primary_keys[$column->name()] = $column;
    }

    public function addUniqueKey($constraint_name, $columns)
    {
        if (!isset($this->unique_keys[$constraint_name])) {
            $this->unique_keys[$constraint_name] = $columns;
        }
    }

    public function addForeignKey(TableColumnInterface $column)
    {
        $this->foreign_keys_by_table[$column->foreignTableName()] = $this->foreign_keys_by_table[$column->foreignTableName()] ?? [];
        $this->foreign_keys_by_table[$column->foreignTableName()] [] = $column;

        $this->foreign_keys_by_name[$column->name()] = $column;
    }

    //getsetter of AIPK, default get is null, cant set to null
    public function autoIncrementedPrimaryKey(TableColumnInterface $setter = null)
    {
        return is_null($setter) ? $this->aipk : ($this->aipk = $setter);
    }

    //------------------------------------------------------------  getters
    // TableDescriptionInterface implementation
    public function name(): string
    {
        return $this->name;
    }

    // TableDescriptionInterface implementation
    public function columns(): array
    {
        return $this->columns;
    }

    // TableDescriptionInterface implementation
    public function column($name)
    {
        return $this->columns[$name] ?? null;
    }

    // TableDescriptionInterface implementation
    public function uniqueKeysByName(): array
    {
        return $this->unique_keys;
    }

    // TableDescriptionInterface implementation
    public function primaryKeys($with_values = null): array
    {
        if (is_null($with_values)) {
            return $this->primary_keys;
        }

        if (!is_array($with_values) && count($this->primary_keys) === 1) {
            $with_values = [current($this->primary_keys)->name() => $with_values];
        }

        $valid_dat_ass = [];
        foreach ($this->primary_keys as $pk_name => $pk_field) {
            if (!isset($with_values[$pk_name]) && !$pk_field->isNullable()) {
                return [];
            }

            $valid_dat_ass[$pk_name] = $with_values[$pk_name];
        }
        return $valid_dat_ass;
    }

    public function matchUniqueness($dat_ass): array
    {
        $ret = $this->primaryKeysMatch($dat_ass);

        if (empty($ret)) {
            $ret = $this->uniqueKeysMatch($dat_ass);
        }

        return $ret;
    }

    /*
    * @return array, empty on mismatch
    * @return array, assoc of column_name => $value on match
    * @throws CruditesException if no pk defined
    */

    public function primaryKeysMatch($dat_ass): array
    {

        if (count($this->primaryKeys()) === 0) {
            throw new CruditesException('NO_PRIMARY_KEYS_DEFINED');
        }

        if (!is_array($dat_ass) && count($this->primaryKeys()) === 1) {
            $dat_ass = [current($this->primaryKeys())->name() => $dat_ass];
        }

        $valid_dat_ass = [];
        foreach ($this->primary_keys as $pk_name => $pk_field) {
            // empty ensures non existing keys, null and empty values
            if (empty($dat_ass[$pk_name]) && !$pk_field->isNullable()) {
                return [];
            }

            $valid_dat_ass[$pk_name] = $dat_ass[$pk_name] ?? null;
        }

        return $valid_dat_ass;
    }

    public function uniqueKeysMatch($dat_ass): array
    {

        if (count($this->uniqueKeysByName()) === 0 || !is_array($dat_ass)) {
            return [];
        }

        $keys = array_keys($dat_ass);

        foreach ($this->uniqueKeysByName() as $constraint_name => $column_names) {
            if (!is_array($column_names)) {
                $column_names = [$column_names];
            }

            if (empty(array_diff($keys, $column_names))) {
                return $dat_ass;
            }
        }
        return [];
    }

    // TableDescriptionInterface implementation
    public function foreignKeysByName(): array
    {
        return $this->foreign_keys_by_name;
    }

    // TableDescriptionInterface implementation
    public function foreignKeysByTable(): array
    {
        return $this->foreign_keys_by_table;
    }

    public function singleForeignKeyTo($other_table)
    {
        $bonding_column_candidates = $this->foreignKeysByTable()[$other_table->name()] ?? [];

        if (count($bonding_column_candidates) === 1) {
            return current($bonding_column_candidates);
        }

        return null;
    }
}
