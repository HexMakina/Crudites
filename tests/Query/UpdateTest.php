<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Query\Update;
use HexMakina\Crudites\Grammar\Clause\Set;
use HexMakina\Crudites\Grammar\Clause\Where;


class UpdateTest extends TestCase
{
    public function testConstructorThrowsExceptionOnEmptyAlterations()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EMPTY_ALTERATIONS_OR_CONDITIONS');
        
        new Update('test_table', [], ['id' => 1]);
    }

    public function testConstructorThrowsExceptionOnEmptyConditions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EMPTY_ALTERATIONS_OR_CONDITIONS');
        
        new Update('test_table', ['name' => 'test'], []);
    }

    public function testStatement()
    {
        $update = new Update('test_table', ['name' => 'test'], ['id' => 1]);
        $expectedStatement = "UPDATE `test_table` SET `name` = :set_name WHERE `test_table`.`id` = :andFields_test_table_id;";
        
        $this->assertEquals($expectedStatement, $update->statement());
    }
}