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
        $this->binding_names = [];
        // $this->binding_names = $this->addBindings($dat_ass);

        $this->values($dat_ass);
    }

    public function values(array $dat_ass): self
    {
        $this->binding_names []= $this->addBindings($dat_ass);

        return $this;
    }

    /**
     * Generates the SQL INSERT statement
     *
     * @return string - The generated SQL INSERT statement
     */
    public function statement(): string
    {
        // Generate the INSERT statement with backticks around the field names

        $fields_labels = array_shift($this->binding_names);
        $fields = array_keys($fields_labels);
        $fields = '`' . implode('`,`', $fields) . '`';

        $bindings = implode(',:', $fields_labels);

        $statement = sprintf('INSERT INTO `%s` (%s) VALUES (:%s)', $this->table, $fields, $bindings);
        foreach($this->binding_names as $bindings){
            $statement .= sprintf(',(:%s)', implode(',:', $bindings));
        }

        return $statement;
    }
}
