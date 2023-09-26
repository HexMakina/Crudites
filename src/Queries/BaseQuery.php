<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\QueryInterface;
use HexMakina\Crudites\CruditesExceptionFactory;

abstract class BaseQuery implements QueryInterface
{
    protected  const STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"

    protected $connection;

    protected $statement;

    protected $table;
    protected $table_alias;
    protected $clauses;

    protected $executed = false;

    //------------------------------------------------------------  DEBUG
    public function __debugInfo(): array
    {
        $dbg = [];
        if ($this->table !== null) {
            $dbg['table_name()'] = $this->tableName();
        }

        $dbg = array_merge($dbg, get_object_vars($this));
        unset($dbg['table']);
        unset($dbg['connection']);

        foreach (array_keys($dbg) as $k) {
            if (!isset($dbg[$k])) {
                unset($dbg[$k]);
            }
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

    public function addClause(string $clause, $argument): self
    {
        if(!is_array($argument))
            $argument = [$argument];

        $this->clauses[$clause] ??= [];
        $this->clauses[$clause] = array_unique(array_merge($this->clauses[$clause], $argument));

        return $this;
    }

    public function setClause($clause, $argument=null): self
    {
        if(is_null($argument)){
            unset($this->clauses[$clause]);
        }
        else{
            $this->clauses[$clause] = [];
            $this->addClause($clause, $argument);
        }

        return $this;
    }

    public function clause($clause) : array
    {
        return $this->clauses[$clause] ?? [];
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
        return sprintf('`%s`.`%s`', $this->tableLabel($table_name), $field_name);
    }

    //------------------------------------------------------------  Run
    // throws CruditesException on failure
    // returns itself
    public function run(): self
    {
        try {
            $this->executed = $this->connection()->query($this->statement());
        } catch (\PDOException $pdoException) {
            // prevents PDOException with credentials to be ever displayed
            throw CruditesExceptionFactory::make($this, $pdoException);
        }

        return $this;
    }

    //------------------------------------------------------------  Return
    public function ret($mode = null, $option = null)
    {
        if (!$this->isSuccess()) {
            return false;
        }
        if (is_null($option)) {
            return $this->executed()->fetchAll($mode);
        }

        return $this->executed()->fetchAll($mode, $option);
    }

    //------------------------------------------------------------ Return:count
    public function count(): int
    {
        // careful: https://www.php.net/manual/en/pdostatement.rowcount.php
        return $this->isSuccess() ? $this->executed()->rowCount() : -1;
    }

    //------------------------------------------------------------  Status
    public function isExecuted(): bool
    {
        return $this->executed instanceof \PDOStatement;
    }

    public function executed(): \PDOStatement
    {
        if (!$this->isExecuted()) {
            $this->run();
        }

        return $this->executed;
    }

    public function isSuccess(): bool
    {
        return $this->executed()->errorCode() === self::STATE_SUCCESS;
    }

    /**
     * @return mixed[]
     */
    public function errorInfo(): array
    {
        return $this->connection()->errorInfo();
    }

    public function errorMessageWithCodes(): string
    {
        list($state, $code, $message) = $this->errorInfo();
        return sprintf('%s (state: %s, code: %s)', $message, $state, $code);
    }

    public function compare(QueryInterface $query)
    {
        if ($this->statement() !== $query->statement()) {
            return 'statement';
        }

        return true;
    }
}
