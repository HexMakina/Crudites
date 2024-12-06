<?php

namespace HexMakina\Crudites\Table;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\RowInterface;
use HexMakina\BlackBox\Database\QueryInterface;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\Result;
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

    private ?QueryInterface $last_query = null;
    private ?QueryInterface $last_alter_query = null;

    private ?Result $last_result = null;
    private ?Result $last_alter_result = null;


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
            'last_query' => $this->last_query,
            'last_alter_query' => $this->last_alter_query,
            'last_result' => $this->last_result,
            'last_alter_result' => $this->last_alter_result,
        ];
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

    public function table(): string
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

    public function load(array $datass = null): Rowinterface
    {
        $unique_match = $this->connection->schema()->matchUniqueness($this->table, $datass ?? $this->export());
        
        if (empty($unique_match)) {
            return $this;
        }

        $where = (new Where())->andFields($unique_match, $this->table, '=');

        $this->last_query = $this->connection->schema()->select($this->table)->add($where);
        $this->last_result = new Result($this->connection->pdo(), $this->last_query);
        
        $res = $this->last_result->ret(\PDO::FETCH_ASSOC);
        $this->load = (is_array($res) && count($res) === 1) ? current($res) : null;

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
    public function alter(array $datass): Rowinterface
    {
        foreach (array_keys($datass) as $field_name) {
            // skips non existing field name and A_I column
            if ($this->connection->schema()->hasColumn($this->table, $field_name)) {
                continue;
            }

            $attributes = $this->connection->schema()->attributes($this->table, $field_name);

            if ($attributes->isAuto()) {
                continue;
            }

            // replaces empty strings with null or default value
            if (trim('' . $datass[$field_name]) === '' && $attributes->nullable()) {
                $datass[$field_name] = $attributes->nullable() ? null : $attributes->default();
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

        } catch (CruditesException $cruditesException) {
            return [$this->table => $cruditesException->getMessage()];
        }

        return [];
    }

    private function create(): void
    {
        $this->last_alter_query = $this->connection->schema()->insert($this->table, $this->export());
        $this->last_alter_result = new Result($this->connection->pdo(), $this->last_alter_query);

        // creation might lead to auto_incremented changes
        // recovering auto_incremented value and pushing it in alterations tracker
        $aipk = $this->connection->schema()->autoIncrementedPrimaryKey($this->table);
        if ($aipk !== null) {
            $this->alterations[$aipk] = $this->connection->lastInsertId();
        }
    }

    private function update(): void
    {
        
        $unique_match = $this->connection->schema()->matchUniqueness($this->table, $this->load);

        if(empty($unique_match)){
            throw new CruditesException('UNIQUE_MATCH_NOT_FOUND');
        }

        $this->last_alter_query = $this->connection->schema()->update($this->table, $this->alterations, $unique_match);
        $this->last_alter_result = new Result($this->connection->pdo(), $this->last_alter_query);
    }

    public function wipe(): bool
    {
        $datass = $this->load ?? $this->fresh ?? $this->alterations;

        // need The Primary key, then you can wipe at ease
        if (!empty($pk_match = $this->connection->schema()->matchPrimaryKeys($this->table, $datass))) {
            $this->last_alter_query = $this->connection->schema()->delete($this->table, $pk_match);
            
            try {
                
                $this->last_alter_result = new Result($this->connection->pdo(), $this->last_alter_query);
                $this->last_query = $this->lastAlterQuery();

            } catch (CruditesException $cruditesException) {
                return false;
            }

            return $this->last_alter_result->isSuccess();
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
            $errors = $attribute->validateValue($datass[$column_name] ?? null);

            if (!empty($errors)) {
                $errors[$column_name] = $errors;
            }
        }

        return $errors;
    }
}
