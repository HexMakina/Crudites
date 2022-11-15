<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\TableDescriptionInterface;
use HexMakina\Crudites\CruditesException;

class Delete extends BaseQuery
{
    use ClauseJoin;
    use ClauseWhere;

    public function __construct(TableDescriptionInterface $table, array $conditions)
    {
        if (empty($conditions)) {
            throw new CruditesException('DELETE_USED_AS_TRUNCATE');
        }

        $this->table = $table;
        $this->connection = $table->connection();
        $this->whereFieldsEQ($conditions);
    }

    public function generate(): string
    {
        return sprintf('DELETE FROM `%s` %s ', $this->tableName(), $this->generateWhere());
    }
}
