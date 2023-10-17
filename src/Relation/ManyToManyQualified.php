<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\Crudites\Crudites;
use HexMakina\Crudites\Relation\ManyToMany as RelationManyToMany;

class ManyToManyQualified extends ManyToMany
{
    private $pivot_qualified;
    private $qualified_table;
    private $qualified_col;

    public function __toString()
    {
        return $this->primary_table . '-hasAndBelongsToManyQualified-' . $this->secondary_table.'-withQualifier-'.$this->qualified_table;
    }

    protected function propertiesFromJoin($join)
    {
        $tables = explode('_', $this->pivot_table);
        foreach($join as $pivot_col => $target){
            [$target_table, $target_col] = $target;
            
            if(in_array($target_table, $tables)){
                if(isset($this->primary_table)){
                    $this->pivot_secondary = $pivot_col;
                    $this->secondary_table = $target_table;
                    $this->secondary_col = $target_col;
                }
                else{
                    $this->pivot_primary = $pivot_col;
                    $this->primary_table = $target_table;
                    $this->primary_col = $target_col;
                }
            }
            else{
                $this->pivot_qualified = $pivot_col;
                $this->qualified_table = $target_table;
                $this->qualified_col = $target_col;
            }
        }
        // [$this->pivot_primary, $this->pivot_secondary, $this->pivot_qualified] = array_keys($join);

        // [$this->primary_table, $this->primary_col] = $join[$this->pivot_primary];
        // [$this->secondary_table, $this->secondary_col] = $join[$this->pivot_secondary];
        // [$this->qualified_table, $this->qualified_col] = $join[$this->pivot_qualified];
    }
}