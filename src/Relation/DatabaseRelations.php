<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\Crudites\Schema;

class DatabaseRelations
{
    private $db;
    private $relations;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
        $this->relations = $this->listRelations();
    }

    public function __debugInfo()
    {
        return [
            'database' => $this->db->name(), 
            'list' => array_keys($this->relations),
            'relations' => $this->relations
            ];
    }

    public function listRelations(): array
    {
        $relations = [];
        foreach($this->db->schema()->foreignKeysByTable() as $table => $join){

            if(count($join) == 1){
                $res = new HasOne($table, $join, $this->db);
                $relations["$res"] = $res;
            }
            else if(count($join) == 2){
                $res = new ManyToMany($table, $join, $this->db);
                $relations["$res"] = $res;

                $res = new ManyToMany($table, array_reverse($join), $this->db);
                $relations["$res"] = $res;
            }
            else if(count($join) == 3){
                $res = new ManyToManyQualified($table, $join, $this->db);
                $relations["$res"] = $res;

                $res = new ManyToManyQualified($table, array_reverse($join), $this->db);
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

    public function getRelation(string $relation): ?AbstractRelation
    {
        return $this->relations[$relation] ?? null;
    }

}