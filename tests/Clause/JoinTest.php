<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\Join;
use HexMakina\Crudites\Grammar\Predicate;

class JoinTest extends TestCase
{
    public function testConstructor()
    {
        $join = new Join('cbx_order', 'Orders');
        $this->assertEquals('cbx_order', $join->table());
        $this->assertEquals('Orders', $join->alias());
    }

    public function testConstructorWithDefaultAlias()
    {
        $join = new Join('cbx_order');
        $this->assertEquals('cbx_order', $join->table());
        $this->assertEquals('cbx_order', $join->alias());
    }

    public function testType()
    {
        $join = new Join('cbx_order', 'Orders');
        $join->type('LEFT');
        $this->assertEquals('LEFT JOIN `cbx_order` Orders ON', (string)$join);
    }

    public function testOn()
    {
        $join = new Join('cbx_order', 'Orders');
        $join->on('user_id', 'User', 'id');
        $this->assertEquals('JOIN `cbx_order` Orders ON `Orders`.`user_id` = `User`.`id`', (string)$join);
    }

    public function testToString()
    {
        $join = new Join('cbx_order', 'Orders');
        $join->type('LEFT')->on('user_id', 'User', 'id');
        $expectedString = sprintf('LEFT JOIN `%s` %s ON %s', 'cbx_order', 'Orders', (string)(new Predicate(['Orders', 'user_id'], '=', ['User', 'id'])));
        $this->assertEquals($expectedString, (string)$join);
    }

    public function testName()
    {
        $join = new Join('cbx_order', 'Orders');
        $this->assertEquals('JOIN', $join->name());
    }
}
