<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\Crudites\Result;

class HasOne extends AbstractRelation
{
    public const NAME = 'hasOne';

    public function __construct(string $table, array $join, ConnectionInterface $c)
    {
        $this->setConnection($c);
        $this->primary_table = $table;
        $this->propertiesFromJoin($join);
    }

    protected function propertiesFromJoin(array $join): void
    {
        $this->primary_col = array_key_first($join);
        [$this->secondary_table, $this->secondary_col] = $join[$this->primary_col];
    }

    public function link(int $primary_id, $secondary_id): bool
    {
        $data = [
            $this->primary_col => $primary_id,
            $this->secondary_col => $secondary_id
        ];

        $query = $this->connection->schema()->insert($this->primary_table, $data);
        return (new Result($this->connection->pdo(), $query))->ran();
    }

    public function unlink(int $primary_id, $secondary_id): bool
    {
        $criteria = [
            $this->primary_col => $primary_id,
            $this->secondary_col => $secondary_id
        ];

        $query = $this->connection->schema()->delete($this->primary_table, $criteria);
        return (new Result($this->connection->pdo(), $query))->ran();
    }
}
