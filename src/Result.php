<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\QueryInterface;

/**
 * Provides a simple interface to run sql statements
 * Main methods: run, ran, ret
 * 
 * Constructor
 *      allows statement as string, PDOStatements or QueryInterface
 *      calls run() with provided bindings (optionals)
 * 
 * All PDOStatement methods are available by encapsluation through the magic __call method.
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
        if ($mode === \PDO::FETCH_CLASS)
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

    public function retObject(string $class = null)
    {
        return $class === null ? $this->ret(\PDO::FETCH_OBJ) : $this->ret(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class);
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     * A wrapper for PDOStatement::rowCount()
     * 
     * @return int, -1 if the statement has not been executed
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
        return $this->pdo->lastInsertId($name);
    }

    /** 
     * Returns the error info of the last operation (execution, preparation or query)
     * A wrapper for PDOStatement::errorInfo() and PDO::errorInfo()
     * 
     * @return array
     */
    public function errorInfo(): array
    {
        $source = $this->executed ?? $this->prepared ?? $this->pdo;
        return $source->errorInfo();
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
}
