<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\TableInterface;
use HexMakina\BlackBox\Database\QueryInterface;

class Insert extends PreparedQuery implements QueryInterface
{
    use ClauseJoin;

    // The constructor takes a TableInterface object and an associative array of data to be inserted
    public function __construct(TableInterface $table, array $assoc_data)
    {
        // Check if the given data is a non-empty array, and throw an exception if it is not
        if (!is_array($assoc_data) || empty($assoc_data)) {
            throw new CruditesException('INSERT_DATA_INVALID_OR_MISSING');
        }

        // Set the table and database connection objects
        $this->table = $table;
        $this->connection = $table->connection();

        // Add the data bindings to the query
        $this->makeBindings($assoc_data);
    }

    /**
     * Loops through the table columns and adds the ones that are not auto-incremented to the query bindings
     *
     * @param array $assoc_data - An associative array of data to be inserted
     * @return array<int|string, string> - An array of bindings
     */
    private function makeBindings($assoc_data): array
    {
        $ret = [];
        foreach ($this->table->columns() as $column_name => $column) {
            // Skip auto-incremented columns
            if ($column->isAutoIncremented()) {
                continue;
            }

            // Add the column binding to the query if it is present in the associative data array
            if (isset($assoc_data[$column_name])) {
                $ret[$column_name] = $this->addBinding($column_name, $assoc_data[$column_name], $this->table->name());
            }
        }

        return $ret;
    }

    /**
     * Generates the SQL INSERT statement
     *
     * @throws CruditesException - Thrown if there are no bindings or if the number of bindings does not match the number of binding names
     * @return string - The generated SQL INSERT statement
     */
    public function statement(): string
    {
        // Throw an exception if there are no bindings
        if (empty($this->getBindings())) {
            throw new CruditesException('INSERT_FIELDS_NO_BINDINGS');
        }


        // Generate the INSERT statement with backticks around the field names
        $fields = $this->getBindingNames();
        $fields = array_keys($fields[$this->table()->name()]);
        
        $fields = '`' . implode('`, `', $fields) . '`';
        $bindings = implode(', ', array_keys($this->getBindings()));

        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $fields, $bindings);
    }
}
