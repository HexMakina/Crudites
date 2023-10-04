<?php


namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\{DatabaseInterface, ConnectionInterface, SelectInterface};
use HexMakina\Crudites\CruditesException;


/**
 * Crudités, it's a cup of carrots sticks (but are they organic ?)
 * Codd's Relational model, Unicity, Definitions, Introspection, Tests, Execution & Sets
 * Create - Retrieve - Update - Delete
 * Library for writing and running SQL queries
 */


class Crudites
{
    /**
     * @var DatabaseInterface|null $database  Database instance
     */
    private static ?DatabaseInterface $database;

    /**
     * takes a DatabaseInterface object and sets it as the global database object that the other methods will use
     */
    public static function setDatabase(DatabaseInterface $database): void
    {
        self::$database = $database;
    }

    public static function inspect(string $table_name)
    {
        if (is_null(self::$database)) {
            throw new CruditesException('NO_DATABASE');
        }

        try {
            return self::$database->inspect($table_name);
        } catch (\Exception $exception) {
            throw new CruditesException('TABLE_INTROSPECTION::' . $table_name);
        }
    }
    /**
     * connects to the database; if the connection already exists, the function verifies and returns it. 
     * If no connection exists, a Connection object is created with the provided parameters.
     */
    public static function connect($dsn = null, $user = null, $pass = null): ConnectionInterface
    {
        // no props, assumes connection made, verify and return
        if (!isset($dsn, $user, $pass)) {
            if (is_null(self::$database)) {
                throw new CruditesException('NO_DATABASE');
            }

            return self::$database->connection();
        }
        
        return new Connection($dsn, $user, $pass);
    }

    //------------------------------------------------------------  DataRetrieval

    /**
     * @param SelectInterface $select  Select instance
     * @return int|null  Number of records
     */
    public static function count(SelectInterface $select): ?int
    {
        $select->selectAlso(['COUNT(*) as count']);
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
    public static function retrieve(SelectInterface $select): array
    {
        $pk_name = implode('_', array_keys($select->table()->primaryKeys()));

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
        $conx = self::$database->connection();
        if (empty($dat_ass)) {
            $res = $conx->query($sql);
        } else {
            $res = $conx->prepare($sql);
            $res->execute($dat_ass);
        }
        
        return $res instanceof \PDOStatement? $res : null;
    }

    public static function distinctFor($table, $column_name, $filter_by_value = null)
    {
        $table = self::tableNameToTable($table);

        if (is_null($table->column($column_name))) {
            throw new CruditesException('TABLE_REQUIRES_COLUMN');
        }

        $Query = $table->select([sprintf('DISTINCT `%s`', $column_name)])
          ->whereNotEmpty($column_name)
          ->orderBy([$table->name(), $column_name, 'ASC']);

        if (!is_null($filter_by_value)) {
            $Query->whereLike($column_name, sprintf('%%%s%%', $filter_by_value));
        }

        $Query->orderBy($column_name, 'DESC');
        // ddt($Query);
        return $Query->retCol();
    }

    public static function distinctForWithId($table, $column_name, $filter_by_value = null)
    {
        $table = self::tableNameToTable($table);

        if (is_null($table->column($column_name))) {
            throw new CruditesException('TABLE_REQUIRES_COLUMN');
        }

        $Query = $table->select([sprintf('DISTINCT `id`,`%s`', $column_name)])
          ->whereNotEmpty($column_name)->orderBy([$table->name(), $column_name, 'ASC']);

        if (!is_null($filter_by_value)) {
            $Query->whereLike($column_name, sprintf('%%%s%%', $filter_by_value));
        }

        return $Query->retPar();
    }

    //------------------------------------------------------------  DataManipulation Helpers
    // returns true on success, false on failure or throws an exception
    // throws Exception on failure
    public static function toggleBoolean($table, $boolean_column_name, $id): bool
    {

        $table = self::tableNameToTable($table);
        if (is_null($column = $table->column($boolean_column_name))) {
            throw new \InvalidArgumentException('TOGGLE_REQUIRES_EXISTING_COLUMN');
        }
        if (!$column->type()->isBoolean()) {
            throw new \InvalidArgumentException('TOGGLE_REQUIRES_BOOLEAN_COLUMN');
        }

        // TODO: still using 'id' instead of table->primaries
        // TODO: not using the QueryInterface Way of binding stuff
        $Query = $table->update();
        $statement = sprintf(
            "UPDATE %s SET %s = COALESCE(!%s, 1) WHERE id=:id",
            $table->name(),
            $boolean_column_name,
            $boolean_column_name,
            $boolean_column_name
        );
        $Query->statement($statement);
        $Query->setBindings([':id' => $id]);
        $Query->run();

        return $Query->isSuccess();
    }

    private static function tableNameToTable($table)
    {
        return is_string($table) ? self::inspect($table) : $table;
    }
}
