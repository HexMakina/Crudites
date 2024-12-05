<?php

use PHPUnit\Framework\TestCase;

use HexMakina\Crudites\Grammar\Query\Select;
use HexMakina\Crudites\Grammar\Clause\Where;
use HexMakina\Crudites\Grammar\Predicate;

class SelectTest extends TestCase
{
    public function testConstructor()
    {
        $columns = ['id', 'contact' => 'email', 'person' => ['name']];
        $table = 'users';
        $table_alias = 'u';

        $select = new Select($columns, $table, $table_alias);

        $this->assertEquals($table, $select->table());
        $this->assertEquals($table_alias, $select->alias());
        $this->assertEquals($table_alias, $select->base());
        $this->assertEquals('SELECT id,email AS `contact`,`name` AS `person` FROM `users` `u`', (string)$select);
    }

    public function testStatementWithTable()
    {
        $columns = ['id', 'name'];
        $table = 'users';
        $select = new Select($columns, $table);

        $expected = 'SELECT id,name FROM `users`';
        $this->assertEquals($expected, $select->statement());
    }

    public function testSelectAlso()
    {
        $columns = ['id', 'name'];
        $table = 'users';
        $select = new Select($columns, $table);

        $additional_columns = ['email', 'age'];
        $select->selectAlso($additional_columns);

        $expected = 'SELECT id,name,email,age FROM `users`';
        $this->assertEquals($expected, $select->statement());
    }

    public function testSelectAlsoWithEmptyArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EMPTY_SETTER_ARRAY');

        $columns = ['id', 'name'];
        $table = 'users';
        $select = new Select($columns, $table);
        $select->selectAlso([]);
    }

    public function testSelectWhere()
    {
        $columns = ['id', 'name'];
        $table = 'users';
        $select = new Select($columns, $table);
        $predicate = new Predicate('id', '=', 1);
        $where = new Where($select->base(), [$predicate]);
        $select->add($where);

        $expected = 'SELECT id,name FROM `users` WHERE id = 1';
        $this->assertEquals($expected, (string)$select);

        $where->andPredicate((new Predicate('name', 'LIKE'))->withValue('John%'));
        $expected = 'SELECT id,name FROM `users` WHERE id = 1 AND name LIKE :name';
        $this->assertEquals($expected, (string)$select);
    }
}
