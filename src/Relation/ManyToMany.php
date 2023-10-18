<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\Crudites\CruditesException;

class ManyToMany extends AbstractRelation
{

    protected $pivot_table;
    protected $pivot_primary;
    protected $pivot_secondary;

    public const NAME = 'hasAndBelongsToMany';

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


    public function set(int $parent, array $children_ids)
    {
        $children_ids = array_unique($children_ids);

        if(empty($children_ids)){
            throw new \InvalidArgumentException('NO_CHILDREN');
        }

        $connection = $this->db->connection();
        $pivot_table = $this->db->inspect($this->pivot_table);

        try {
            $connection->transact();
            $flush = $pivot_table->delete([$this->pivot_primary => $parent]);
            // vd($flush);
            $flush->run();
            if (!$flush->isSuccess()) {
                throw new CruditesException(__CLASS__.'::FLUSH_QUERY_FAILED');
            }

            foreach ($children_ids as $child_id) {
                $insert = $pivot_table->insert([$this->pivot_primary => $parent, $this->pivot_secondary => $child_id]);
                // vd($insert);
                $insert = $insert->run();

                if (!$insert->isSuccess()) {
                    throw new CruditesException(__CLASS__.'::INSERT_QUERY_FAILED');
                }
            }
            $connection->commit();
        } catch (\Exception $e) {
            dd($e);
            $connection->rollback();
            return $e->getMessage();
        }
    }

    public function getIds(int $parent_id)
    {
        $pivot_table = $this->db->inspect($this->pivot_table);
        $res = $pivot_table->select([$this->pivot_secondary])->whereEQ($this->pivot_primary, $parent_id);
        return $res->retCol();
    }
}
