<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Connection;
use HexMakina\Crudites\Schema\Schema;


class SchemaQueryTest extends TestCase
{
    private Connection $connection;
    private Schema $schema;

    // setup
    public function setUp(): void
    {
        $dsn = 'mysql:host=localhost;dbname=crudites;charset=utf8';
        $this->connection = new Connection($dsn, 'root', 'changeme0');
        $this->schema = new Schema($this->connection);

        // code to execute before each test
    }
    
    public function testSelect()
    {
        $query = $this->schema->select('users');
        $this->assertEquals('SELECT * FROM `users`', (string)$query);

        // $query = $this->schema->select('users', ['id', 'name', ['email']]);
        // $this->assertEquals('SELECT id,name,`email` FROM `users`', (string)$query);
    }
}
