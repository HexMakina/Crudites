<?php

namespace HexMakina\Crudites\Queries;

class Describe
{
    private $table_name = null;

    public function __construct($table_name)
    {
        $this->table_name = $table_name;
    }

    public function __toString()
    {
        return sprintf('DESCRIBE `%s`;', $this->table_name);
    }
}
