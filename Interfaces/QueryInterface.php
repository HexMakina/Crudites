<?php

namespace HexMakina\Crudites\Interfaces;

interface QueryInterface
{
    public function statement($setter = null) : string;
    public function bindings($setter = null);

    public function connection(ConnectionInterface $setter = null) : ConnectionInterface;

    public function table_name() : string;
    public function table() : TableManipulationInterface;

    public function is_prepared();
    public function is_executed($setter = null) : bool;
    public function is_success() : bool;

    public function run() : QueryInterface;
}
