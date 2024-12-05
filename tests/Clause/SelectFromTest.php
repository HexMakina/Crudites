²<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\SelectFrom;



class SelectFromTest extends TestCase
{
    public function testConstructor()
    {
        $selectFrom = new SelectFrom('users');
        $this->assertEquals('SELECT * FROM `users`', (string)$selectFrom);

        $selectFrom = new SelectFrom('users', 'u');
        $this->assertEquals('users', $selectFrom->table());
        $this->assertEquals('u', $selectFrom->alias());
        $this->assertEquals('SELECT * FROM `users` `u`', (string)$selectFrom);
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

        $this->assertEquals('SELECT * FROM `users` `u`', (string)$selectFrom);
    }

    public function testAddRaw()
    {
        $selectFrom = new SelectFrom('users', 'u');
        $selectFrom->addRaw('COUNT(*)');

        $this->assertEquals('SELECT COUNT(*) FROM `users` `u`', (string)$selectFrom);
    }

    public function testAddString()
    {
        $selectFrom = new SelectFrom('users', 'u');
        $selectFrom->add('id');

        $this->assertEquals('SELECT id FROM `users` `u`', (string)$selectFrom);
    }

    public function testAddArray()
    {
        $selectFrom = new SelectFrom('users', 'u');
        $selectFrom->add(['id']);
        $selectFrom->add(['name']);

        $this->assertEquals('SELECT `id`,`name` FROM `users` `u`', (string)$selectFrom);

        $selectFrom = new SelectFrom('users', 'u');
        $selectFrom->add(['id']);
        $selectFrom->add(['u', 'name']);

        $this->assertEquals('SELECT `id`,`u`.`name` FROM `users` `u`', (string)$selectFrom);

                $selectFrom = new SelectFrom('users');
        $selectFrom->add(['id']);
        $selectFrom->add(['users', 'name']);

        $this->assertEquals('SELECT `id`,`users`.`name` FROM `users`', (string)$selectFrom);
    }
}