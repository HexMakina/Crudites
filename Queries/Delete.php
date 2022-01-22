<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\TableDescriptionInterface;
use HexMakina\Crudites\CruditesException;

class Delete extends BaseQuery
{
    use ClauseJoin;
    use ClauseWhere;

    public function __construct(TableDescriptionInterface $table, $conditions = [])
    {
        $this->table = $table;
        $this->connection = $table->connection();
        if (!empty($conditions)) {
            $this->whereFieldsEQ($conditions);
        }
    }

    public function generate(): string
    {
        if (empty($this->where)) {
            // prevents haphazardous generation of dangerous DELETE statement
            throw new CruditesException('DELETE_USED_AS_TRUNCATE');
        }

        return sprintf('DELETE FROM `%s` %s ', $this->tableName(), $this->generateWhere());
    }
}
