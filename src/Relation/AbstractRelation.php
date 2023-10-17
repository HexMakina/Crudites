<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\Table\Table;

abstract class AbstractRelation
{
    protected DatabaseInterface $db;
    // protected ConnectionInterface $connection;

    protected $primary_table;
    protected $primary_col;

    protected $secondary_table;
    protected $secondary_col;

    public const NAME='ABSTRACT_RELATION';


    public function __toString()
    {
        return sprintf('%s-%s-%s', $this->primary_table, static::NAME, $this->secondary_table);
    }
        
    public function setDatabase(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function source(){
        return $this->primary_table;
    }

    public function target(){
        return $this->secondary_table;
    }
}