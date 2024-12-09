<?php


/**
 * Provides a simple interface to run sql statements
 * Main methods: run, ran, ret
 * 
 * Constructor
 *      allows statement as string, PDOStatements or QueryInterface
 *      calls run() with provided bindings (optionals)
 * 
 * All PDOStatement methods are available by encapsulation through the magic __call method.
 * 
 * Fetching:
 *      ret     wrapper for fetchAll()
 *      retOne  wrapper for fetch()
 *      retObject  fetchAll(\PDO::FETCH_OBJ) unless a class name is provided then fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE)
 * 
 * Here is a list of all the PDO fetch constants:
 *
 * PDO::FETCH_ASSOC     : Returns rows as an associative array, with column names as keys.
 * PDO::FETCH_NUM       : Returns rows as a numeric array, with column indexes as keys.
 * PDO::FETCH_BOTH      : Default fetch style; returns rows as both an associative and numeric array.
 * PDO::FETCH_OBJ       : Returns rows as an object with property names corresponding to column names.
 * PDO::FETCH_LAZY      : Combines PDO::FETCH_BOTH, PDO::FETCH_OBJ, and PDO::FETCH_BOUND. Allows accessing columns in multiple ways.
 * PDO::FETCH_BOUND     : Assigns columns to PHP variables using bindColumn().
 * PDO::FETCH_CLASS     : Maps rows to a specified class, optionally calling a constructor.
 * PDO::FETCH_INTO      : Updates an existing object with column values.
 * PDO::FETCH_GROUP     : Groups rows by the first column's values.
 * PDO::FETCH_UNIQUE    : Uses the first column's values as keys, ensuring unique rows.
 * PDO::FETCH_COLUMN    : Returns a single column from each row.
 * PDO::FETCH_KEY_PAIR  : Fetches rows as key-value pairs, using the first two columns.
 * PDO::FETCH_FUNC      : Passes each row's data to a user-defined function and returns the result.
 * PDO::FETCH_NAMED     : Similar to PDO::FETCH_ASSOC, but handles duplicate column names by returning an array of values for each name.
 * PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE : Used with PDO::FETCH_CLASS, delays property population until after the constructor has been called.
 * PDO::FETCH_SERIALIZE : Serializes the returned data (requires specific driver support).
 */

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\QueryInterface;
use HexMakina\BlackBox\Database\ResultInterface;

class Result implements ResultInterface
{
    private \PDO $pdo;
    private \PDOStatement $prepared;
    private ?\PDOStatement $executed = null;

    // string or PDOStatement
    private $statement;

    private array $bindings = [];


    public const PDO_STATE_SUCCESS = '00000'; //PDO "error" code for "all is fine"


    /**
     * @param \PDO $pdo
     * @param string|\PDOStatement|QueryInterface $statement, the SQL statement to run, raw or prepared
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
     * transfers calls to the executed (or prepared) PDOStatement instance, if the method exists
     * if no statements are available, it is transferred to the PDO instance, if the method exists
     * if no call is possible, a BadMethodCallException is thrown
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        // first use the executed instance, then the prepared one
        // make senses for chronology and error handling
        $pdo_cascade = $this->executed ?? $this->prepared;

        if ($pdo_cascade === null || !method_exists($pdo_cascade, $method)) {
            $pdo_cascade = $this->pdo;
        }
        // two time testing is necessary, f.i. lastInsertId is a PDO method, not a PDOStatement method
        if ($pdo_cascade === null || !method_exists($pdo_cascade, $method))
            throw new \BadMethodCallException("__call($method) not possible in PDO or PDOStatement");

        return call_user_func_array([$pdo_cascade, $method], $args);
    }

    public function run(array $bindings = [])
    {
        // (re)set the executed PDOStatement instance
        $this->executed = null;
        $this->bindings = $bindings;
        if (isset($this->prepared)) {
            return $this->executePrepared($bindings);
        }

        if (empty($bindings)) {
            return $this->queryStatement();
        }

        return $this->prepareAndRun($bindings);
    }

    public function ran(): bool
    {
        return $this->executed !== null && $this->executed->errorCode() === self::PDO_STATE_SUCCESS;
    }

    public function ret($mode = \PDO::FETCH_ASSOC, $fetch_argument = null, $ctor_args = null)
    {
        if (!$this->ran()) {
            throw new CruditesException('No executed statement available for fetching results');
        }

        if ($mode === \PDO::FETCH_CLASS)
            return $this->executed->fetchAll($mode, $fetch_argument, $ctor_args);

        return $this->executed->fetchAll($mode);
    }

    public function retOne($mode = \PDO::FETCH_ASSOC, $orientation = null, $offset = null)
    {
        if (!$this->ran()) {
            throw new CruditesException('No executed statement available for fetching');
        }
        return $this->executed->fetch($mode, $orientation, $offset);
    }

    public function retObject(string $class = null)
    {
        return $class === null ? $this->ret(\PDO::FETCH_OBJ) : $this->ret(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class);
    }

    // wrapper for rowCount()
    public function count(): int
    {
        if (!$this->ran()) {
            throw new CruditesException('No executed statement available for counting results');
        }
        return $this->executed->rowCount();
    }

    public function errorInfo(): array
    {
        return ($this->executed ?? $this->prepared ?? $this->pdo)->errorInfo();
    }

    /**
     * Returns a formatted error message of the last operation (execution, preparation or query)
     * Format is: "message (state: code)"
     * 
     * @return string
     */
    public function errorMessageWithCodes(): string
    {
        // magic call to errorInfo()
        list($state, $code, $message) = $this->errorInfo();
        return sprintf('%s (state: %s, code: %s)', $message, $state, $code);
    }


    private function executePrepared(array $bindings): self
    {
        if ($this->prepared->execute($bindings) !== false) {
            $this->executed = $this->prepared;
            $this->bindings = $bindings;
            return $this;
        }

        throw new CruditesException('PDOSTATEMENT_EXECUTE');
    }

    private function queryStatement(): self
    {
        if (($res = $this->pdo->query((string)$this->statement)) !== false) {
            $this->executed = $res;
            return $this;
        }

        throw new CruditesException('PDO_QUERY_STRING');
    }

    private function prepareAndRun(array $bindings): self
    {
        if (($res = $this->pdo->prepare((string)$this->statement)) !== false) {
            $this->prepared = $res;
            return $this->run($bindings);
        }

        throw new CruditesException('PDO_PREPARE_STRING');
    }
}
