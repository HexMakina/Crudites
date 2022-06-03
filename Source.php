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
    private $driver = null;
    private $database = null;

    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }

    public function DSN(): string
    {
        return $this->dsn;
    }

    /*
     * @return string the driver name extracted from the $dsn string
     * @throws CruditesException if no driver name was parsed from the DSN
     */
    public function driver(): string
    {
        return $this->driver ?? $this->driver = self::extractDriverFromDSN();
    }

    /*
     * @throws CruditesException if $dsn string is invalid or incomplete
     */
    public function database(): string
    {
        return $this->database ?? $this->database = self::extractDatabaseFromDSN($this->dsn);
    }

    public static function extractDriverFromDSN($dsn): string
    {
      $matches = [];

      if (empty(preg_match('/^([a-z]+)\:/', $dsn, $matches))) {
          throw new CruditesException('DSN_NO_DRIVER');
      }

      if (!self::driverIsAvailable($matches[1])) {
          throw new CruditesException('DSN_UNAVAILABLE_DRIVER');
      }

      return $matches[1];
    }

  /*
   * @return boolean availability of driver name
   * @throws CruditesException if driver name is not in \PDO::getAvailableDrivers()
   */
    public static function driverIsAvailable($driver): bool
    {
        return in_array($driver, \PDO::getAvailableDrivers(), true);
    }

  /*
   * @return string the database name extracted from the $dsn string
   * @throws CruditesException if no database name was parsed from the DSN
   */
    public static function extractDatabaseFromDSN($dsn): string
    {
        $matches = [];

        if (empty(preg_match('/dbname=(.+);/', $dsn, $matches))) {
            throw new CruditesException('DSN_NO_DBNAME');
        }

        return $matches[1];
    }
}
