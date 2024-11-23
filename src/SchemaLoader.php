<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\SchemaInterface;

/**
 * SchemaLoader
 * 
 * This class is responsible for loading the schema of a database.
 * 
 * The schema is loaded from the INFORMATION_SCHEMA database.
 * Using the tables and columns from the INFORMATION_SCHEMA database, 
 * such as TABLES, COLUMNS, KEY_COLUMN_USAGE, TABLE_CONSTRAINTS, and REFERENTIAL_CONSTRAINTS.
 * The class constructs an array of tables with their columns, primary keys, foreign keys, and unique keys.
 * 
 */

class SchemaLoader
{
    const INTROSPECTION_DATABASE_NAME = 'information_schema';

    public static function load(ConnectionInterface $connection): array
    {
        try {
            $database = $connection->database();

            $connection->transact();

            $connection->query(sprintf('USE %s;', self::INTROSPECTION_DATABASE_NAME));

            $res = $connection->query(self::informationSchemaQuery($database));

            $connection->query(sprintf('USE %s;', $database));

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }

        $res =  $res ? $res->fetchAll() : [];

        return self::parseInformationSchema($res);
    }

    /**
     * Parses the result of the INFORMATION_SCHEMA query and stores the information in arrays.
     *
     * @param array $information_schema_result The result of the INFORMATION_SCHEMA query.
     */
    private static function parseInformationSchema(array $information_schema_result): array
    {
        $tables = [];
        foreach ($information_schema_result as $res) {

            $table = $res['table'];
            $column = $res['column'];

            // initialize arrays
            $tables[$table] ??= ['columns' => [], 'primary' => [], 'foreign' => [], 'unique' => []];


            // store common column information once
            if (!isset($tables[$table]['columns'][$column])) {
                $tables[$table]['columns'][$column] = ['SCHEMA' => $res, 'unique' => []];
            }

            // store primary keys, foreign keys, and unique keys
            if (!empty($res['constraint_type'])) {

                $constraint_name = $res['constraint'];

                switch ($res['CONSTRAINT_TYPE']) {

                    case 'PRIMARY KEY':
                        $tables[$table]['primary'][] = $column;
                        break;

                    case 'FOREIGN KEY':
                        $reference = [$res['parent_table'], $res['parent_column'], $res['delete_rule'], $res['update_rule']];
                        $tables[$table]['foreign'][$column] ??= [];
                        $tables[$table]['foreign'][$column] = $reference;
                        break;

                    case 'UNIQUE':
                        $tables[$table]['unique'][$constraint_name] ??= [];
                        $tables[$table]['unique'][$constraint_name][] = $column;

                        $tables[$table]['columns'][$column]['unique'][] = $constraint_name;
                        break;
                }
            }
        }

        return $tables;
    }

    /**
     * Returns the query to get the schema information from the INFORMATION_SCHEMA database.
     *
     * @param string $database The name of the database.
     */
    private static function informationSchemaQuery(string $database): string
    {
        return "
SELECT 
    `t`.`TABLE_NAME` AS `table`,
    `c`.`COLUMN_NAME` AS `column`,
    `c`.`COLUMN_DEFAULT` AS `default`,
    `c`.`COLUMN_TYPE` AS `column_type`,

    CASE
        WHEN `c`.`IS_NULLABLE` = 'YES' THEN 1
        ELSE NULL
    END AS `nullable`,

    CASE
        WHEN `c`.`DATA_TYPE` = 'bit' AND `c`.`NUMERIC_PRECISION` = 1 THEN 'boolean'
        WHEN `c`.`DATA_TYPE` IN ('bit', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint') THEN 'integer'
        WHEN `c`.`DATA_TYPE` IN ('float', 'double', 'real') THEN 'float'
        WHEN `c`.`DATA_TYPE` IN ('decimal', 'numeric') THEN 'decimal'
        WHEN `c`.`DATA_TYPE` IN ('char', 'varchar') THEN 'string'
        WHEN `c`.`DATA_TYPE` IN ('text', 'tinytext', 'mediumtext', 'longtext') THEN 'text'
        WHEN `c`.`DATA_TYPE` = 'date' THEN 'date'
        WHEN `c`.`DATA_TYPE` = 'datetime' THEN 'datetime'
        WHEN `c`.`DATA_TYPE` = 'timestamp' THEN 'timestamp'
        WHEN `c`.`DATA_TYPE` = 'time' THEN 'time'
        WHEN `c`.`DATA_TYPE` = 'year' THEN 'year'
        WHEN `c`.`DATA_TYPE` = 'enum' THEN 'enum'
        ELSE 'unknown'
    END AS `type`,

    `c`.`CHARACTER_MAXIMUM_LENGTH` AS `length`,
    `c`.`NUMERIC_PRECISION` AS `precision`,
    `c`.`NUMERIC_SCALE` AS `scale`,

    CASE 
        WHEN `c`.`EXTRA` = 'auto_increment' THEN 1
        ELSE NULL
    END AS `auto_increment`,

    `tc`.`CONSTRAINT_TYPE` AS `constraint_type`,
    `kcu`.`CONSTRAINT_NAME` AS `constraint`,
    `kcu`.`REFERENCED_TABLE_NAME` AS `parent_table`,
    `kcu`.`REFERENCED_COLUMN_NAME` AS `parent_column`,
    `rc`.`DELETE_RULE` AS `delete_rule`,
    `rc`.`UPDATE_RULE` AS `update_rule`
FROM 
    `INFORMATION_SCHEMA`.`TABLES` AS `t`
JOIN 
    `INFORMATION_SCHEMA`.`COLUMNS` AS `c`
    ON `t`.`TABLE_NAME` = `c`.`TABLE_NAME`
    AND `t`.`TABLE_SCHEMA` = `c`.`TABLE_SCHEMA`
LEFT JOIN 
    `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` AS `kcu`
    ON `t`.`TABLE_NAME` = `kcu`.`TABLE_NAME`
    AND `c`.`COLUMN_NAME` = `kcu`.`COLUMN_NAME`
    AND `t`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA`
LEFT JOIN 
    `INFORMATION_SCHEMA`.`TABLE_CONSTRAINTS` AS `tc`
    ON `kcu`.`CONSTRAINT_NAME` = `tc`.`CONSTRAINT_NAME`
    AND `kcu`.`TABLE_SCHEMA` = `tc`.`CONSTRAINT_SCHEMA`
    AND `t`.`TABLE_NAME` = `tc`.`TABLE_NAME`
LEFT JOIN 
    `INFORMATION_SCHEMA`.`REFERENTIAL_CONSTRAINTS` AS `rc`
    ON `kcu`.`CONSTRAINT_NAME` = `rc`.`CONSTRAINT_NAME`
    AND `kcu`.`TABLE_SCHEMA` = `rc`.`CONSTRAINT_SCHEMA`
    AND `t`.`TABLE_NAME` = `rc`.`TABLE_NAME` 
WHERE 
    `t`.`TABLE_SCHEMA` = '$database'
    AND `t`.`TABLE_TYPE` = 'BASE TABLE'
ORDER BY 
    `t`.`TABLE_NAME`, `c`.`ORDINAL_POSITION`;
";
    }
}
