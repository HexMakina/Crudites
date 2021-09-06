<?php

namespace HexMakina\Crudites\Queries;

use \HexMakina\Interfaces\Database\TableDescriptionInterface;
use \HexMakina\Crudites\CruditesException;

class Delete extends BaseQuery
{
    use ClauseWhere;

    public function __construct(TableDescriptionInterface $table, $conditions = [])
    {
        $this->table = $table;
        $this->connection = $table->connection();
        if (!empty($conditions)) {
            $this->aw_fields_eq($conditions);
        }
    }

    public function generate(): string
    {
        if (empty($this->where)) {
            throw new CruditesException('DELETE_USED_AS_TRUNCATE');  // prevents haphazardous generation of dangerous DELETE statement
        }

        return sprintf('DELETE FROM `%s` %s ', $this->table_name(), $this->generate_where());
    }
}
