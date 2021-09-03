<?php

namespace HexMakina\Crudites\Interfaces;

interface TableColumnInterface
{
    public function name(): string;

    public function type(): ColumnTypeInterface
    // public function length($setter=null) : int;

    public function isPrimary(): bool;
    public function isAutoIncremented(): bool;
    public function isNullable(): bool;

    public function default();

    public function foreignTableName();
}
