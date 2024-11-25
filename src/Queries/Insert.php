<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;

class Insert extends Query
{
    use ClauseJoin;

    public function __construct(string $table, array $dat_ass)
    {
        // Check if the given data is a non-empty array, and throw an exception if it is not
        if (empty($dat_ass)) {
            throw new CruditesException(__CLASS__ . '_DATA_INVALID_OR_MISSING');
        }
        
        $this->table = $table;
        $this->addBindings($dat_ass);
    }

    /**
     * Generates the SQL INSERT statement
     *
     * @throws CruditesException - Thrown if there are no bindings or if the number of bindings does not match the number of binding names
     * @return string - The generated SQL INSERT statement
     */
    public function statement(): string
    {
        // Generate the INSERT statement with backticks around the field names
        $fields = $this->getBindingNames();
        $fields = array_keys($fields[$this->table]);
        
        $fields = '`' . implode('`, `', $fields) . '`';
        $bindings = implode(', ', array_keys($this->getBindings()));

        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $bindings);
    }
}
