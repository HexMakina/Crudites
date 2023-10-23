<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\CruditesExceptionFactory;

class ManyToMany extends AbstractRelation
{

    protected $pivot_table;
    protected $pivot_primary;
    protected $pivot_secondary;

    public const NAME = 'hasAndBelongsToMany';


    public function __debugInfo()
    {
        return array_merge(parent::__debugInfo(), [
            'pivot_table' => $this->pivot_table,
            'pivot_primary' => $this->pivot_primary,
            'pivot_secondary' => $this->pivot_secondary
        ]);
    }

    public function __construct($table, $join, DatabaseInterface $db)
    {
        $this->setDatabase($db);
        $this->pivot_table = $table;
        $this->propertiesFromJoin($join);
    }


    protected function propertiesFromJoin($join)
    {
        [$this->pivot_primary, $this->pivot_secondary] = array_keys($join);

        [$this->primary_table, $this->primary_col] = $join[$this->pivot_primary];
        [$this->secondary_table, $this->secondary_col] = $join[$this->pivot_secondary];
    }

    public function link(int $parent_id, $children_ids)
    {
        return $this->query($parent_id, $children_ids, 'insert');
    }

    public function unlink(int $parent_id, $children_ids)
    {
        return $this->query($parent_id, $children_ids, 'delete');
    }

    public function getIds(int $parent_id)
    {
        $pivot_table = $this->db->inspect($this->pivot_table);
        $res = $pivot_table->select([$this->pivot_secondary])->whereEQ($this->pivot_primary, $parent_id);
        return $res->retCol();
    }

    public function getTargets(int $parent_id): array
    {
        $table = $this->db->inspect($this->secondary_table);
        $select = $table->select()
            ->join([$this->pivot_table], [[$this->secondary_table, $this->secondary_col, $this->pivot_table, $this->pivot_secondary]], 'INNER')
            ->whereEQ($this->pivot_primary, $parent_id, $this->pivot_table);
        
        return $select->retAss();
    }

    private function query(int $parent_id, array $children_ids, string $method)
    {
        if($parent_id < 1) {
            throw new \InvalidArgumentException('MISSING_PARENT_ID');
        }

        if($method !== 'insert' && $method !== 'delete') {
            throw new \InvalidArgumentException('INVALID_METHOD');
        }

        $children_ids = array_unique($children_ids);

        if(empty($children_ids)){
            throw new \InvalidArgumentException('NO_UNIQUE_CHILDREN');
        }

        $pivot_table = $this->db->inspect($this->pivot_table);

        foreach ($children_ids as $child_id) {

            $query = call_user_func_array([$pivot_table, $method], 
            [
                [
                    $this->pivot_primary => $parent_id, 
                    $this->pivot_secondary => $child_id
                ]
            ]);

            $query->run();

            if (!$query->isSuccess()) {
                // vd($query);
                throw CruditesExceptionFactory::make($query);
            }
        }
    }


}
