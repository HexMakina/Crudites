<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\DatabaseInterface;

class Schema
{
    private const INTROSPECTION_DATABASE_NAME = 'INFORMATION_SCHEMA';

    /** @var array<string,array> */
    private array $fk_by_table = [];

    /** @var array<string,array> */
    private array $unique_by_table = [];


    public function __construct(DatabaseInterface $db)
    {
        $res = $this->loadSchemaFor($db);
        $this->processSchema($res);
    }


    public function uniqueConstraintNameFor(string $table, string $column): ?string
    {
        return $this->unique_by_table[$table][$column][0] ?? null;
    }

    /** @return array<int,string> */
    public function uniqueColumnNamesFor(string $table, string $column): array
    {
        return isset($this->unique_by_table[$table][$column][0])
               ? array_slice($this->unique_by_table[$table][$column], 1)
               : [];
    }

    /** @return array<int,string> */
    public function foreignKeyFor(string $table_name, string $column_name): ?array
    {
        return $this->fk_by_table[$table_name][$column_name] ?? null;
    }


    private function loadSchemaFor(DatabaseInterface $db) : array
    {
      // prepare to query database INFORMATION_SCHEMA
      $db->connection()->useDatabase(self::INTROSPECTION_DATABASE_NAME);

      // Run the query
      $res = $db->connection()->query($this->introspectionQuery($db->name()));

      // return to previous database
      $db->connection()->restoreDatabase();

      return $res->fetchAll();
    }

    private function introspectionQuery(string $database_name): string
    {
        $fields = [
        'TABLE_NAME',
        'CONSTRAINT_NAME',
        'ORDINAL_POSITION',
        'COLUMN_NAME',
        'POSITION_IN_UNIQUE_CONSTRAINT',
        'REFERENCED_TABLE_NAME',
        'REFERENCED_COLUMN_NAME'
        ];

        $statement = 'SELECT ' . implode(', ', $fields)
        . ' FROM KEY_COLUMN_USAGE'
        . ' WHERE TABLE_SCHEMA = "%s"'
        . ' ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION';

        return sprintf($statement, $database_name);
    }

    private function processSchema(array $res)
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
