<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\{CruditesException, CruditesExceptionFactory};

abstract class PreparedQuery extends BaseQuery 
{
    protected array $bindings = [];

    protected array $binding_names = [];

    protected $prepared;

    //------------------------------------------------------------  DEBUG
    public function __debugInfo(): array
    {
        $dbg = parent::__debugInfo();

        $dbg['bindings'] = json_encode($dbg['bindings']);
        if (empty($this->bindings)) {
            unset($dbg['bindings']);
        }

        return $dbg;
    }


    //------------------------------------------------------------  PREP::BINDINGS

    public function setBindings($dat_ass): void
    {
        $this->bindings = $dat_ass;
    }

    /**
     * @return mixed[]
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @return mixed[]
     */
    public function getBindingNames(): array
    {
        return $this->binding_names;
    }

    public function addBinding($field, $value, $table_name = null, $bind_label = null): string
    {
        $table_label = $this->tableLabel($table_name);
        $bind_label ??= $this->bindLabel($field, $table_name);

        $this->binding_names[$table_label] ??= [];
        $this->binding_names[$table_label][$field] = $bind_label;
        $this->bindings[$bind_label] = $value;

        return $bind_label;
    }

    public function bindLabel($field, $table_name = null): string
    {
        return ':' . $this->tableLabel($table_name) . '_' . $field;
    }

    //------------------------------------------------------------  Run
    // throws CruditesException on failure
    // returns itself
    public function run(): self
    {

        try {
            if ($this->prepared() === null)
                throw new CruditesException('QUERY_NOT_PREPARED');

            // https://www.php.net/manual/en/pdostatement.execute.php
            $res = $this->prepared()->execute($this->getBindings());

            if ($res) {
                $this->executed = $this->prepared();
            }
        } catch (\PDOException $pdoException) {
            throw CruditesExceptionFactory::make($this, $pdoException);
        }

        return $this;
    }

    public function isPrepared(): bool
    {
        return $this-> prepared !== null;
    }

    
    public function addBindings($assoc_data): array
    {
        $ret = [];
        foreach ($assoc_data as $column_name => $value) {
            $ret[$column_name] = $this->addBinding($column_name, $value, $this->table);
        }
        return $ret;
    }

    public function prepared(): ?\PDOStatement
    {
        if ($this->prepared === null)
            $this->prepare();

        return $this->prepared;
    }

    public function prepare(): self
    {
        $res = $this->connection()->prepare($this->statement());

        if ($res === null) {
            throw new CruditesException('QUERY_PREPARATION_FAILED');
        }

        $this->prepared = $res;
        return $this;
    }

    public function errorInfo(): array
    {
        if ($this->isExecuted())
            return $this->executed()->errorInfo();

        if ($this->prepared() !== null)
            return $this->prepared()->errorInfo();

        return parent::errorInfo();
    }

    public function compare($query)
    {
        $res = parent::compare($query);

        if ($res !== true)
            return $res;

        if (!empty(array_diff($this->getBindings(), $query->getBindings()))) {
            return 'bindings';
        }
        if (!empty(array_diff($query->getBindings(), $this->getBindings()))) {
            return 'bindings';
        }

        return true;
    }
}
