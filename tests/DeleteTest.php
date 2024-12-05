<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Query\Delete;
use HexMakina\Crudites\Grammar\Clause\Where;

class DeleteTest extends TestCase
{
    public function testConstructorThrowsExceptionWhenConditionsAreEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DELETE_USED_AS_TRUNCATE');
        
        new Delete('test_table', []);
    }

    public function testConstructorSetsTableAndConditions()
    {
        $table = 'test_table';
        $conditions = ['id' => 1];
        $delete = new Delete($table, $conditions);

        $this->assertEquals($table, $delete->table());
        $this->assertInstanceOf(Where::class, $delete->clause(Where::WHERE));
    }
    /*
    public function testStatement()
    {
        $table = 'test_table';
        $conditions = ['id' => 1];
        $delete = new Delete($table, $conditions);

        $expectedStatement = 'DELETE FROM `test_table` WHERE `id` = :id ';
        $this->assertEquals($expectedStatement, $delete->statement());

        $this->assertEquals(['id' => 1], $delete->bindings());
    }
/*
    public function testBindings()
    {
        $table = 'test_table';
        $conditions = ['id' => 1];
        $delete = new Delete($table, $conditions);

        $expectedBindings = [':id' => 1];
        $this->assertEquals($expectedBindings, $delete->bindings());
    }
        */
}