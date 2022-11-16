<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\BlackBox\Database\QueryInterface;

class Insert extends BaseQuery
{
    use ClauseJoin;

    public function __construct(TableInterface $table, array $assoc_data)
    {
        if (!is_array($assoc_data) || empty($assoc_data)) {
            throw new CruditesException('INSERT_DATA_INVALID_OR_MISSING');
        }

        $this->table = $table;
        $this->connection = $table->connection();

        $this->addBindings($assoc_data);
    }

    /**
     * @return array<int|string, string>
     */
    public function addBindings($assoc_data): array
    {
        $ret = [];
        foreach ($this->table->columns() as $column_name => $column) {
            if ($column->isAutoIncremented()) {
                continue;
            }

            if (isset($assoc_data[$column_name])) {
                $ret[$column_name] = $this->addBinding($column_name, $assoc_data[$column_name]);
            }
        }

        return $ret;
    }

    public function generate(): string
    {
        if (empty($this->getBindingNames()) || count($this->getBindings()) !== count($this->getBindingNames())) {
            throw new CruditesException('INSERT_FIELDS_BINDINGS_MISMATCH');
        }

        $fields = '`' . implode('`, `', array_keys($this->getBindingNames())) . '`';
        $bindings = implode(', ', array_keys($this->getBindings()));

        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $bindings);
    }
}
