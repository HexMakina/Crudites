<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\{ConnectionInterface, SchemaAttributeInterface};
use HexMakina\Crudites\CruditesException;


/**
 * Crudités, it's a cup of carrots sticks (but are they organic ?)
 * Codd's Relational model, Unicity, Definitions, Introspection, Tests, Execution & Sets
 * Create - Retrieve - Update - Delete
 * Library for writing and running SQL queries
 */
class Crudites
{
    protected static ?ConnectionInterface $connection;

    public static function setConnection(ConnectionInterface $connection): void
    {
        self::$connection = $connection;
    }

    public static function connection(): ConnectionInterface
    {
        return self::$connection;
    }

    /**
     * connects to the database; if the connection already exists, the function verifies and returns it. 
     * If no connection exists, a Connection object is created with the provided parameters.
     */
    public static function connect($dsn = null, $user = null, $pass = null): ConnectionInterface
    {
        // no props, assumes connection made, verify and return
        if (!isset($dsn, $user, $pass)) {
            if (self::$connection === null) {
                throw new CruditesException('NO_DATABASE');
            }

            return self::$connection;
        }
        
        return new Connection($dsn, $user, $pass);
    }

    //------------------------------------------------------------  DataRetrieval

    /**
     * @param QueryInterface $select  Select instance
     * @return int|null  Number of records
     */
    public static function count(QueryInterface $select): ?int
    {
        $select->selectAlso(['count' => ['COUNT(*)']]);
        $res = $select->retCol();
        if (is_array($res)) {
            return (int) current($res);
        }

        return null;
    }

    /**
     * retrieve(): A method that retrieves data from a SELECT statement, organizes them 
     * in an associative array using their primary keys as the indices
     * 
     * @return array<int|string, mixed>
     */
    public static function retrieve(QueryInterface $select): array
    {
        $primary_keys = self::$connection->schema()->primaryKeys($select->table());
        $pk_name = implode('_', $primary_keys);

        $ret = [];

        if ($select->run()->isSuccess()) {
            foreach ($select->retAss() as $rec) {
                $ret[$rec[$pk_name]] = $rec;
            }
        }

        return $ret;
    }

    /**
     * Executes a custom SQL statement and returns a PDOStatement object, 
     * optionally binding variables to the statement.
     */
    public static function raw($sql, $dat_ass = []): ?\PDOStatement
    {
        if (empty($dat_ass)) {
            $res = self::$connection->query($sql);
        } else {
            $res = self::$connection->prepare($sql);
            $res->execute($dat_ass);
        }
        
        return $res instanceof \PDOStatement? $res : null;
    }

    public static function distinctFor(string $table, string $column_name, string $filter_by_value = null)
    {
        $query = self::$connection->schema()->select($table, [sprintf('DISTINCT `%s`', $column_name)]);
        $query->whereNotEmpty($column_name);
        $query->orderBy([$column_name, 'ASC']);

        if ($filter_by_value !== null) {
            $query->whereLike($column_name, sprintf('%%%s%%', $filter_by_value));
        }

        return $query->retCol();
    }

    public static function distinctForWithId(string $table, string $column_name, string $filter_by_value = null)
    {
        $Query = self::$connection->schema()->select($table [sprintf('DISTINCT `id`,`%s`', $column_name)])
          ->whereNotEmpty($column_name)->orderBy([$column_name, 'ASC', $table]);

        if ($filter_by_value !== null) {
            $Query->whereLike($column_name, sprintf('%%%s%%', $filter_by_value));
        }

        return $Query->retPar();
    }

    //------------------------------------------------------------  DataManipulation Helpers
    // returns true on success, false on failure or throws an exception
    // throws Exception on failure
    public static function toggleBoolean(string $table, string $boolean_column, array $unique_match): bool
    {
        $attribute = self::$connection->schema()->attributes($table, $boolean_column);
        if (!$attribute->type() === SchemaAttributeInterface::TYPE_BOOLEAN) {
            throw new CruditesException('TOGGLE_REQUIRES_BOOLEAN_COLUMN');
        }

        // throws exception if the table or column does not exist
        $unique_match = self::$connection->schema()->matchUniqueness($table, $unique_match);
        if (empty($unique_match)) {
            throw new CruditesException('NO_MATCH_TO_UNIQUE_RECORD');
        }


        // TODO: not using the QueryInterface Way of binding stuff
        $where = [];
        $bindings = [];
        foreach ($unique_match as $column_name => $value) {
            $binding_label = sprintf(':%s', $column_name);
            $where[] = sprintf('`%s` = %s', $column_name, $binding_label);
            $bindings[$binding_label] = $value;
        }

        $where = implode(' AND ', $where);
        $Query = self::raw("UPDATE $table SET $boolean_column = COALESCE(!$boolean_column, 1) WHERE $where", $bindings);
        return $Query->errorCode() === '00000';
    }
}
