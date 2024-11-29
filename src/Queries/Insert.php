<?php

namespace HexMakina\Crudites\Queries;

class Insert extends Query
{
    public function __construct(string $table, array $dat_ass)
    {
        // Check if the given data is a non-empty array, and throw an exception if it is not
        if (empty($dat_ass)) {
            throw new \InvalidArgumentException('EMPTY_DATA');
        }
        
        $this->table = $table;
        $this->addBindings($dat_ass);
    }

    /**
     * Generates the SQL INSERT statement
     *
     * @return string - The generated SQL INSERT statement
     */
    public function statement(): string
    {
        // Generate the INSERT statement with backticks around the field names
        $fields = $this->getBindingNames();
        $fields = array_keys($fields[$this->table]);
        
        $fields = '`' . implode('`, `', $fields) . '`';
        $bindings = implode(', ', array_keys($this->bindings()));

        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $bindings);
    }
}
