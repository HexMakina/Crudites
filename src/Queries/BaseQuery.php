<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\QueryInterface;

abstract class BaseQuery implements QueryInterface
{
    private static int $executions = 0;

    /**
     * @var string
     */
    private const STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"

    protected $table;

    protected $table_alias;

    protected $statement;

    protected array $bindings = [];

    protected array $binding_names = [];

    protected $connection;

    protected $executed = false;

    protected $state;

    protected $prepared_statement;

    protected $row_count;

    protected $error_code;

    protected $error_text;

    //------------------------------------------------------------  DEBUG
    public function __debugInfo(): array
    {
        $dbg = [];
        if ($this->table !== null) {
            $dbg['table_name()'] = $this->tableName();
        }

        $dbg = array_merge($dbg, get_object_vars($this));
        unset($dbg['table']);

        foreach (array_keys($dbg) as $k) {
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


    public function connection(ConnectionInterface $connection = null): ConnectionInterface
    {
        if (!is_null($connection)) {
            $this->connection = $connection;
        }

        if (is_null($this->connection)) {
            throw new CruditesException('NO_CONNECTION');
        }

        return $this->connection;
    }

    public function table(TableInterface $table = null): TableInterface
    {
        return is_null($table) ? $this->table : ($this->table = $table);
    }

    public function tableName(): string
    {
        return $this->table()->name();
    }

    public function addPart($group, $part): self
    {
        $this->{$group} ??= [];
        $this->{$group}[] = $part;
        return $this;
    }

    //------------------------------------------------------------  PREP::FIELDS
    public function tableLabel($table_name = null)
    {
        return $table_name ?? $this->tableName();
    }

    public function tableAlias($setter = null): string
    {
        if (!is_null($setter)) {
            $this->table_alias = $setter;
        }

        return $this->table_alias ?? $this->tableName();
    }

    public function backTick($field_name, $table_name = null): string
    {
        if (empty($table_name)) {
            return sprintf('`%s`', $field_name);
        }

        return sprintf('`%s`.`%s`', $this->tableLabel($table_name), $field_name);
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
    // DEBUG dies on \Exception

    public function run(): self
    {
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
        } catch (\PDOException $pdoException) {
            throw (new CruditesException($pdoException->getMessage()))->fromQuery($this);
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

    /**
     * @return mixed[]
     */
    public function errorInfo(): array
    {
        if ($this->isPrepared()) {
            return $this->prepared_statement->errorInfo();
        }

        return $this->connection()->errorInfo();
    }

    public function compare(QueryInterface $query)
    {
        if ($this->statement() !== $query->statement()) {
            return 'statement';
        }
        if (!empty(array_diff($this->getBindings(), $query->getBindings()))) {
            return 'bindings';
        }
        if (!empty(array_diff($query->getBindings(), $this->getBindings()))) {
            return 'bindings';
        }

        return true;
    }
}
