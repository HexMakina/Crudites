<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\{ConnectionInterface, SchemaAttributeInterface};

use HexMakina\Crudites\CruditesException;

use HexMakina\Crudites\Grammar\Query\Select;
use HexMakina\Crudites\Grammar\Clause\Where;
use HexMakina\Crudites\Grammar\Clause\OrderBy;
use HexMakina\Crudites\Grammar\Predicate;


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
        if (isset($dsn, $user, $pass)) {
            self::$connection = new Connection($dsn, $user, $pass);
        }
        elseif (self::$connection === null) {
            throw new CruditesException('NO_DATABASE');
        }

        return self::$connection;
    }

    //------------------------------------------------------------  DataRetrieval

    /**
     * @param QueryInterface $select  Select instance
     * @return int|null  Number of records
     */
    public static function count(Select $select): ?int
    {
        $select->selectAlso(['count' => ['COUNT(*)']]);
        $res = self::$connection->result($select);
        $res = $res->ret(\PDO::FETCH_COLUMN);
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
    public static function retrieve(Select $select): array
    {
        $ret = [];

        $res = self::$connection->result($select);
        if ($res->ran()) {
            $ret = $res->ret(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
        }

        return $ret;
    }

    public static function distinctFor(string $table, string $column_name, string $filter_by_value = null)
    {
        $query = self::$connection->schema()->select($table, [sprintf('DISTINCT `%s`', $column_name)]);
        $where = $query->where([(new Predicate([$table, $column_name]))->isNotEmpty()]);

        if ($filter_by_value !== null) {
            $where->andLike([$table, $column_name], $filter_by_value);
        }
        
        $query->orderBy([$table, $column_name], 'ASC');

        return self::$connection->result($query)->ret(\PDO::FETCH_COLUMN);
    }

    public static function distinctForWithId(string $table, string $column_name, string $filter_by_value = null)
    {
        $Query = self::$connection->schema()->select($table [sprintf('DISTINCT `id`,`%s`', $column_name)]);
        
        $clause = new Where([(new Predicate([$table, $column_name]))->isNotEmpty()]);
        if ($filter_by_value !== null) {
            $clause->andLike([$table, $column_name], $filter_by_value);
        }
        $Query->add($clause);

        $clause = new OrderBy([$table, $column_name], 'ASC');
        $Query->add($clause);

        return self::$connection->result($Query)->ret(\PDO::FETCH_KEY_PAIR);
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

        $where = (new Where())->andFields($unique_match, $table);

        $query = "UPDATE $table SET $boolean_column = COALESCE(!$boolean_column, 1) WHERE $where";
        $res = self::$connection->result($query);

        return $res->ran();
        
        // TODO: not using the QueryInterface Way of binding stuff
        // $where = [];
        // $bindings = [];
        // foreach ($unique_match as $column_name => $value) {
        //     $binding_label = sprintf(':%s', $column_name);
        //     $where[] = sprintf('`%s` = %s', $column_name, $binding_label);
        //     $bindings[$binding_label] = $value;
        // }

        // $where = implode(' AND ', $where);


    }
}
