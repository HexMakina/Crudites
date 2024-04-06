<?php

namespace HexMakina\Crudites\Table;

use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\BlackBox\Database\RowInterface;
use HexMakina\BlackBox\Database\QueryInterface;
use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\CruditesExceptionFactory;

class Row implements RowInterface
{
    private TableInterface $table;

    /** @var array<int|string,mixed>|null $load from database */
    private ?array $load = null;

    /** @var array<int|string,mixed> $alterations during lifecycle */
    private array $alterations = [];

    /** @var array<int|string,mixed> $fresh from the constructor */
    private array $fresh = [];

    private ?QueryInterface $last_query = null;

    private ?QueryInterface $last_alter_query = null;


    /** @param array<string,mixed> $datass */
    /**
     * Represents a row in a table.
     *
     * @param TableInterface $table The table to which the row belongs.
     * @param array $fresh The fresh data for the row.
     */
    public function __construct(TableInterface $table, array $fresh = [])
    {
        $this->table = $table;
        $this->fresh = $fresh;
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

    public function get($name)
    {
        return $this->alterations[$name]
            ?? $this->fresh[$name]
            ?? $this->load[$name]
            ?? null;
    }

    public function set($name, $value) : void
    {
        $this->alterations[$name] = $value;
    }

    public function table(): TableInterface
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
     * merges the initial database load with the constructor and the alterations
     * the result is an associative array containing all data, all up-to-date
     */
    public function export(): array
    {
        return array_merge((array)$this->load, $this->fresh, $this->alterations);
    }

    /**
     * loads row content from database,
     *
     * looks for primary key matching data in $datass and sets the $load variable
     * $load stays null if
     * 1. not match is found in $datass
     * 2. multiple records are returned
     * 3. no record is found
     *
     * @param  array<int|string,mixed> $datass an associative array containing primary key data matches
     */
    public function load(array $datass): Rowinterface
    {
        $unique_identifiers = $this->table()->matchUniqueness($datass);
        
        if (empty($unique_identifiers)) {
            return $this;
        }
        $this->last_query = $this->table()->select()->whereFieldsEQ($unique_identifiers);
        $res = $this->last_query->ret(\PDO::FETCH_ASSOC);
        
        $this->load = (is_array($res) && count($res) === 1) ? current($res) : null;

        return $this;
    }

    /**
     * records changes vis-à-vis loaded data
     *
     * loops through the $datass params
     *
     * @param  array<int|string,mixed> $datass an associative array containing the new data
     */
    public function alter(array $datass): Rowinterface
    {
        foreach (array_keys($datass) as $field_name) {
            $column = $this->table->column($field_name);
            // skips non existing field name and A_I column
            if (is_null($column)) {
                continue;
            }

            if ($column->isAutoIncremented()) {
                continue;
            }

            // replaces empty strings with null or default value
            if (trim('' . $datass[$field_name]) === '') {
                $datass[$field_name] = $column->isNullable() ? null : $column->default();
            }

            // checks for changes with loaded data. using == instead of === is risky but needed
            if (!is_array($this->load) || $this->load[$field_name] != $datass[$field_name]) {
                $this->set($field_name, $datass[$field_name]);
            }
        }

        return $this;
    }

    /**
      * @return array<string,string> an array of errors, column name => message
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

            if(is_null($this->lastQuery()) || !$this->lastQuery()->isSuccess()){
                $res = CruditesExceptionFactory::make($this->last_query);
                throw $res;
            }
            
        } catch (CruditesException $cruditesException) {
            return [$this->table()->name() => $cruditesException->getMessage()];
        }

        return [];
    }

    private function create(): void
    {
        $this->last_alter_query = $this->table()->insert($this->export());

        $this->lastAlterQuery()->run();

        // creation might lead to auto_incremented changes
        // recovering auto_incremented value and pushing it in alterations tracker
        if ($this->lastAlterQuery()->isSuccess()) {
            $aipk = $this->lastAlterQuery()->table()->autoIncrementedPrimaryKey();
            if (!is_null($aipk)) {
                $this->alterations[$aipk->name()] = $this->lastAlterQuery()->connection()->lastInsertId();
            }
        }
    }

    private function update(): void
    {
        $pk_match = $this->table()->primaryKeysMatch($this->load);
        $this->last_alter_query = $this->table()->update($this->alterations, $pk_match);
        $this->lastAlterQuery()->run();
    }

    public function wipe(): bool
    {
        $datass = $this->load ?? $this->fresh ?? $this->alterations;

        // need The Primary key, then you can wipe at ease
        if (!empty($pk_match = $this->table()->primaryKeysMatch($datass))) {
            $this->last_alter_query = $this->table->delete($pk_match);
            try {
                $this->last_alter_query->run();
                $this->last_query = $this->last_alter_query;

            } catch (CruditesException $cruditesException) {
                return false;
            }

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
        $datass = $this->export();

        foreach ($this->table->columns() as $column_name => $column) {

            $validation = $column->validateValue($datass[$column_name] ?? null);
            
            if (!empty($validation)) {
                $errors[$column_name] = $validation;
            }
        }

        return $errors;
    }
}
