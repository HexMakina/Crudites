<?php

namespace HexMakina\Crudites\Relation;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\Crudites\CruditesExceptionFactory;

class OneToMany extends AbstractRelation
{

    protected $pivot_table;
    protected $pivot_primary;
    protected $pivot_secondary;

    public const NAME = 'hasAndBelongsToMany';

    public const ACTION_LINK = 'insert';
    public const ACTION_UNLINK = 'delete';

    public function __debugInfo()
    {
        return array_merge(parent::__debugInfo(), [
            'pivot_table' => $this->pivot_table,
            'pivot_primary' => $this->pivot_primary,
            'pivot_secondary' => $this->pivot_secondary
        ]);
    }

    public function __construct($table, $join, ConnectionInterface $c)
    {
        $this->setConnection($c);
        $this->pivot_table = $table;
        $this->propertiesFromJoin($join);
    }


    protected function propertiesFromJoin($join)
    {
        [$this->pivot_primary, $this->pivot_secondary] = array_keys($join);

        [$this->primary_table, $this->primary_col] = $join[$this->pivot_primary];
        [$this->secondary_table, $this->secondary_col] = $join[$this->pivot_secondary];
    }

    public function link(int $source, $target_ids): array
    {
        return $this->query($source, $target_ids, self::ACTION_LINK);
    }

    public function unlink(int $source, $target_ids): array
    {
        return $this->query($source, $target_ids, self::ACTION_UNLINK);
    }

    public function getIds(int $source)
    {
        $pivot_table =$this->connection->schema()->table($this->pivot_table);
        $res = $pivot_table->select([$this->pivot_secondary])->whereEQ($this->pivot_primary, $source);
        return $res->retCol();
    }

    public function getTargets(int $source): array
    {
        $table =$this->connection->schema()->table($this->secondary_table);
        $select = $table->select()
            ->join([$this->pivot_table], [[$this->secondary_table, $this->secondary_col, $this->pivot_table, $this->pivot_secondary]], 'INNER')
            ->whereEQ($this->pivot_primary, $source, $this->pivot_table);
        
        return $select->retAss();
    }

    private function query(int $source, array $target_ids, string $method): array
    {
        if($source < 1) {
            throw new \InvalidArgumentException('MISSING_PARENT_ID');
        }
        
        if($method !== self::ACTION_LINK && $method !== self::ACTION_UNLINK) {
            throw new \InvalidArgumentException('INVALID_METHOD');
        }
        
        $target_ids = array_unique($target_ids);
        
        if(empty($target_ids)){
            throw new \InvalidArgumentException('NO_UNIQUE_CHILDREN');
        }
        
        $errors = [];
        $pivot_table =$this->connection->schema()->table($this->pivot_table);

        foreach ($target_ids as $target) {

            $query = call_user_func_array([$pivot_table, $method], 
            [
                [
                    $this->pivot_primary => $source, 
                    $this->pivot_secondary => $target
                ]
            ]);
            $query->prepare();
            $query->run();

            if (!$query->isSuccess()) {
                $errors[] = $query->error();

                if($query->error()->getCode() !== 1062){
                    throw CruditesExceptionFactory::make($query);
                }
            }
        }

        return $errors;
    }


}
