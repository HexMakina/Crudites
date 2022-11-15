<?php

namespace HexMakina\Crudites\Table;

use HexMakina\BlackBox\Database\TableManipulationInterface;
use HexMakina\BlackBox\Database\RowInterface;
use HexMakina\BlackBox\Database\QueryInterface;
use HexMakina\Crudites\CruditesException;

class Row implements RowInterface
{
    private TableManipulationInterface $table;


    /** @var array<int|string, mixed>|null $load */
    private ?array $load = null;

    /** @var array<int|string,mixed> $alterations */
    private array $alterations = [];

    /** @var array<int|string,mixed> $fresh */
    private array $fresh = [];

    private ?QueryInterface $last_query = null;

    private ?QueryInterface $last_alter_query = null;


    /** @param array<string,mixed> $dat_ass */
    public function __construct(TableManipulationInterface $tableManipulation, array $dat_ass = [])
    {
        $this->table = $tableManipulation;
        $this->fresh = $dat_ass;
    }

    public function __toString()
    {
        return PHP_EOL . 'load: '
        . json_encode($this->load)
        . PHP_EOL . 'alterations: '
        . json_encode(array_keys($this->alterations));
    }

    /**
      * @return array<string,mixed>
      */
    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);
        unset($dbg['table']);
        $dbg['(string)table_name'] = $this->table()->name();

        return $dbg;
    }

    public function table(): TableManipulationInterface
    {
        return $this->table;
    }

    public function lastQuery(): ?QueryInterface
    {
        return $this->last_query;
    }

    public function lastAlterQuery(): ?QueryInterface
    {
        return $this->last_alter_query;
    }

    public function isNew(): bool
    {
        return empty($this->load);
    }

    public function isAltered(): bool
    {
        return !empty($this->alterations);
    }

    /**
     * @return array<int|string,mixed>
     */
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
     * @param  array<int|string,mixed> $dat_ass an associative array containing primary key data matches
     */
    public function load(array $dat_ass): self
    {
        $pks = $this->table()->primaryKeysMatch($dat_ass);

        if (empty($pks)) {
            return $this;
        }

        $this->last_query = $this->table()->select()->wherePrimary($pks);
        $res = $this->last_query->retAss();

        $this->load = (is_array($res) && count($res) === 1) ? current($res) : null;

        return $this;
    }

    /**
     * records changes vis-à-vis loaded data
     *
     * loops through the $dat_ass params
     *
     * @param  array<int|string,mixed> $dat_ass an associative array containing the new data
     */
    public function alter(array $dat_ass): self
    {
        foreach (array_keys($dat_ass) as $field_name) {
            $column = $this->table->column($field_name);
            // skips non exisint field name and A_I column
            if (is_null($column)) {
                continue;
            }
            if ($column->isAutoIncremented()) {
                continue;
            }

            // replaces empty strings with null or default value
            if (trim('' . $dat_ass[$field_name]) === '') {
                $dat_ass[$field_name] = $column->isNullable() ? null : $column->default();
            }

            // checks for changes with loaded data. using == instead of === is risky but needed
            if (!is_array($this->load) || $this->load[$field_name] != $dat_ass[$field_name]) {
                $this->alterations[$field_name] = $dat_ass[$field_name];
            }
        }

        return $this;
    }

    /**
      * @return array<mixed,string> an array of errors
      */
    public function persist(): array
    {
        if (!$this->isNew() && !$this->isAltered()) { // existing record with no alterations
            return [];
        }

        if (!empty($errors = $this->validate())) { // Table level validation
            return $errors;
        }

        try {
            if ($this->isNew()) {
                $this->create();
            } else {
                $this->update();
            }

            $this->last_query = $this->lastAlterQuery();
        } catch (CruditesException $cruditesException) {
            return [$cruditesException->getMessage()];
        }

        return !is_null($this->lastQuery()) && $this->lastQuery()->isSuccess()
               ? []
               : ['CRUDITES_ERR_ROW_PERSISTENCE'];
    }

    private function create(): void
    {
        $this->last_alter_query = $this->table()->insert($this->export());
        $this->last_alter_query->run();

      // creation might lead to auto_incremented changes
      // recovering auto_incremented value and pushing it in alterations tracker
        if ($this->last_alter_query->isSuccess()) {
            $aipk = $this->last_alter_query->table()->autoIncrementedPrimaryKey();
            if (!is_null($aipk)) {
                $this->alterations[$aipk->name()] = $this->last_alter_query->connection()->lastInsertId();
            }
        }
    }

    private function update(): void
    {
        $pk_match = $this->table()->primaryKeysMatch($this->load);
        $this->last_alter_query = $this->table()->update($this->alterations, $pk_match);
        $this->last_alter_query->run();
    }

    public function wipe(): bool
    {
        $dat_ass = $this->load ?? $this->fresh ?? $this->alterations;

        // need The Primary key, then you can wipe at ease
        if (!empty($pk_match = $this->table()->primaryKeysMatch($dat_ass))) {
            $this->last_alter_query = $this->table->delete($pk_match);
            try {
                $this->last_alter_query->run();
            } catch (CruditesException $cruditesException) {
                return false;
            }

            $this->last_query = $this->last_alter_query;
            return $this->last_alter_query->isSuccess();
        }

        return false;
    }

    //------------------------------------------------------------  type:data validation
    /**
     * @return array<mixed,string> containing all invalid data, indexed by field name, or empty if all valid
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
