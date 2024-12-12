<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\RowInterface;
use HexMakina\BlackBox\Database\ResultInterface;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\Grammar\Clause\Where;

class Row implements RowInterface
{
    private string $table;

    private ConnectionInterface $connection;

    /** @var array<int|string,mixed>|null $load from database */
    private ?array $load = null;

    /** @var array<int|string,mixed> $fresh from the constructor */
    private array $fresh = [];

    /** @var array<int|string,mixed> $alterations during lifecycle */
    private array $alterations = [];

    /** @var ResultInterface|null $result the result from the last executed query */
    private ?ResultInterface $result = null;


    /** @param array<string,mixed> $datass */
    /**
     * Represents a row in a table.
     *
     * @param ConnectionInterface $connection The database connection.
     * @param string $table The table name.
     * @param array $fresh The fresh data for the row.
     */
    public function __construct(ConnectionInterface $connection, string $table, array $fresh = [])
    {
        $this->connection = $connection;
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

    public function __debugInfo()
    {
        return [
            'table' => $this->table,
            'load' => $this->load,
            'fresh' => $this->fresh,
            'alterations' => $this->alterations,
            'result' => $this->result,
        ];
    }

    public function get($name)
    {
        return $this->alterations[$name]
            ?? $this->fresh[$name]
            ?? $this->load[$name]
            ?? null;
    }

    public function set(string $name, $value = null)
    {
        $this->alterations[$name] = $value;
    }

    public function table(): string
    {
        return $this->table;
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

    public function load(array $datass = null): Rowinterface
    {
        $unique_match = $this->connection->schema()->matchUniqueness($this->table, $datass ?? $this->export());

        if (empty($unique_match)) {
            return $this;
        }

        $where = (new Where())->andFields($unique_match, $this->table, '=');

        $query = $this->connection->schema()->select($this->table)->add($where);
        $this->result = $this->connection->result($query);

        $res = $this->result->retOne(\PDO::FETCH_ASSOC);
        $this->load = $res === false ? null : $res;

        return $this;
    }

    /**
     * loops the associative data and records changes vis-à-vis loaded data
     * 
     * 1. skips non existing field name and A_I column
     * 2. replaces empty strings with null or default value
     * 3. checks for changes with loaded data. using == instead of === is risky but needed
     * 4. pushes the changes in the alterations tracker
     *
     *
     * @param  array<int|string,mixed> $datass an associative array containing the new data
     */
    public function alter(array $datass): RowInterface
    {
        foreach (array_keys($datass) as $field_name) {

            if($datass[$field_name] === $this->get($field_name)) {
                continue;
            }
            
            // Skip non-existing field names and auto-increment columns
            if (!$this->connection->schema()->hasColumn($this->table, $field_name)) {
                continue;
            }

            $attributes = $this->connection->schema()->attributes($this->table, $field_name);

            if ($attributes->isAuto()) {
                continue;
            }

            // Replace empty strings with null if the column is nullable
            if (trim('' . $datass[$field_name]) === '' && $attributes->nullable()) {
                $datass[$field_name] = null;
            }

            // checks for changes with loaded data. using == instead of === is risky but needed
            if ($this->isNew() || $this->load[$field_name] != $datass[$field_name]) {
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
        } catch (CruditesException $cruditesException) {
            return [$this->table => $cruditesException->getMessage()];
        }

        return [];
    }

    /**
     * Creates a new record in the database.
     * Executes an insert query with the current data and updates the alterations tracker with the auto-incremented primary key value if applicable.
     */
    private function create(): void
    {
        $query = $this->connection->schema()->insert($this->table, $this->export());
        $this->result = $this->connection->result($query);

        // creation might lead to auto_incremented changes
        // recovering auto_incremented value and pushing it in alterations tracker
        $aipk = $this->connection->schema()->autoIncrementedPrimaryKey($this->table);
        if ($aipk !== null) {
            $this->set($aipk, $this->result->lastInsertId());
        }
    }

    /**
     * Updates the existing record in the database with the current alterations.
     *
     * @throws CruditesException if a unique match is not found.
     */
    private function update(): void
    {
        $unique_match = $this->connection->schema()->matchUniqueness($this->table, $this->load);

        if (empty($unique_match)) {
            throw new CruditesException('UNIQUE_MATCH_NOT_FOUND');
        }

        $query = $this->connection->schema()->update($this->table, $this->alterations, $unique_match);
        $this->result = new Result($this->connection->pdo(), $query);
    }

    /**
     * Deletes the current record from the database.
     * 
     * @return bool true if the record was deleted, false otherwise.
     * @throws CruditesException if a unique match is not found.
     */
    public function wipe(): bool
    {
        $datass = $this->load ?? $this->fresh ?? $this->alterations;

        // need The Primary key, then you can wipe at ease
        if (!empty($pk_match = $this->connection->schema()->matchPrimaryKeys($this->table, $datass))) {
            $query = $this->connection->schema()->delete($this->table, $pk_match);

            $this->result = $this->connection->result($query);
            return $this->result->ran();
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

        foreach ($this->connection->schema()->columns($this->table) as $column_name) {

            $attribute = $this->connection->schema()->attributes($this->table, $column_name);
            $column_errors = $attribute->validateValue($datass[$column_name] ?? null);

            if (!empty($column_errors)) {
                $errors[$column_name] = $column_errors;
            }
        }

        return $errors;
    }
}
