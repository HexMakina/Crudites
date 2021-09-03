<?php

namespace HexMakina\Crudites\Table;

use \HexMakina\Crudites\Interfaces\TableManipulationInterface;
use \HexMakina\Crudites\CruditesException;

class Row
{
    private $table;

    private $load = null;

    private $alterations = [];

    private $fresh = [];

    private $last_query = null;
    private $last_alter_query = null;

    public function __construct(TableManipulationInterface $table, $dat_ass = [])
    {
        $this->table = $table;
        $this->fresh = $dat_ass;
    }

    public function __toString()
    {
        return PHP_EOL . 'load: ' . json_encode($this->load) . PHP_EOL . 'alterations: ' . json_encode(array_keys($this->alterations));
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);
        unset($dbg['table']);
        $dbg['(string)table_name'] = (string)$this->table;

        return $dbg;
    }

    public function table(): TableManipulationInterface
    {
        return $this->table;
    }

    public function last_query()
    {
        return $this->last_query;
    }

    public function last_alter_query()
    {
        return $this->last_alter_query;
    }

    public function is_new(): bool
    {
        return empty($this->load);
    }

    public function is_loaded(): bool
    {
        return !$this->is_new();
    }

    public function is_altered(): bool
    {
        return !empty($this->alterations);
    }

    public function export(): array
    {
        return array_merge((array)$this->load, $this->fresh, $this->alterations);
    }

    /**
     * loads row content from database,
     *
     * looks for primary key matching data in $dat_ass and sets the $load variable
     * $load stays null if
     * 1. not match is found in $dat_ass
     * 2. multiple records are returned
     * 3. no record is found
     *
     * @param  Array $dat_ass an associative array containing primary key data matches
     * @return $this
     */
    public function load($dat_ass)
    {
        $pks = $this->table()->primary_keys_match($dat_ass);

        if (empty($pks)) {
            return $this;
        }

        $this->last_query = $this->table()->select()->aw_primary($pks);
        $res = $this->last_query->ret_ass();

        $this->load = (is_array($res) && count($res) === 1) ? current($res) : null;

        return $this;
    }

    /**
     * records changes vis-à-vis loaded data
     *
     * loops through the $dat_ass params
     *
     * @param  Array $dat_ass an associative array containing the new data
     * @return $this
     */
    public function alter($dat_ass)
    {
        foreach ($dat_ass as $field_name => $value) {
            $column = $this->table->column($field_name);

            // skips non exisint field name and A_I column
            if (is_null($column) || $column->isAutoIncremented()) {
                continue;
            }

            // replaces empty strings with null or default value
            if (trim($dat_ass[$field_name]) === '') {
                $dat_ass[$field_name] = $column->isNullable() ? null : $column->default();
            }

            // checks for changes with loaded data. using == instead of === is risky but needed
            if (!is_array($this->load) || $this->load[$field_name] != $dat_ass[$field_name]) {
                $this->alterations[$field_name] = $dat_ass[$field_name];
            }
        }

        return $this;
    }

    public function persist(): array
    {

        if (!$this->is_new() && !$this->is_altered()) { // existing record with no alterations
            return [];
        }

        if (!empty($errors = $this->validate())) { // Table level validation
            return $errors;
        }

        try {
            if ($this->is_new()) {
                $this->last_alter_query = $this->table()->insert($this->export());
                $this->last_alter_query->run();
                if ($this->last_alter_query->is_success() && !is_null($aipk = $this->last_alter_query->table()->auto_incremented_primary_key())) {
                    $this->alterations[$aipk->name()] = $this->last_alter_query->inserted_id();
                }
            } else {
                $pk_match = $this->table()->primary_keys_match($this->load);
                $this->last_alter_query = $this->table()->update($this->alterations, $pk_match);
                $this->last_alter_query->run();
            }

            $this->last_query = $this->last_alter_query;
        } catch (CruditesException $e) {
            return [$e->getMessage()];
        }

        return $this->last_query()->is_success() ? [] : ['CRUDITES_ERR_ROW_PERSISTENCE'];
    }

    public function wipe(): bool
    {
        $dat_ass = $this->load ?? $this->fresh ?? $this->alterations;

        // need The Primary key, then you can wipe at ease
        if (!empty($pk_match = $this->table()->primary_keys_match($dat_ass))) {
            $this->last_alter_query = $this->table->delete($pk_match);
            try {
                $this->last_alter_query->run();
            } catch (CruditesException $e) {
                return false;
            }

            $this->last_query = $this->last_alter_query;
            return $this->last_alter_query->is_success();
        }

        return false;
    }

    //------------------------------------------------------------  type:data validation
    /**
     * @return array containing all invalid data, indexed by field name, or empty if all valid
     */
    public function validate(): array
    {
        $errors = [];
        $dat_ass = $this->export();

        // vdt($this->table);
        foreach ($this->table->columns() as $column_name => $column) {
            $field_value = $dat_ass[$column_name] ?? null;

            $validation = $column->validateValue($field_value);
            if ($validation !== true) {
                $errors[$column_name] = $validation;
            }
        }

        return $errors;
    }
}
