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

        $where = new Where([new Predicate('id', '=', 1)]);
        $select->add($where);

        $expected = 'SELECT id,name FROM `users` WHERE id = 1';
        $this->assertEquals($expected, (string)$select);
        $this->assertEmpty($select->bindings());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PREDICATE_REQUIRES_A_BIND_LABEL');
        $where->andPredicate((new Predicate('name', 'LIKE'))->withValue('John%'));

        $where->andPredicate((new Predicate('name', 'LIKE'))->withValue('John%'), 'search_name');
        $expected_bindings = ['name' => 'John%'];
        $expected .= ' AND name LIKE :name';
        $this->assertEquals($expected, (string)$select);
        $this->assertEquals($expected_bindings, $where->bindings());
        $this->assertEquals($expected_bindings, $select->bindings());


        $where->andIsNull(['email']);
        $expected .= ' AND `email` IS NULL';

        $this->assertEquals($expected, (string)$select);
        $this->assertEquals($expected_bindings, $where->bindings());
        $this->assertEquals($expected_bindings, $select->bindings());

        $where->andFields(['age' => 23]);
        $expected_bindings['andFields_age'] = 23;
        $expected .= ' AND `age` = :andFields_age';
        $this->assertEquals($expected, (string)$select);
        $this->assertEquals($expected_bindings, $where->bindings());
        $this->assertEquals($expected_bindings, $select->bindings());
    }
}
