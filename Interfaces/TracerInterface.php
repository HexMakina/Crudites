<?php

namespace HexMakina\Crudites\Interfaces;

interface TracerInterface
{
    const CODE_CREATE = 'C';
    const CODE_SELECT = 'R';
    const CODE_UPDATE = 'U';
    const CODE_DELETE = 'D';

    public function tracing_table() : TableManipulationInterface;

    public function trace(QueryInterface $q, $operator_id, $model_id) : bool;

    public function query_code($sql_statement) : string;

  // public function history($table_name, $table_pk, $sort='DESC') : array;
}
