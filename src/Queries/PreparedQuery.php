<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\PreparedQueryInterface;

abstract class PreparedQuery extends BaseQuery implements PreparedQueryInterface
{
    private static int $executions = 0;

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


    abstract public function generate(): string;

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

    /**
     * @return array<int|string, string>
     */
    public function addBindings($assoc_data): array
    {
        $binding_names = [];
        foreach ($assoc_data as $k => $v) {
            $binding_names[$k] = $this->addBinding($k, $v);
        }
        return $binding_names;
    }

    public function addBinding($field, $value, $table_name = null, $bind_label = null): string
    {
        $bind_label ??= $this->bindLabel($field, $table_name);
        $this->bindings[$bind_label] = $value;
        $this->binding_names[$field] = $bind_label;

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
            
            if($this->prepared()->execute($this->getBindings()) === true);
                $this->executed = $this->prepared();

        } catch (\PDOException $pdoException) {
            throw (new CruditesException($pdoException->getMessage()))->fromQuery($this);
        }

        return $this;
    }


    //------------------------------------------------------------  Status
    public function isPrepared(): bool
    {
        return $this->prepared instanceof \PDOStatement;
    }

    public function prepared(): \PDOStatement
    {
        if (!$this->isPrepared()) {
            $this->prepared = $this->connection()->prepare($this->statement());
        }

        return $this->prepared;
    }

    public function errorInfo(): array
    {
        if($this->isExecuted())
            return $this->executed()->errorInfo();

        return parent::errorInfo();
    }
    
    public function compare($query)
    {
        $res = parent::compare($query);

        if($res !== true)
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
