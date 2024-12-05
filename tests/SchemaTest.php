<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Connection;


class SchemaTest extends TestCase
{
    private Connection $connection;
    private Schema $schema;
    // setup
    public function setUp(): void
    {
        $dsn = 'mysql:host=localhost;dbname=crudites;charset=utf8';
        $this->connection = new Connection($dsn, 'root', 'changeme0');

        // code to execute before each test
    }

    public function testTables()
    {
        $schema = $this->connection->schema();
        $tables = $schema->tables();
        
        $this->assertIsArray($tables);

        $this->assertEquals(6, count($tables));
        
        $this->assertContains('product_reviews', $tables);
        $this->assertContains('users', $tables);
        $this->assertContains('products', $tables);
        $this->assertContains('data_types_table', $tables);
        $this->assertContains('orders', $tables);
        $this->assertContains('order_items', $tables);

    }


}
