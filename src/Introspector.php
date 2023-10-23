<?php

namespace HexMakina\Crudites;
use HexMakina\Crudites\Table\Table;
use HexMakina\BlackBox\Database\{ConnectionInterface, TableInterface};

class Introspector
{
    const INTROSPECTION_DATABASE_NAME = 'information_schema';

    private string $database;
    private ConnectionInterface $connection;
    
    /** @var array<string,array> */
    private $fk_by_table = [];
    
    /** @var array<string,array> */
    private $unique_by_table = [];


    public function __construct(string $database, ConnectionInterface $connection)
    {
        $this->database = $database;
        $this->connection = $connection;
        
        $information_schema = $this->fetchKeyColumnUsage();
        $this->parseSchemaResult($information_schema);
    }

    public function foreignKeysByTable($table = null, $column = null): array
    {
        if(isset($column)){
            return $this->fk_by_table[$table][$column] ?? [];
        }
        if(isset($table)){
            return $this->fk_by_table[$table] ?? [];
        }
        return $this->fk_by_table;
    }

    public function uniqueKeysByTable($table = null, $column = null): array
    {
        if(isset($column)){
            return $this->unique_by_table[$table][$column] ?? [];
        }
        if(isset($table)){
            return $this->unique_by_table[$table] ?? [];
        }
        return $this->unique_by_table;
    }

    /**
     * Loads the schema for the specified database.
     *
     * @param DatabaseInterface $db The database instance to load the schema from.
     *
     * @return array The schema data.
     */
    private function fetchKeyColumnUsage(): array
    {
        // switch database
        $this->connection->useDatabase(self::INTROSPECTION_DATABASE_NAME);

         // run the query
        $res = $this->connection->query($this->introspectionQuery());

        // return to previous database
        $this->connection->restoreDatabase();

        return $res->fetchAll();
    }


    /**
     * Generates the SQL query to load the schema for the specified database.
     *
     * @param string $database_name The name of the database.
     *
     * @return string The SQL query.
     */
    private function introspectionQuery(): string
    {
        $statement = 'SELECT 
            TABLE_NAME, 
            CONSTRAINT_NAME, 
            ORDINAL_POSITION, 
            COLUMN_NAME, 
            POSITION_IN_UNIQUE_CONSTRAINT, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = "%s"
        ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION';
        
        return sprintf($statement, $this->database);
    }

    /**
     * Takes the result set from the database schema query 
     * and build a representation of the schema that can be used by the rest of the clas
     *
     * @param array $res the result set to process
     *
     * @return void
     */
    private function parseSchemaResult(array $res)
    {
        $unique_by_constraint = [];

        foreach ($res as $key_usage) {
            $table = $key_usage['TABLE_NAME'];

            // foreign keys
            if (isset($key_usage['REFERENCED_TABLE_NAME'])) {
                $this->fk_by_table[$table] ??= [];
                $this->fk_by_table[$table][$key_usage['COLUMN_NAME']] = [$key_usage['REFERENCED_TABLE_NAME'], $key_usage['REFERENCED_COLUMN_NAME']];
            }

            // primary & uniques
            if (!isset($key_usage['POSITION_IN_UNIQUE_CONSTRAINT'])) {
                $unique_by_constraint[$table] ??= [];

                $constraint = $key_usage['CONSTRAINT_NAME'];
                $unique_by_constraint[$table][$constraint] ??= [];

                $unique_by_constraint[$table][$constraint][$key_usage['ORDINAL_POSITION']] = $key_usage['COLUMN_NAME'];
            }
        }

      // unique indexes, indexed by table and column for easier retrieval
        foreach ($unique_by_constraint as $table => $uniques) {
            $this->unique_by_table[$table] ??= [];
            foreach ($uniques as $constraint => $columns) {
                foreach ($columns as $column_name) {
                    $this->unique_by_table[$table][$column_name] = [0 => $constraint] + $columns;
                }
            }
        }
    }

}

?>