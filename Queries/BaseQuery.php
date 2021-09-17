<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableManipulationInterface;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\QueryInterface;

abstract class BaseQuery implements QueryInterface
{
    private static $executions = 0;

    private const STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"

    protected $table = null;
    protected $statement = null;
    protected $bindings = [];

    protected $connection = null;
    protected $executed = false;
    protected $state = null;

    protected $prepared_statement = null;

    protected $row_count = null;

    protected $error_code = null;
    protected $error_text = null;

    //------------------------------------------------------------  DEBUG
    public function __debugInfo(): array
    {
        $dbg = [];
        if (isset($this->table)) {
            $dbg['table_name()'] = $this->tableName();
        }

        $dbg = array_merge($dbg, get_object_vars($this));
        unset($dbg['table']);

        foreach ($dbg as $k => $v) {
            if (!isset($dbg[$k])) {
                unset($dbg[$k]);
            }
        }

        $dbg['bindings'] = json_encode($dbg['bindings']);
        if (empty($this->bindings)) {
            unset($dbg['bindings']);
        }

        $dbg['statement()'] = $this->statement();
        return $dbg;
    }

    public function __toString()
    {
        return $this->statement();
    }


    abstract public function generate(): string;


    //------------------------------------------------------------  GET/SETTERS
    public function statement($setter = null): string
    {
        if (!is_null($setter)) {
            $this->statement = $setter;
        }

        return $this->statement ?? $this->generate();
    }


    public function connection(ConnectionInterface $setter = null): ConnectionInterface
    {
        if (!is_null($setter)) {
            $this->connection = $setter;
        }

        return $this->connection;
    }

    public function table(TableManipulationInterface $setter = null): TableManipulationInterface
    {
        return is_null($setter) ? $this->table : ($this->table = $setter);
    }

    public function tableName(): string
    {
        return $this->table()->name();
    }

    //------------------------------------------------------------  PREP::FIELDS
    public function tableLabel($table_name = null)
    {
        return $table_name ?? $this->tableName();
    }

    public function backTick($field_name, $table_name = null): string
    {
        if (empty($table_name)) {
            return sprintf('`%s`', $field_name);
        }
        return sprintf('`%s`.`%s`', $this->tableLabel($table_name), $field_name);
    }

    //------------------------------------------------------------  PREP::BINDINGS

    public function setBindings($dat_ass)
    {
        $this->bindings = $dat_ass;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

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
        $bind_label = $bind_label ?? $this->bindLabel($field, $table_name);
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
    // DEBUG dies on \Exception

    public function run(): QueryInterface
    {
        if (is_null($this->connection())) {
            throw new CruditesException('NO_CONNECTION');
        }
        try {
            if (!$this->isPrepared()) {
                $this->prepared_statement = $this->connection()->prepare($this->statement());
            }

            if ($this->prepared_statement->execute($this->getBindings()) !== false) {
                // execute returns TRUE on success or FALSE on failure.
                ++self::$executions;

                $this->isExecuted(true);

                if ($this->prepared_statement->errorCode() === self::STATE_SUCCESS) {
                    $this->state = self::STATE_SUCCESS;
                    // careful: https://www.php.net/manual/en/pdostatement.rowcount.php
                    $this->row_count = $this->prepared_statement->rowCount();
                }
            }
        } catch (\PDOException $e) {
            throw (new CruditesException($e->getMessage()))->fromQuery($this);
        }

        return $this;
    }

    //------------------------------------------------------------  Return
    public function ret($mode = null, $option = null)
    {
        if (!$this->isExecuted()) {
            $this->run();
        }

        if (!$this->isSuccess()) {
            return false;
        }

        if (is_null($option)) {
            return $this->prepared_statement->fetchAll($mode);
        }

        return $this->prepared_statement->fetchAll($mode, $option);
    }

    //------------------------------------------------------------ Return:count
    public function count()
    {
        if (!$this->isExecuted()) {
            $this->run();
        }

        return $this->isSuccess() ? $this->row_count : null;
    }

    //------------------------------------------------------------  Status
    public function isPrepared(): bool
    {
        return !is_null($this->prepared_statement) && false !== $this->prepared_statement;
    }

    public function isExecuted($setter = null): bool
    {
        return is_null($setter) ? $this->executed === true : ($this->executed = $setter);
    }

    public function isSuccess(): bool
    {
        return $this->state === self::STATE_SUCCESS;
    }

    public function errorInfo(): array
    {
        if ($this->isPrepared()) {
            return $this->prepared_statement->errorInfo();
        }

        return $this->connection()->errorInfo();
    }

    public function compare(QueryInterface $other)
    {
        if ($this->statement() !== $other->statement()) {
            return 'statement';
        }

        if (
            !empty(array_diff($this->getBindings(), $other->getBindings()))
            || !empty(array_diff($other->getBindings(), $this->getBindings()))
        ) {
            return 'bindings';
        }

        return true;
    }
}
