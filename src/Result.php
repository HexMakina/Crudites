<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\QueryInterface;

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
    private ?\PDOStatement $executed;

    // string or PDOStatement
    private $statement;

    private array $bindings;


    public const STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"


    /**
     * @param \PDO $pdo
     * @param string|\PDOStatement $statement, the SQL statement to run, raw or prepared
     * @param array $bindings, optional bindings for the prepared statement's execution
     */
    public function __construct(\PDO $pdo, $statement, array $bindings = [])
    {
        $this->pdo = $pdo;

        if ($statement instanceof QueryInterface) {
            $this->statement = (string)$statement;
            $bindings = $statement->bindings();
        } else {
            $this->statement = $statement;
        }

        if ($this->statement instanceof \PDOStatement)
            $this->prepared = $this->statement;

        $this->run($bindings);
    }

    /**
     * Magic method, transfers calls to the executed PDOStatement instance
     * fetch(), fetchAll(), fetchColumn(), fetchObject(), bindColumn(), bindParam(), bindValue(), rowCount(), columnCount(), errorCode(), errorInfo()
     */

    public function __call($method, $args)
    {
        // first use the executed instance, then the prepared one
        // make senses for chronology and error handling
        $pdo_statement = $this->executed ?? $this->prepared;

        if ($pdo_statement === null)
            throw new CruditesException('both executed and prepared instances are null, cannot call PDOStatement method ' . $method);

        if (!method_exists($pdo_statement, $method))
            throw new \BadMethodCallException("method $method not found in PDOStatement instance");

        return call_user_func_array([$pdo_statement, $method], $args);
    }

    /**
     * Runs the statement with the given bindings (optional)
     * Without bindings, the string statement is executed with PDO::query()
     * 
     * 
     * @param array $bindings
     * @return self
     * @throws CruditesException if the statement could not be queried, executed or prepared or if a PDOException is thrown
     */
    public function run(array $bindings = [])
    {
        // (re)set the executed PDOStatement instance
        $this->executed = null;
        $this->bindings = $bindings;
        try {

            // is prepared, execute it with bindings
            if (isset($this->prepared)) {
                if ($this->prepared->execute($bindings) !== false) {
                    $this->executed = $this->prepared;
                    $this->bindings = $bindings;

                    return $this;
                }

                throw new CruditesException('PDOSTATEMENT_EXECUTE');
            }

            // not prepared, no bindings, PDO::query the statement
            if (empty($bindings)) {
                if (($res = $this->pdo->query((string)$this->statement)) !== false) {
                    $this->executed = $res;
                    return $this;
                }

                throw new CruditesException('PDO_QUERY_STRING');
            }

            // PDO::prepare it and recursively call run() with bindings
            if (($res = $this->pdo->prepare((string)$this->statement)) !== false) {
                $this->prepared = $res;
                return $this->run($bindings);
            }

            throw new CruditesException('PDO_PREPARE_STRING');
        } catch (\PDOException $e) {
            throw new CruditesException('PDO_EXCEPTION: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * Returns true if the statement has been executed without error
     * @return bool
     */
    public function ran(): bool
    {
        return $this->executed !== null && $this->executed->errorCode() === \PDO::ERR_NONE;
    }

    /**
     * Returns the result set (by default as an array of associative arrays) 
     * A wrapper for PDOStatement::fetchAll()
     * 
     */
    public function ret($mode = \PDO::FETCH_ASSOC, $fetch_argument = null, $ctor_args = null)
    {
        if($mode === \PDO::FETCH_CLASS)
            return $this->executed->fetchAll($mode, $fetch_argument, $ctor_args);
        
        return $this->executed->fetchAll($mode);
    }

    /**
     * Returns the first row of the result set
     * A wrapper for PDOStatement::fetch()
     * 
     * @param int $mode 
     * @param mixed $orientation
     * @param mixed $offset 
     * @return mixed
     * 
     */
    public function retOne($mode = \PDO::FETCH_ASSOC, $orientation = null, $offset = null)
    {
        return $this->executed->fetch($mode, $orientation, $offset);
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     * A wrapper for PDOStatement::rowCount()
     * 
     * @return int
     */
    public function count(): int
    {
        return $this->ran() ? $this->executed->rowCount() : -1;
    }

    /**
     * Returns the last inserted ID
     * A wrapper for PDO::lastInsertId()
     * 
     * @param string $name
     * @return string
     */
    public function lastInsertId($name = null)
    {
        $id = $this->pdo->lastInsertId($name);
    }

    /** 
     * Returns the error info of the last operation (execution, preparation or query)
     * A wrapper for PDOStatement::errorInfo() and PDO::errorInfo()
     * 
     * @return array
     */
    public function errorInfo(): array
    {
        if ($this->executed !== null)
            return $this->executed->errorInfo();

        if ($this->prepared !== null)
            return $this->prepared->errorInfo();

        return $this->pdo->errorInfo();
    }

    /**
     * Returns a formatted error message of the last operation (execution, preparation or query)
     * Format is: "message (state: code)"
     * 
     * @return string
     */
    public function errorMessageWithCodes(): string
    {
        list($state, $code, $message) = $this->errorInfo();
        return sprintf('%s (state: %s, code: %s)', $message, $state, $code);
    }


    // a few shorthands for ret() parameters

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
