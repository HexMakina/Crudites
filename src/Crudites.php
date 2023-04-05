<?php


namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\{DatabaseInterface, TableInterface, SelectInterface};
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
     * @param DatabaseInterface $database  Database instance
     * @return void
     */
    public static function setDatabase(DatabaseInterface $database): void
    {
        self::$database = $database;
    }

    /**
     * @param string|null $dsn  DSN
     * @param string|null $user  Username
     * @param string|null $pass  Password
     * @return Connection  Database connection
     * @throws CruditesException
     */
    public static function connect($dsn = null, $user = null, $pass = null)
    {
        // no props, means connection already exists, verify and return
        if (!isset($dsn, $user, $pass)) {
            if (is_null(self::$database)) {
                throw new CruditesException('CONNECTION_MISSING');
            }

            return self::$database->connection();
        }
        return new Connection($dsn, $user, $pass);
    }

    
    /**
     * @param string $table_name  Table name
     * @throws CruditesException
     * @return array  Inspection of the given table name
     */
    public static function inspect(string $table_name): TableInterface
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

    // success: return AIPK-indexed array of results (associative array or object)
    /**
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

    public static function raw($sql, $dat_ass = []): ?\PDOStatement
    {
        $conx = self::connect();
        if (empty($dat_ass)) {
            $res = $conx->query($sql);
            //TODO query | alter !
            //$res = $conx->alter($sql);
        } else {
            $res = $conx->prepare($sql);
            $res->execute($dat_ass);
        }
        return $res === false ? null : $res;
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
            return false;
        }
        if (!$column->type()->isBoolean()) {
            return false;
        }

        // TODO: still using 'id' instead of table->primaries
        // TODO: not using the QueryInterface Way of binding stuff
        $Query = $table->update();
        $statement = sprintf(
            "UPDATE %s SET %s = !%s WHERE id=:id",
            $table->name(),
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
