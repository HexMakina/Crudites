<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\TableMetaInterface;
use HexMakina\Crudites\CruditesException;

class Delete extends PreparedQuery
{
    use ClauseJoin;
    use ClauseWhere;

    public function __construct(TableMetaInterface $tableMeta, array $conditions)
    {
        if (empty($conditions)) {
            throw new CruditesException('DELETE_USED_AS_TRUNCATE');
        }

        $this->table = $tableMeta;
        $this->connection = $tableMeta->connection();
        $this->whereFieldsEQ($conditions);
    }

    public function statement(): string
    {
        return sprintf('DELETE FROM `%s` %s ', $this->table()->name(), $this->generateWhere());
    }
}
