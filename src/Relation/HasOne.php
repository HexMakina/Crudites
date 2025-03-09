<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\Crudites\Result;

class HasOne extends AbstractRelation
{
    public const NAME = 'hasOne';

    public function __construct($table, $join, ConnectionInterface $c)
    {
        $this->setConnection($c);

        $this->primary_table = $table;

        $this->propertiesFromJoin($join);
    }

    protected function propertiesFromJoin($join)
    {
        $this->primary_col = array_keys($join);
        $this->primary_col = array_pop($this->primary_col);
        [$this->secondary_table, $this->secondary_col] = $join[$this->primary_col];
    }

    public function link(int $primary_id, $secondary_id): bool
    {
        $query = $this->connection->schema()->insert($this->primary_table, [$this->primary_col => $primary_id, $this->secondary_col => $secondary_id]);
        $res = new Result($this->connection->pdo(), $query);
        return $res->ran();
    }

    public function unlink(int $primary_id, $secondary_id): bool
    {
        $query = $this->connection->schema()->delete($this->primary_table, [$this->primary_col => $primary_id, $this->secondary_col => $secondary_id]);
        $res = new Result($this->connection->pdo(), $query);
        return $res->ran();
    }
}
