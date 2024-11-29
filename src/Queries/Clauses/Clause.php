<?php

namespace HexMakina\Crudites\Queries\Clauses;

use HexMakina\Crudites\Queries\Grammar;

abstract class Clause extends Grammar
{
    public const SELECT = 'SELECT';
    public const JOIN = 'JOIN';
    public const JOINS = 'JOINS';
    public const WHERE = 'WHERE';
    public const ORDER = 'ORDER BY';
    public const GROUP = 'GROUP BY';
    public const HAVING = 'HAVING';
    public const LIMIT = 'LIMIT';
    public const OFFSET = 'OFFSET';

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