<?php

/**
 * Crudités, it's a cup of carrots sticks (but are they organic ?)
 * Codd's Relational model, Unicity, Definitions, Introspection, Tests, Execution & Sets
 * Create - Retrieve - Update - Delete
 * API for writing and running SQL queries
 */

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\Crudites\CruditesException;

class Crudites
{
    private static $database = null;

    public static function setDatabase(DatabaseInterface $db)
    {
        self::$database = $db;
    }

    public static function inspect($table_name)
    {
        if (is_null(self::$database)) {
            throw new CruditesException('NO_DATABASE');
        }

        try {
            return self::$database->inspect($table_name);
        } catch (\Exception $e) {
            throw new CruditesException('TABLE_INTROSPECTION::' . $table_name);
        }
    }

    public static function connect($dsn = null, $user = null, $pass = null)
    {
        // no props, means connection already exists, verify and return
        if (!isset($dsn, $user, $pass)) {
            if (is_null(self::$database)) {
                throw new CruditesException('CONNECTION_MISSING');
            }

            return self::$database->connection();
        }

        $conx = new Connection($dsn, $user, $pass);
        return $conx;
    }

    //------------------------------------------------------------  DataRetrieval
    // success: return AIPK-indexed array of results (associative array or object)
    public static function count(SelectInterface $Query)
    {
        $Query->selectAlso(['COUNT(*) as count']);
        $res = $Query->retCol();
        if (is_array($res)) {
            return intval(current($res));
        }
        return null;
    }

    // success: return AIPK-indexed array of results (associative array or object)
    public static function retrieve(SelectInterface $Query): array
    {
        $pk_name = implode('_', array_keys($Query->table()->primaryKeys()));

        $ret = [];

        if ($Query->run()->isSuccess()) {
            foreach ($Query->retAss() as $rec) {
                $ret[$rec[$pk_name]] = $rec;
            }
        }

        return $ret;
    }

    public static function raw($sql, $dat_ass = [])
    {
        $conx = self::connect();
        if (empty($dat_ass)) {
            $res = $conx->query($sql);
            //TODO query | alter !
            //$res = $conx->alter($sql);
        } else {
            $stmt = $conx->prepare($sql);
            $res = $stmt->execute($dat_ass);
        }
        return $res;
    }

    public static function distinctFor($table, $column_name, $filter_by_value = null)
    {
        $table = self::tableNameToTable($table);

        if (is_null($table->column($column_name))) {
            throw new CruditesException('TABLE_REQUIRES_COLUMN');
        }

        $Query = $table->select(["DISTINCT `$column_name`"])
          ->whereNotEmpty($column_name)
          ->orderBy([$table->name(), $column_name, 'ASC']);

        if (!is_null($filter_by_value)) {
            $Query->whereLike($column_name, "%$filter_by_value%");
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

        $Query = $table->select(["DISTINCT `id`,`$column_name`"])
          ->whereNotEmpty($column_name)->orderBy([$table->name(), $column_name, 'ASC']);

        if (!is_null($filter_by_value)) {
            $Query->whereLike($column_name, "%$filter_by_value%");
        }

        return $Query->retPar();
    }

    //------------------------------------------------------------  DataManipulation Helpers
    // returns true on success, false on failure or throws an exception
    // throws Exception on failure
    public static function toggleBoolean($table, $boolean_column_name, $id): bool
    {

        $table = self::tableNameToTable($table);

        if (is_null($column = $table->column($boolean_column_name)) || !$column->type()->isBoolean()) {
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