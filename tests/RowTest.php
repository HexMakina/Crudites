<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Row;
use HexMakina\Crudites\Connection;


class RowTest extends TestCase
{
    private Connection $connection;

    private string $table = 'users';
    private array $data_pk_match = ['id' => 1];
    private array $data_form = ['name' => 'Test', 'username' => 'john_doe'];
    private array $data_form_with_id = ['name' => 'Test', 'username' => 'john_doe'] + ['id' => 1];
    
    // setup
    public function setUp(): void
    {
        // code to execute before each test
        $this->connection = new Connection('mysql:host=localhost;dbname=crudites;charset=utf8', 'crudites', '2ce!fNe8(weVz3k4TN#');
    }

    public function testConstructor()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $this->assertEquals('users', $row->table());
        $this->assertEquals($this->data_form_with_id, $row->export());
    }

    public function testGet()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $this->assertEquals('Test', $row->get('name'));
        $this->assertNull($row->get('non_existing'));
    }

    public function testSetGet()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->set('name', 'New Name');
        $this->assertEquals('New Name', $row->get('name'));

        $row->set('non_existing', 'New Value');
        $this->assertEquals('New Value', $row->get('non_existing'));

        $row->set('no_param');
        $this->assertNull($row->get('no_param'));

        $row->set('null_param', null);
        $this->assertNull($row->get('null_param'));

        $row->set('empty_string', '');
        $this->assertEquals('', $row->get('empty_string'));
    }

    public function testIsNew()
    {
        $row = new Row($this->connection, $this->table, $this->data_form);
        $this->assertTrue($row->isNew());
        $row->load();
    }

    public function testIsAltered()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $this->assertFalse($row->isAltered());
        $row->set('name', 'New Name');
        $this->assertTrue($row->isAltered());
    }

    public function testExport()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->set('name', 'New Name');
        $expected_export = ['id' => 1, 'name' => 'New Name', 'username' => 'john_doe'];
        $this->assertEquals($expected_export, $row->export());
    }

    public function testLoad()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->set('name', 'New Name');

        $expected = [
            'id' => 1,
            'name' => 'New Name',
            'username' => 'john_doe',
            'email' => 'john@example.com'
        ];
        
        $row->load();

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $row->get($key));
        }

        $this->assertFalse($row->isNew());
    }

    public function testAlter()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->alter(['name' => 'New Name']);
        $this->assertFalse($row->isAltered());
        $this->assertEquals('Test', $row->get('name'));
        
        $row->alter(['username' => __FUNCTION__, 'email' => __FUNCTION__, 'invalid' => __FUNCTION__]);
        $this->assertTrue($row->isAltered());
        $this->assertEquals(__FUNCTION__, $row->get('username'));
        $this->assertEquals(__FUNCTION__, $row->get('email'));
        $this->assertNull($row->get('invalid'));
    }
}
