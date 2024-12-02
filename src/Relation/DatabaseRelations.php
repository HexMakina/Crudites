<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\ConnectionInterface;

class DatabaseRelations
{
    private $connection;
    private $relations;

    public function __construct(ConnectionInterface $c)
    {
        $this->connection = $c;
        $this->relations = $this->listRelations();
    }

    public function __debugInfo()
    {
        return [
            'database' => $this->connection->databaseName(), 
            'list' => array_keys($this->relations),
            'relations' => $this->relations
            ];
    }

    public function listRelations(): array
    {
        $relations = [];
        foreach($this->connection->schema()->foreignKeysFor() as $table => $join){

            if(count($join) == 1){
                $res = new HasOne($table, $join,$this->connection);
                $relations["$res"] = $res;
            }
            else if(count($join) == 2){
                $res = new OneToMany($table, $join,$this->connection);
                $relations["$res"] = $res;

                $res = new OneToMany($table, array_reverse($join),$this->connection);
                $relations["$res"] = $res;
            }
            else if(count($join) == 3){
                $res = new OneToManyQualified($table, $join,$this->connection);
                $relations["$res"] = $res;

                $res = new OneToManyQualified($table, array_reverse($join),$this->connection);
                $relations["$res"] = $res;
            }
            else
            {
                vd($join, 'skipping '.$table);
            }
            
        }

        return $relations;
    }

    public function relations(): array
    {
        return $this->relations;
    }

    public function relationsBySource(string $source): array
    {
        $ret = [];

        foreach($this->relations as $urn => $relation){
            if($relation->source() === $source){
                $ret[$urn] = $relation;
            }
        }

        return $ret;
    }

    public function getRelation(string $relation): ?AbstractRelation
    {
        return $this->relations[$relation] ?? null;
    }

}