<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;

class Delete extends Query
{
    use ClauseJoin;
    use ClauseWhere;

    public function __construct(string $table, array $strict_conditions)
    {
        if (empty($strict_conditions)) {
            throw new CruditesException('DELETE_USED_AS_TRUNCATE');
        }

        $this->table = $table;
        $this->whereFieldsEQ($strict_conditions);
    }

    public function statement(): string
    {
        return sprintf('DELETE FROM `%s` %s ', $this->table, $this->generateWhere());
    }
}
