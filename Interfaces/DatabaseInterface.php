<?php

namespace HexMakina\Crudites\Interfaces;

interface DatabaseInterface
{
    public function inspect($table_name) : TableManipulationInterface;

    public function contentConnection() : ConnectionInterface;
}
