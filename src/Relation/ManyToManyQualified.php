<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\Crudites\CruditesExceptionFactory;
use HexMakina\Crudites\Relation\ManyToMany;

class ManyToManyQualified extends ManyToMany
{
    private $pivot_qualified;   // FK to the table storing the qualifiers

    private $qualified_table;   // table storing the qualifiers (tags)
    private $qualified_col;     // PK of the table storing the qualifiers

    public const NAME = 'hasAndBelongsToManyQualified';

    public function __debugInfo()
    {
        return array_merge(parent::__debugInfo(), [
            'pivot_qualified' => $this->pivot_qualified,
            'qualified_table' => $this->qualified_table,
            'qualified_col' => $this->qualified_col
        ]);
    }

    public function __toString()
    {
        return $this->primary_table . '-hasAndBelongsToManyQualified-' . $this->secondary_table;
    }


    protected function propertiesFromJoin($join)
    {
        $tables = explode('_', $this->pivot_table);
        foreach ($join as $pivot_col => $target) {
            [$target_table, $target_col] = $target;

            if (in_array($target_table, $tables)) {
                if (isset($this->primary_table)) {
                    $this->pivot_secondary = $pivot_col;
                    $this->secondary_table = $target_table;
                    $this->secondary_col = $target_col;
                } else {
                    $this->pivot_primary = $pivot_col;
                    $this->primary_table = $target_table;
                    $this->primary_col = $target_col;
                }
            } else {
                $this->pivot_qualified = $pivot_col;
                $this->qualified_table = $target_table;
                $this->qualified_col = $target_col;
            }
        }
    }

    public function link(int $parent_id, $children_ids)
    {
        return $this->query($parent_id, $children_ids, 'insert');
    }

    public function unlink(int $parent_id, $children_ids)
    {
        return $this->query($parent_id, $children_ids, 'delete');
    }

    private function query(int $parent_id, array $many_ids, string $method)
    {
        if($parent_id < 1) {
            throw new \InvalidArgumentException('MISSING_PARENT_ID');
        }

        if($method !== 'insert' && $method !== 'delete') {
            throw new \InvalidArgumentException('INVALID_METHOD');
        }

        try {

            $pivot_table = $this->db->inspect($this->pivot_table);

            foreach ($many_ids as ['qualified' => $qualified_id, 'qualifier' => $qualifier_id]) {

                if (empty($qualified_id) || empty($qualifier_id)) {
                    throw new \InvalidArgumentException('MANY_IDS_MISSING_A_QUALIFYING_ID');
                }
                $query = call_user_func_array([$pivot_table, $method], 
                    [
                        [
                            $this->pivot_primary => $parent_id, 
                            $this->pivot_secondary => $qualified_id, 
                            $this->pivot_qualified => $qualifier_id
                        ]
                    ]);
                $query->run();
                
                if (!$query->isSuccess()) {
                    throw CruditesExceptionFactory::make($query);
                }
            }
        } catch (\Exception $e) {
            // dd($e);
            return $e->getMessage();
        }
    }

}
