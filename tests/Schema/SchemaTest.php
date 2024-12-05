<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Connection;
use HexMakina\Crudites\Schema\Schema;
use HexMakina\Crudites\Schema\SchemaAttribute;
use HexMakina\BlackBox\Database\SchemaAttributeInterface;


class SchemaTest extends TestCase
{
    private Connection $connection;
    private Schema $schema;
    // setup
    public function setUp(): void
    {
        // code to execute before each test

        $dsn = 'mysql:host=localhost;dbname=crudites;charset=utf8';
        $this->connection = new Connection($dsn, 'root', 'changeme0');
        $this->schema = $this->connection->schema();
    }

    public function testDatabase()
    {
        $this->assertEquals('crudites', $this->schema->database());
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

    public function testColumns()
    {
        $schema = $this->connection->schema();
        $columns = $schema->columns('users');

        $this->assertIsArray($columns);

        $this->assertEquals(4, count($columns));

        $this->assertContains('id', $columns);
        $this->assertContains('username', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('created_at', $columns);
    }

    public function testHasTable()
    {
        $schema = $this->connection->schema();
        $this->assertTrue($schema->hasTable('users'));
        $this->assertFalse($schema->hasTable('non_existent_table'));
    }

    public function testHasColumn()
    {
        $schema = $this->connection->schema();
        $this->assertTrue($schema->hasColumn('users', 'id'));
        $this->assertFalse($schema->hasColumn('users', 'non_existent_column'));
        $this->assertFalse($schema->hasColumn('non_existent_table', 'non_existent_column'));
    }

    public function testColumn()
    {
        $column = $this->schema->column('users', 'id');
        $this->assertIsArray($column);
        $this->assertArrayHasKey('table', $column);
        $this->assertArrayHasKey('column', $column);
        $this->assertEquals('users', $column['table']);
        $this->assertEquals('id', $column['column']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CANNOT FIND COLUMN non_existent_column IN TABLE users');
        $this->schema->column('users', 'non_existent_column');
        
    }

    public function testAutoIncrementedPrimaryKey()
    {
        $this->assertEquals('id', $this->schema->autoIncrementedPrimaryKey('users'));
        $this->assertEquals('product_id', $this->schema->autoIncrementedPrimaryKey('products'));
    }

    public function testPrimaryKeys()
    {
        $this->assertIsArray($this->schema->primaryKeys('users'));
        $this->assertEquals(['review_id'], $this->schema->primaryKeys('product_reviews'));
    }

    public function testForeignKeys()
    {
        $res = $this->schema->foreignKeys('orders');
        $this->assertIsArray($res);
        $this->assertEquals(['user_id' => ['users', 'id', 'NO ACTION', 'CASCADE']], $res);

        $res = $this->schema->foreignKeys('order_items');
        $this->assertIsArray($res);
        $this->assertEquals([
            'order_id' => ['orders', 'order_id', 'CASCADE', 'CASCADE'], 
            'product_id' => ['products', 'product_id', 'CASCADE', 'CASCADE']], $res);

        $res = $this->schema->foreignKeys('products');
        $this->assertIsArray($res);
        $this->assertEquals([], $res);
    }

    public function testUniqueKeys()
    {
        $res = $this->schema->uniqueKeys('users');

        $this->assertIsArray($res);
        $this->assertArrayHasKey('username_unique', $res);
        $this->assertArrayHasKey('email_unique', $res);
        $this->assertEquals(['username'], $res['username_unique']);
        $this->assertEquals(['email'], $res['email_unique']);

        $res = $this->schema->uniqueKeys('products');
        $this->assertEquals([], $res);
    }
}
