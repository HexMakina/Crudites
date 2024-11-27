<?php

namespace HexMakina\Crudites;

/**
 * Provides a simple interface to run statements, string or PDOStatements
 * 
 * It encapsulates a PDOStatement instance and provides methods for fetching results.
 * All PDOStatement methods are available through the magic __call method.
 * 
 * 
 */
class Result
{
    private \PDO $pdo;
    private \PDOStatement $prepared;
    private \PDOStatement $executed;

    private $statement; // string or PDOStatement
    private array $bindings;


    public const STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"
    
    
    public function __construct(\PDO $pdo, $statement, array $bindings = [])
    {
        $this->pdo = $pdo;
        $this->statement = $statement;
    
        $this->run($bindings);
    }

    /**
     * Magic method, transfers calls to the executed PDOStatement instance
     * fetch(), fetchAll(), fetchColumn(), fetchObject(), bindColumn(), bindParam(), bindValue(), rowCount(), columnCount(), errorCode(), errorInfo()
     */

    public function __call($method, $args)
    {
        if ($this->executed === null)
            throw new CruditesException('RUN_QUERY_BEFORE_CALLING_METHOD');

        if (!method_exists($this->executed, $method))
            throw new \BadMethodCallException(__FUNCTION__ . " method $method() does not exist in PDOStatement class");

        return call_user_func_array([$this->executed, $method], $args);
    }

    public function run($bindings = [])
    {
        $this->bindings = $bindings; // keep it for debugging

        // PDO::query the SQL statement or PDO::prepare it for later execution
        if (is_string($this->statement)) {

            if (empty($bindings)) {

                $res = $this->pdo->query($this->statement);

                if ($res === false) {
                    throw new CruditesException('PDO_QUERY_STRING');
                }

                $this->executed = $res;

            } else if ($this->prepared === null) {
                $res = $this->pdo->prepare($this->statement);

                if ($res === false) {
                    throw new CruditesException('PDO_PREPARE_STRING');
                }

                $this->prepared = $res;
            }
        }
        else if($this->statement instanceof \PDOStatement){
            $this->prepared = $this->statement;
        }
        else{
            throw new CruditesException('STATEMENT_TYPE_STRING_OR_PDOSTATEMENT');
        }


        if($this->executed === null){
            if ($this->prepared->execute($bindings) === false) {
                throw new CruditesException('PDOSTATEMENT_EXECUTE');
            }

            $this->executed = $this->prepared;
        }

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->executed !== null && $this->executed->errorCode() === \PDO::ERR_NONE;
    }

    public function count(): int
    {
        return $this->isSuccess() ? $this->executed->rowCount() : -1;
    }

    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    public function errorInfo(): array
    {
        if ($this->executed !== null)
            return $this->executed->errorInfo();

        if ($this->prepared !== null)
            return $this->prepared->errorInfo();

        return $this->pdo->errorInfo();
    }

    public function errorMessageWithCodes(): string
    {
        list($state, $code, $message) = $this->errorInfo();
        return sprintf('%s (state: %s, code: %s)', $message, $state, $code);
    }


    public function ret($mode = \PDO::FETCH_ASSOC, $fetch_argument = null, $ctor_args = null)
    {
        return $this->executed->fetchAll($mode, $fetch_argument, $ctor_args);
    }

    public function retOne($mode = \PDO::FETCH_ASSOC, $orientation = null, $offset = null)
    {
        return $this->executed->fetch($mode, $orientation, $offset);
    }

    public function retObj($c = null)
    {
        return $c === null ? $this->ret(\PDO::FETCH_OBJ) : $this->ret(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $c);
    }

    public function retNum()
    {
        return $this->ret(\PDO::FETCH_NUM);
    }

    public function retAss()
    {
        return $this->ret(\PDO::FETCH_ASSOC);
    }

    //ret: array indexed by column name
    public function retCol()
    {
        return $this->ret(\PDO::FETCH_COLUMN);
    }

    //ret: all values of a single column from the result set
    public function retPar()
    {
        return $this->ret(\PDO::FETCH_KEY_PAIR);
    }

    public function retKey()
    {
        return $this->ret(\PDO::FETCH_UNIQUE);
    }
}
