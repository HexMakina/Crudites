<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Crudites\{CruditesException,Schema};
use HexMakina\Crudites\Queries\Describe;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\TableMetaInterface;
use HexMakina\BlackBox\Database\ColumnInterface;

abstract class TableMeta implements TableMetaInterface
{
    protected ConnectionInterface $connection;

    protected string $name;

    // protected $ORM_class_name = null;

    /** @var array<string,ColumnInterface> */
    protected array $columns = [];

    // auto_incremented_primary_key
    protected ?ColumnInterface $aipk = null;


    /** @var array<string,ColumnInterface> */
    protected array $primary_keys = [];

    /** @var array<string,ColumnInterface> */
    protected array $unique_keys = [];


    /** @var array<string,ColumnInterface> */
    protected array $foreign_keys_by_name = [];

    /** @var array<string,ColumnInterface> */
    protected array $foreign_keys_by_table = [];


    public function __toString(): string
    {
        return $this->name;
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function describe($schema): void
    {
        $query = $this->connection()->query((new Describe($this->name())));
        if (is_null($query)) {
            throw new CruditesException('TABLE_DESCRIBE_FAILURE');
        }

        $res = $query->fetchAll(\PDO::FETCH_UNIQUE);

        if ($res === false) {
            throw new CruditesException('TABLE_DESCRIBE_FETCH_FAILURE');
        }

        foreach ($res as $column_name => $specs) {
            $column = new Column($this, $column_name, $specs);

            $this->setUniqueFor($column, $schema);
            $this->setForeignFor($column, $schema);

            $this->addColumn($column);

        }
    }

    private function setUniqueFor(ColumnInterface $column, Schema $schema): void
    {
      $constraint = $schema->uniqueConstraintNameFor($this->name(), $column->name());

      if(!is_null($constraint)){

        $columns = $schema->uniqueColumnNamesFor($this->name(), $column->name());

        $this->addUniqueKey($constraint, $columns);

        if(count($columns) === 1)
        {
          $column->uniqueName($constraint);
        }
        else
        {
          $column->uniqueGroupName($constraint);
        }
      }

    }

    private function setForeignFor(ColumnInterface $column, Schema $schema): void
    {
        $reference = $schema->foreignKeyFor($this->name(), $column->name());

        if (!is_null($reference)) {
            $column->isForeign(true);
            $column->setForeignTableName($reference[0]);
            $column->setForeignColumnName($reference[1]);

            $this->addForeignKey($column);
        }
    }


    public function addColumn(ColumnInterface $tableColumn): void
    {
        $this->columns[$tableColumn->name()] = $tableColumn;

        if ($tableColumn->isPrimary()) {
            $this->addPrimaryKey($tableColumn);
            if ($tableColumn->isAutoIncremented()) {
                $this->autoIncrementedPrimaryKey($tableColumn);
            }
        }
    }

    public function addPrimaryKey(ColumnInterface $tableColumn): void
    {
        $this->primary_keys[$tableColumn->name()] = $tableColumn;
    }

    /** @param array<string,ColumnInterface> $columns */
    public function addUniqueKey(string $constraint_name, array $columns): void
    {
        if (!isset($this->unique_keys[$constraint_name])) {
            $this->unique_keys[$constraint_name] = $columns;
        }
    }

    public function addForeignKey(ColumnInterface $tableColumn): void
    {
        // adds to the foreign key dictionary string column_name => ColumnInterface
        $this->foreign_keys_by_name[$tableColumn->name()] = $tableColumn;

        // prepares the table name based index
        $name = $tableColumn->foreignTableName();

        $this->foreign_keys_by_table[$name] ??= [];

        // adds to the index tring table_name => ColumnInterface
        $this->foreign_keys_by_table[$name] [] = $tableColumn;
    }

    //getsetter of AIPK, default get is null, cant set to null
    public function autoIncrementedPrimaryKey(ColumnInterface $tableColumn = null): ?ColumnInterface
    {
        return is_null($tableColumn) ? $this->aipk : ($this->aipk = $tableColumn);
    }

    //------------------------------------------------------------  getters
    // TableMetaInterface implementation
    public function name(): string
    {
        return $this->name;
    }

    // TableMetaInterface implementation
    /**
     * @return array<string, \HexMakina\BlackBox\Database\ColumnInterface>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    // TableMetaInterface implementation
    public function column(string $name): ?ColumnInterface
    {
        return $this->columns[$name] ?? null;
    }

    // TableMetaInterface implementation
    /**
     * @return array<string, \HexMakina\BlackBox\Database\ColumnInterface>
     */
    public function uniqueKeysByName(): array
    {
        return $this->unique_keys;
    }

    // TableMetaInterface implementation
    /**
     * @return array<string, \HexMakina\BlackBox\Database\ColumnInterface>|array<string, mixed>
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
            if (empty(array_diff($keys, [$tableColumn]))) {
                return $dat_ass;
            }
        }

        return [];
    }

    // TableMetaInterface implementation

    /** @return array<string,ColumnInterface> */
    public function foreignKeysByName(): array
    {
        return $this->foreign_keys_by_name;
    }

    // TableMetaInterface implementation

    /** @return array<string,ColumnInterface> */
    public function foreignKeysByTable(): array
    {
        return $this->foreign_keys_by_table;
    }

    public function singleForeignKeyTo(TableMetaInterface $tableMeta): ?ColumnInterface
    {
        $bonding_column_candidates = $this->foreignKeysByTable()[$tableMeta->name()] ?? [];

        if (count($bonding_column_candidates) === 1) {
            return current($bonding_column_candidates);
        }

        return null;
    }
}
