<?php

/**
 * DSN model for validation
 *
 *
 * Throws CruditesException when DSN is wrong
 */

namespace HexMakina\Crudites;

class Source
{
    private $dsn = null;
    private $name = null;

    public function DSN()
    {
        return $this->dsn;
    }

    public function name()
    {
        return $this->name;
    }

  /*
   * @throws CruditesException if $dsn string is invalid or incomplete
   */
    public function __construct($dsn)
    {
        $driver = self::extractDriverName($dsn);
        if (!empty($driver) && self::isAvailable($driver)) {
            $this->name = self::extractDatabaseName($dsn);
            $this->dsn = $dsn;
        }
    }

  /*
   * @return string the driver name extracted from the $dsn string
   * @throws CruditesException if no driver name was parsed from the DSN
   */
    private static function extractDriverName($dsn)
    {
        $matches = [];
        if (preg_match('/^([a-z]+)\:/', $dsn, $matches) !== 1) {
            return $matches[1];
        }
        throw new CruditesException('DSN_NO_DRIVER');
    }

  /*
   * @return boolean availability of driver name
   * @throws CruditesException if driver name is not in \PDO::getAvailableDrivers()
   */
    private static function isAvailable($driverName)
    {
        if (in_array($driverName, \PDO::getAvailableDrivers(), true)) {
            return true;
        }
        throw new CruditesException('DSN_UNAVAILABLE_DRIVER');
    }

  /*
   * @return string the database name extracted from the $dsn string
   * @throws CruditesException if no database name was parsed from the DSN
   */
    private static function extractDatabaseName($dsn)
    {
        $matches = [];
        if (preg_match('/dbname=(.+);/', $dsn, $matches) !== 1) {
            return $matches[1];
        }
        throw new CruditesException('DSN_NO_DBNAME');
    }
}
