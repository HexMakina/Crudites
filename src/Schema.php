<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\DatabaseInterface;

/**
 * 
 * The class provides functionality for retrieving and processing database schema information 
 * using the INFORMATION_SCHEMA database.
 * 
 */

class Schema
{
    private const INTROSPECTION_DATABASE_NAME = 'INFORMATION_SCHEMA';

    /** @var array<string,array> */
    private array $fk_by_table = [];

    /** @var array<string,array> */
    private array $unique_by_table = [];

    
    /**
     * Constructor.
     *
     * @param DatabaseInterface $db The database instance to load the schema from.
     */
    public function __construct(DatabaseInterface $db)
    {
        $res = $this->loadSchemaFor($db);
        $this->parseSchemaResult($res);
    }


    /**
     * Gets the name of the unique constraint that the specified column belongs to, if any.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     *
     * @return string|null The name of the unique constraint, or null if the column is not part of a unique constraint.
     */
    public function uniqueConstraintNameFor(string $table, string $column): ?string
    {
        return $this->unique_by_table[$table][$column][0] ?? null;
    }


    /**
     * Gets an array of column names that are part of the same unique constraint as the specified column.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column.
     *
     * @return array<int,string> An array of column names that are part of the same unique constraint as the specified column.
     */
    public function uniqueColumnNamesFor(string $table, string $column): array
    {
        return isset($this->unique_by_table[$table][$column][0])
               ? array_slice($this->unique_by_table[$table][$column], 1)
               : [];
    }


    /**
     * Gets the foreign key that references the specified column, if any.
     *
     * @param string $table_name The name of the table.
     * @param string $column_name The name of the column.
     *
     * @return array|null An array containing the name of the referenced table and column, or null if the column is not part of a foreign key.
     */
    public function foreignKeyFor(string $table_name, string $column_name): ?array
    {
        return $this->fk_by_table[$table_name][$column_name] ?? null;
    }


    /**
     * Loads the schema for the specified database.
     *
     * @param DatabaseInterface $db The database instance to load the schema from.
     *
     * @return array The schema data.
     */
    private function loadSchemaFor(DatabaseInterface $db): array
    {
        // prepare to query database INFORMATION_SCHEMA
        $query = $this->introspectionQuery($db->name());

        // switch database
        $db->connection()->useDatabase(self::INTROSPECTION_DATABASE_NAME);

         // run the query
        $res = $db->connection()->query($query);

        // return to previous database
        $db->connection()->restoreDatabase();

        return $res->fetchAll();
    }


    /**
     * Generates the SQL query to load the schema for the specified database.
     *
     * @param string $database_name The name of the database.
     *
     * @return string The SQL query.
     */
    private function introspectionQuery(string $database_name): string
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
        
        return sprintf($statement, $database_name);
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
