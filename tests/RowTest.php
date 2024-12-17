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
        $this->assertEquals('Test', $row->name);
        $this->assertNull($row->non_existing);
    }

    public function testSetGet()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);

        $row->username = 'New Username';
        $this->assertEquals('New Username', $row->username);

        $row->name = 'New Name'; // cannot set a non existing column
        $this->assertEquals('Test', $row->name);

        $row->non_existing = 'New Value';
        $this->assertNull($row->non_existing);
;
        $this->assertNull($row->no_param);

        $row->null_param =  null;
        $this->assertNull($row->null_param);

        $row->empty_string =  '';
        $this->assertEquals('', $row->empty_string);
    }

    public function testIsNew()
    {
        $row = new Row($this->connection, $this->table, $this->data_form);
        $this->assertTrue($row->isNew());
        $row->load();
        $this->assertFalse($row->isNew());
    }

    public function testIsAltered()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $this->assertFalse($row->isAltered());
        $row->name = 'New Name';
        $this->assertFalse($row->isAltered());
        $row->username = 'New Username';
        $this->assertTrue($row->isAltered());
    }

    public function testExport()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->name= 'New Name';
        $expected_export = ['id' => 1, 'name' => 'Test', 'username' => 'john_doe'];
        $this->assertEquals($expected_export, $row->export());
    }

    public function testLoad()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->name = 'New Name';

        $expected = [
            'id' => 1,
            'name' => 'Test',
            'username' => 'john_doe',
            'email' => 'john@example.com'
        ];
        
        $row->load();

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $row->$key);
        }

        $this->assertFalse($row->isNew());
    }

    public function testImport()
    {
        $row = new Row($this->connection, $this->table, $this->data_form_with_id);
        $row->import(['name' => 'New Name']);
        $this->assertFalse($row->isAltered());
        $this->assertEquals('Test', $row->name);
        
        $row->import(['username' => __FUNCTION__, 'email' => __FUNCTION__, 'invalid' => __FUNCTION__]);
        $this->assertTrue($row->isAltered());
        $this->assertEquals(__FUNCTION__, $row->username);
        $this->assertEquals(__FUNCTION__, $row->email);
        $this->assertNull($row->invalid);
    }
}
