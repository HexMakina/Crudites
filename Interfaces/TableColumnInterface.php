<?php

namespace HexMakina\Crudites\Interfaces;

interface TableColumnInterface
{
    public function name(): string;
    public function tableName(): string;

    public function type(): ColumnTypeInterface

    public function isPrimary(): bool;
    public function isForeign($setter = null): bool;
    public function isIndex($setter = null): bool;

    public function isAutoIncremented(): bool;
    public function isNullable(): bool;

    public function default();

    public function foreignTableName();
}
