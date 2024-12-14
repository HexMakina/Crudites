<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\BlackBox\Database\ClauseInterface;
use HexMakina\Crudites\Grammar\Grammar;

abstract class Clause extends Grammar implements ClauseInterface
{
    public array $bindings;

    public function __construct()
    {
        $this->bindings = [];
    }

    public function add($nothing): self
    {
        return $this;
    }
    
    public function bindings(): array
    {
        return $this->bindings;
    }
}