<?php

namespace HexMakina\Crudites\Schema;

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

    public static function cache(string $database): ?SchemaInterface
    {
        $cache_file = __DIR__ . '/cache/' . $database . '.php';
        if(!file_exists($cache_file)){
            return null;
        }

        return new Schema($database, require $cache_file);
    }

    public static function load(string $database, \PDO $pdo, string $schema_database = 'information_schema'): SchemaInterface
    {
        
        try {
            $pdo->beginTransaction();
            
            // switch to the INFORMATION_SCHEMA database
            if(false === $pdo->query(sprintf('USE %s;', $schema_database))){
                throw new CruditesException('SWICTH_TO_INFORMATION_SCHEMA');
            }
            
            // get the schema information
            $res = $pdo->query(self::informationSchemaQuery($database));
            if(false === $res){
                throw new CruditesException('LOAD_INFORMATION_SCHEMA');
            }
            
            // switch back to the original database
            if(false === $pdo->query(sprintf('USE %s;', $database))){
                throw new CruditesException('SWICTH_BACK_TO_USER_DATABASE');
            }

            $pdo->commit();
            
        } catch (\Exception $e) {
            
            $pdo->rollBack();
            throw $e;
        }

        $res = $res->fetchAll();
        if($res === false){
            throw new CruditesException('SCHEMA_LOAD_FETCHALL');
        }
        
        if(empty($res)){
            throw new CruditesException('SCHEMA_LOAD_FETCHED_EMPTY_RESULTS');
        }

        var_dump($res);
        die;        
        return new Schema($database, self::parseInformationSchema($res));
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
            $tables[$table]['columns'][$column] ??= ['schema' => $res, 'unique' => []];

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
        return "SELECT 
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
