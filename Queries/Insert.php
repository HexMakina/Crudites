<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableManipulationInterface;
use HexMakina\BlackBox\Database\QueryInterface;

class Insert extends BaseQuery
{
    private $query_fields = [];

    public function __construct(TableManipulationInterface $table, $assoc_data = [])
    {
        if (!is_array($assoc_data) || empty($assoc_data)) {
            throw new \Exception('INSERT_DATA_INVALID_OR_MISSING');
        }

        $this->table = $table;
        $this->connection = $table->connection();

        if (!empty($assoc_data)) {
            $this->addBindings($assoc_data);
        }
    }

    public function addBindings($assoc_data): array
    {
        $binding_names = [];
        foreach ($this->table->columns() as $column_name => $column) {
            if ($column->isAutoIncremented()) {
                continue;
            }

            if (isset($assoc_data[$column_name])) {
                $this->query_fields[$column_name] = $column_name;
                $binding_names[$column_name] = $this->addBinding($column_name, $assoc_data[$column_name]);
            }
        }
        return $binding_names;
    }

    public function generate(): string
    {
        if (empty($this->query_fields) || count($this->bindings) !== count($this->query_fields)) {
            throw new CruditesException('INSERT_FIELDS_BINDINGS_MISMATCH');
        }

        $fields = '`' . implode('`, `', $this->query_fields) . '`';
        $values = implode(', ', array_keys($this->bindings));

        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $values);
    }
}
