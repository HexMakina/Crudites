<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\Table\Table;

class HasOne extends AbstractRelation
{
    public const NAME = 'hasOne';

    public function __construct($table, $join, DatabaseInterface $db)
    {
        $this->setDatabase($db);

        $this->primary_table = $table;

        $this->propertiesFromJoin($join);
        // $primary_col = array_keys($join);
        // $this->primary_col = array_pop($primary_col);

        // [$secondary_table, $secondary_col] = $join[$this->primary_col];

        // $this->secondary_table = $secondary_table;
        // $this->secondary_col = $secondary_col;
    }


    
    protected function propertiesFromJoin($join){
        $this->primary_col = array_keys($join);
        $this->primary_col = array_pop($this->primary_col);
        [$this->secondary_table, $this->secondary_col] = $join[$this->primary_col];
    }
}