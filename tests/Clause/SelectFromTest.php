²<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\SelectFrom;



class SelectFromTest extends TestCase
{
    public function testConstructor()
    {
        $selectFrom = new SelectFrom('users', 'u');
        $this->assertEquals('users', $selectFrom->table());
        $this->assertEquals('u', $selectFrom->alias());
    }

    public function testName()
    {
        $selectFrom = new SelectFrom('users');
        $this->assertEquals('SELECT FROM', $selectFrom->name());
    }

    public function testAll()
    {
        $selectFrom = new SelectFrom('users', 'u');
        $selectFrom->all();
        $this->assertStringContainsString('`u`.*', (string)$selectFrom);
    }

    public function testToString()
    {
        $selectFrom = new SelectFrom('users', 'u');
        $selectFrom->all();
        $this->assertEquals('SELECT `u`.* FROM `users` AS `u`', (string)$selectFrom);

        $selectFrom = new SelectFrom('users');
        $selectFrom->all();
        $this->assertEquals('SELECT `users`.* FROM `users`', (string)$selectFrom);
    }
}