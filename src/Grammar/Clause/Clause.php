<?php

/**
 * Abstract class Clause
 *
 * This class defines the structure and constants for SQL clauses.
 * It extends the Grammar class and provides a base for specific SQL clause implementations.
 *
 * Constants:
 * - SELECT: Represents the 'SELECT FROM' clause.
 * - JOIN: Represents the 'JOIN' clause.
 * - JOINS: Represents the 'JOINS' clause.
 * - WHERE: Represents the 'WHERE' clause.
 * - ORDER: Represents the 'ORDER BY' clause.
 * - GROUP: Represents the 'GROUP BY' clause.
 * - HAVING: Represents the 'HAVING' clause.
 * - LIMIT: Represents the 'LIMIT' clause.
 * - UPDATE: Represents the 'UPDATE' clause.
 * - SET: Represents the 'SET' clause.
 * - INSERT: Represents the 'INSERT INTO' clause.
 * - VALUES: Represents the 'VALUES' clause.
 * - DELETE: Represents the 'DELETE FROM' clause.
 *
 * Properties:
 * - array $bindings: An array to hold the bindings for the clause.
 *
 * Methods:
 * - abstract public function __toString(): string
 *   Converts the clause to a string representation.
 *
 * - abstract public function name(): string
 *   Returns the name of the clause.
 *
 * - public function bindings(): array
 *   Returns the bindings for the clause.
 */

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\Crudites\Grammar\Grammar;

abstract class Clause extends Grammar
{
    public const SELECT = 'SELECT FROM';
    public const JOIN = 'JOIN';
    public const JOINS = 'JOINS';
    public const WHERE = 'WHERE';
    public const ORDER = 'ORDER BY';
    public const GROUP = 'GROUP BY';
    public const HAVING = 'HAVING';
    public const LIMIT = 'LIMIT';

    public const UPDATE = 'UPDATE';
    public const SET = 'SET';

    public const INSERT = 'INSERT INTO';
    public const VALUES = 'VALUES';
    public const DELETE = 'DELETE FROM';

    public array $bindings = [];

    abstract public function __toString(): string;
    abstract public function name(): string;

    public function bindings(): array
    {
        return $this->bindings;
    }
}