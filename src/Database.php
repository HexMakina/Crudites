<?php

namespace HexMakina\Crudites;

use HexMakina\Crudites\Table\Manipulation;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableDescriptionInterface;
use HexMakina\BlackBox\Database\TableManipulationInterface;
use HexMakina\BlackBox\Database\TableColumnInterface;

class Database implements DatabaseInterface
{
    private const INTROSPECTION_DATABASE_NAME = 'INFORMATION_SCHEMA';

    private ConnectionInterface $connection;
    private Schema $schema;

    /** @var array<string,TableManipulationInterface> */
    private $table_cache = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema($this);
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function name(): string
    {
        return $this->connection()->databaseName();
    }

    public function inspect(string $table_name): TableManipulationInterface
    {
        if (isset($this->table_cache[$table_name])) {
            return $this->table_cache[$table_name];
        }

        $table = new Manipulation($table_name, $this->connection());
        $table->describe($this->schema);

        $this->table_cache[$table_name] = $table;
        return $this->table_cache[$table_name];
    }
}
