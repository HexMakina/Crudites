<?php

/**
 * OO wrapper for DSN string
 * Throws CruditesException when DSN is wrong
 */

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\SourceInterface;

class Source implements SourceInterface
{
    // DSN
    private string $name;

    // null because extracted from DSN when calling driver() or databaseName()
    private ?string $driver = null;
    private ?string $database = null;

    // give it a well formatted DSN
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    /*
     * @throws CruditesException if $name string is invalid or incomplete
     */
    public function database(): string
    {
        if (is_null($this->database)) {
            $this->database = $this->parseName('/dbname=(.+);/', 'DSN_DATABASE_NOT_FOUND');
        }
        return $this->database;
    }

    /*
     * @return string the driver name extracted from the $name string
     * @throws CruditesException if no driver name was parsed from the DSN
     */
    public function driver(): string
    {
        if (is_null($this->driver)) {
            $this->driver = $this->parseName('/^([a-z]+)\:/', 'DSN_DRIVER_NOT_FOUND');
        }
        return $this->driver;
    }

    /**
      * @return boolean availability of driver name
      */
    public function driverAvailable(): bool
    {
        return in_array($this->driver(), \PDO::getAvailableDrivers(), true);
    }

    /*
     * @throws CruditesException if $name string is invalid or incomplete
     */
    private function parseName(string $regex, string $err): string
    {
        $matches = [];

        if (1 !== preg_match($regex, $this->name(), $matches) || !isset($matches[1])) {
            throw new CruditesException($err);
        }

        return $matches[1];
    }
}
