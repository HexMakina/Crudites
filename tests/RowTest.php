<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Table\Row;
use HexMakina\Crudites\Connection;


class RowTest extends TestCase
{
    private Row $row;
    
    // setup
    public function setUp(): void
    {
        // code to execute before each test
        $connection = new Connection('mysql:host=localhost;dbname=crudites;charset=utf8', 'crudites', '2ce!fNe8(weVz3k4TN#');
        
        $this->row = new Row($connection, 'users', ['id' => 1, 'name' => 'Test']);
    }

    public function testConstructor()
    {
        $this->assertEquals('users', $this->row->table());
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $this->row->export());
    }

    public function testGet()
    {
        $this->assertEquals('Test', $this->row->get('name'));
        $this->assertNull($this->row->get('non_existing'));
    }

    public function testSet()
    {
        $this->row->set('name', 'New Name');
        $this->assertEquals('New Name', $this->row->get('name'));
    }

    public function testIsNew()
    {
        $this->assertTrue($this->row->isNew());
        $this->row->load();
    }

    public function testIsAltered()
    {
        $this->assertFalse($this->row->isAltered());
        $this->row->set('name', 'New Name');
        $this->assertTrue($this->row->isAltered());
    }
    public function testExport()
    {
        $this->row->set('name', 'New Name');
        $this->assertEquals(['id' => 1, 'name' => 'New Name'], $this->row->export());
    }

    public function testLoad()
    {
        $this->row->set('name', 'New Name');

        $expected = [
            'id' => 1,
            'name' => 'New Name',
            'username' => 'john_doe',
            'email' => 'john@example.com'
        ];
        
        $this->row->load();

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $this->row->get($key));
        }

        $this->assertFalse($this->row->isNew());

    }
}
