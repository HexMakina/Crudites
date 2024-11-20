<?php

namespace HexMakina\Crudites\Table;

use HexMakina\BlackBox\Database\RowInterface;
use HexMakina\BlackBox\Database\QueryInterface;
use HexMakina\BlackBox\Database\SchemaInterface;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\CruditesExceptionFactory;

class Row implements RowInterface
{
    private string $table;

    private SchemaInterface $schema;

    /** @var array<int|string,mixed>|null $load from database */
    private ?array $load = null;

    /** @var array<int|string,mixed> $fresh from the constructor */
    private array $fresh = [];

    /** @var array<int|string,mixed> $alterations during lifecycle */
    private array $alterations = [];

    private ?QueryInterface $last_query = null;
    private ?QueryInterface $last_alter_query = null;


    /** @param array<string,mixed> $datass */
    /**
     * Represents a row in a table.
     *
     * @param TableInterface $table The table to which the row belongs.
     * @param array $fresh The fresh data for the row.
     */
    public function __construct(SchemaInterface $schema, string $table, array $fresh = [])
    {
        $this->schema = $schema;
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

    public function load(array $datass): Rowinterface
    {
        $unique_match = $this->schema->matchUniqueness($this->table, $datass);
        
        if (empty($unique_match)) {
            return $this;
        }

        $this->last_query = $this->schema->select($this->table)->whereFieldsEQ($unique_match);
        $res = $this->last_query->prepare()->run()->ret(\PDO::FETCH_ASSOC);
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
            // skips non existing field name and A_I column
            if ($this->schema->hasColumn($this->table, $field_name)) {
                continue;
            }

            $attributes = $this->schema->attributes($this->table, $field_name);

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
            if(is_null($this->lastQuery()) || !$this->lastQuery()->isSuccess()){
                $res = CruditesExceptionFactory::make($this->last_query);
                throw $res;
            }
            
        } catch (CruditesException $cruditesException) {
            return [$this->table => $cruditesException->getMessage()];
        }

        return [];
    }

    private function create(): void
    {
        $this->last_alter_query = $this->schema->insert($this->table, $this->export());
        $this->lastAlterQuery()->connection($this->schema->connection());
        $this->lastAlterQuery()->prepare();
        $this->lastAlterQuery()->run();

        // creation might lead to auto_incremented changes
        // recovering auto_incremented value and pushing it in alterations tracker
        if ($this->lastAlterQuery()->isSuccess()) {
            $aipk = $this->schema->autoIncrementedPrimaryKey($this->table);
            if (!is_null($aipk)) {
                $this->alterations[$aipk] = $this->lastAlterQuery()->connection()->lastInsertId();
            }
        }
    }

    private function update(): void
    {
        $unique_match = $this->schema->matchUniqueness($this->table, $this->load);

        if(empty($unique_match)){
            throw new CruditesException('UNIQUE_MATCH_NOT_FOUND');
        }

        $this->last_alter_query = $this->schema->update($this->table, $this->alterations, $unique_match);
        $this->lastAlterQuery()->connection($this->schema->connection());
        $this->lastAlterQuery()->prepare();
        $this->lastAlterQuery()->run();
    }

    public function wipe(): bool
    {
        $datass = $this->load ?? $this->fresh ?? $this->alterations;

        // need The Primary key, then you can wipe at ease
        if (!empty($pk_match = $this->schema->matchPrimaryKeys($this->table, $datass))) {
            $this->last_alter_query = $this->schema->delete($this->table, $pk_match);
            $this->lastAlterQuery()->connection($this->schema->connection());

            try {

                $this->lastAlterQuery()->prepare();
                $this->lastAlterQuery()->run();

                $this->last_query = $this->lastAlterQuery();

            } catch (CruditesException $cruditesException) {
                return false;
            }

            return $this->lastAlterQuery()->isSuccess();
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

        foreach ($this->schema->columns($this->table) as $column_name) {

            $attribute = $this->schema->attributes($this->table, $column_name);
            $errors = $attribute->validateValue($datass[$column_name] ?? null);

            if (!empty($errors)) {
                $errors[$column_name] = $errors;
            }
        }

        return $errors;
    }
}
