<?php

namespace HexMakina\Crudites\Interfaces;

interface TraceableInterface
{
    public function traceable() : bool;
    public function traces() : array;
}
