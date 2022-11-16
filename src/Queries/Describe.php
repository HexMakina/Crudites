<?php

namespace HexMakina\Crudites\Queries;

class Describe
{
    private $table_name;

    public function __construct($table_name)
    {
        $this->table_name = $table_name;
    }

    public function __toString(): string
    {
        return sprintf('DESCRIBE `%s`;', $this->table_name);
    }
}
