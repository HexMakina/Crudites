<?php

namespace HexMakina\Crudites\Grammar\Clause;

use HexMakina\BlackBox\Database\ClauseInterface;
use HexMakina\Crudites\Grammar\Grammar;

abstract class Clause extends Grammar implements ClauseInterface
{
    public array $bindings = [];

    public function bindings(): array
    {
        return $this->bindings;
    }
}