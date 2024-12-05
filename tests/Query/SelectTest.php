<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Query\Select;
use HexMakina\Crudites\CruditesException;



class SelectTest extends TestCase
{
    public function testConstructor()
    {
        $columns = ['id', 'person' => 'name'];
        $table = 'users';
        $table_alias = 'u';

        $select = new Select($columns, $table, $table_alias);

        $this->assertEquals('SELECT id,name AS `person` FROM `users`', (string)$select);
    }

    public function testStatementWithTable()
    {
        $columns = ['id', 'name'];
        $table = 'users';
        $select = new Select($columns, $table);

        $expected = 'SELECT id,name FROM `users`';
        $this->assertEquals($expected, $select->statement());
    }

    public function testTableLabel()
    {
        $columns = ['id', 'name'];
        $table = 'users';
        $table_alias = 'u';

        $select = new Select($columns, $table, $table_alias);

        $this->assertEquals($table_alias, $select->tableLabel());
        $this->assertEquals($table_alias, $select->tableLabel(null));
        $this->assertEquals('forced_value', $select->tableLabel('forced_value'));
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
}
