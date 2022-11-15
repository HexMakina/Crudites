<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\Queries\Describe;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\TableDescriptionInterface;
use HexMakina\BlackBox\Database\TableColumnInterface;

class Description implements TableDescriptionInterface
{
    protected ConnectionInterface $connection;

    protected string $name;

    // protected $ORM_class_name = null;

    /** @var array<string,TableColumnInterface> */
    protected array $columns = [];

    // auto_incremented_primary_key
    protected ?TableColumnInterface $aipk = null;


    /** @var array<string,TableColumnInterface> */
    protected array $primary_keys = [];

    /** @var array<string,TableColumnInterface> */
    protected array $unique_keys = [];


    /** @var array<string,TableColumnInterface> */
    protected array $foreign_keys_by_name = [];

    /** @var array<string,TableColumnInterface> */
    protected array $foreign_keys_by_table = [];




    public function __construct(string $table_name, ConnectionInterface $connection)
    {
        $this->name = $table_name;
        $this->connection = $connection;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    /** @return array<string,array> */
    public function describe(): array
    {
        $query = $this->connection()->query((new Describe($this->name())));
        if ($query === false) {
            throw new CruditesException('TABLE_DESCRIBE_FAILURE');
        }

        $ret = [];
        $res = $query->fetchAll(\PDO::FETCH_UNIQUE);

        if ($res === false) {
            throw new CruditesException('TABLE_DESCRIBE_FETCH_FAILURE');
        }

        foreach ($res as $column_name => $specs) {
            $ret [] = new Column($this, $column_name, $specs);
        }


        return $ret;
    }

    public function addColumn(TableColumnInterface $tableColumn): void
    {
        $this->columns[$tableColumn->name()] = $tableColumn;

        if ($tableColumn->isPrimary()) {
            $this->addPrimaryKey($tableColumn);
            if ($tableColumn->isAutoIncremented()) {
                $this->autoIncrementedPrimaryKey($tableColumn);
            }
        }
    }

    public function addPrimaryKey(TableColumnInterface $tableColumn): void
    {
        $this->primary_keys[$tableColumn->name()] = $tableColumn;
    }

    /** @param array<string,TableColumnInterface> $columns     */
    public function addUniqueKey(string $constraint_name, array $columns): void
    {
        if (!isset($this->unique_keys[$constraint_name])) {
            $this->unique_keys[$constraint_name] = $columns;
        }
    }

    public function addForeignKey(TableColumnInterface $tableColumn): void
    {
        // adds to the foreign key dictionary string column_name => TableColumnInterface
        $this->foreign_keys_by_name[$tableColumn->name()] = $tableColumn;

        // prepares the table name based index
        $name = $tableColumn->foreignTableName();
        if (!isset($this->foreign_keys_by_table[$name])) {
            $this->foreign_keys_by_table[$name] = [];
        }

        // adds to the index tring table_name => TableColumnInterface
        $this->foreign_keys_by_table[$name] [] = $tableColumn;
    }

    //getsetter of AIPK, default get is null, cant set to null
    public function autoIncrementedPrimaryKey(TableColumnInterface $tableColumn = null): ?\HexMakina\BlackBox\Database\TableColumnInterface
    {
        return is_null($tableColumn) ? $this->aipk : ($this->aipk = $tableColumn);
    }

    //------------------------------------------------------------  getters
    // TableDescriptionInterface implementation
    public function name(): string
    {
        return $this->name;
    }

    // TableDescriptionInterface implementation
    /**
     * @return array<string, \HexMakina\BlackBox\Database\TableColumnInterface>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    // TableDescriptionInterface implementation
    public function column(string $name): ?TableColumnInterface
    {
        return $this->columns[$name] ?? null;
    }

    // TableDescriptionInterface implementation
    /**
     * @return array<string, \HexMakina\BlackBox\Database\TableColumnInterface>
     */
    public function uniqueKeysByName(): array
    {
        return $this->unique_keys;
    }

    // TableDescriptionInterface implementation
    /**
     * @return array<string, \HexMakina\BlackBox\Database\TableColumnInterface>|array<string, mixed>
     */
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

    /**
     * @return mixed[]
     */
    public function matchUniqueness($dat_ass): array
    {
        $ret = $this->primaryKeysMatch($dat_ass);

        if (empty($ret)) {
            return $this->uniqueKeysMatch($dat_ass);
        }

        return $ret;
    }

    /*
     * @return array, empty on mismatch
     * @return array, assoc of column_name => $value on match
     * @throws CruditesException if no pk defined
     */
    /**
     * @return mixed[]
     */
    public function primaryKeysMatch($dat_ass): array
    {

        if ($this->primaryKeys() === []) {
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

    /**
     * @return mixed[]
     */
    public function uniqueKeysMatch($dat_ass): array
    {

        if ($this->uniqueKeysByName() === []) {
            return [];
        }
        if (!is_array($dat_ass)) {
            return [];
        }
        $keys = array_keys($dat_ass);

        foreach ($this->uniqueKeysByName() as $tableColumn) {
            $tableColumn = [$tableColumn];

            if (empty(array_diff($keys, $tableColumn))) {
                return $dat_ass;
            }
        }

        return [];
    }

    // TableDescriptionInterface implementation

    /** @return array<string,array> */
    public function foreignKeysByName(): array
    {
        return $this->foreign_keys_by_name;
    }

    // TableDescriptionInterface implementation

    /** @return array<string,array> */
    public function foreignKeysByTable(): array
    {
        return $this->foreign_keys_by_table;
    }

    /** @return ?array<TableColumnInterface> */
    public function singleForeignKeyTo(TableDescriptionInterface $tableDescription): ?array
    {
        $bonding_column_candidates = $this->foreignKeysByTable()[$tableDescription->name()] ?? [];

        if (count($bonding_column_candidates) === 1) {
            return current($bonding_column_candidates);
        }

        return null;
    }
}
