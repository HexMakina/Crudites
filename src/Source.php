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

    /**
     * Constructor method that initializes the $name attribute
     *
     * @param string $name A well formatted DSN string
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Returns the DSN string
     *
     * @return string The DSN string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the name of the database extracted from the DSN string
     *
     * @return string The name of the database extracted from the DSN string
     * @throws CruditesException if $name string is invalid or incomplete
     */
    public function database(): string
    {
        if (is_null($this->database)) {
            $this->database = $this->parseName('/dbname=(.+);/', 'DSN_DATABASE_NOT_FOUND');
        }
        return $this->database;
    }

    /**
     * Returns the driver name extracted from the DSN string
     *
     * @return string The driver name extracted from the DSN string
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
     * Parses the name of the database or driver from the DSN string
     *
     * @param string $regex The regular expression pattern used to parse the name
     * @param string $err The error message to throw if the name is not found
     * @return string The name parsed from the DSN string
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
