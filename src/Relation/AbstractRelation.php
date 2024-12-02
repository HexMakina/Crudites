<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\Table\Table;

abstract class AbstractRelation
{
    protected ConnectionInterface $connection;

    protected $primary_table;
    protected $primary_col;

    protected $secondary_table;
    protected $secondary_col;

    public const NAME='ABSTRACT_RELATION';

    
    public function __debugInfo()
    {
        return [
            'primary_table' => $this->primary_table,
            'primary_col' => $this->primary_col,
            'secondary_table' => $this->secondary_table,
            'secondary_col' => $this->secondary_col,
        ];
    }

    
    public function __toString()
    {
        return $this->nss();
    }
    
    public function nss()
    {
        return sprintf('%s-%s-%s', $this->primary_table, static::NAME, $this->secondary_table);
    }

    public function setConnection(ConnectionInterface $c)
    {
        $this->connection = $c;
    }

    public function source(){
        return $this->primary_table;
    }

    public function target(){
        return $this->secondary_table;
    }

    abstract public function link(int $primary_id, $mixed_id);
    abstract public function unlink(int $primary_id, $mixed_id);
}