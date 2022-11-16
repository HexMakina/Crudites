<?php

namespace HexMakina\Crudites;

use HexMakina\Crudites\Table\Table;
use HexMakina\BlackBox\Database\ConnectionInterface;
use HexMakina\BlackBox\Database\DatabaseInterface;
use HexMakina\BlackBox\Database\TableInterface;

class Database implements DatabaseInterface
{
    private ConnectionInterface $connection;
    private Schema $schema;

    /** @var array<string,TableInterface> */
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

    public function inspect(string $table_name): TableInterface
    {
        if (isset($this->table_cache[$table_name])) {
            return $this->table_cache[$table_name];
        }

        $table = new Table($table_name, $this->connection());
        $table->describe($this->schema);

        $this->table_cache[$table_name] = $table;
        return $this->table_cache[$table_name];
    }
}
