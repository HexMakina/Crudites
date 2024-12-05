<?php

namespace HexMakina\Crudites\Grammar\Query;

class Insert extends Query
{
    private array $binding_names;
    
    public function __construct(string $table, array $dat_ass)
    {
        // Check if the given data is a non-empty array, and throw an exception if it is not
        if (empty($dat_ass)) {
            throw new \InvalidArgumentException('EMPTY_DATA');
        }
        
        $this->table = $table;
        $this->binding_names = $this->addBindings($dat_ass);
    }

    /**
     * Generates the SQL INSERT statement
     *
     * @return string - The generated SQL INSERT statement
     */
    public function statement(): string
    {
        // Generate the INSERT statement with backticks around the field names
        $fields = array_keys($this->binding_names);
        $fields = '`' . implode('`, `', $fields) . '`';
        $bindings = implode(', ', array_keys($this->bindings()));

        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $bindings);
    }
}
