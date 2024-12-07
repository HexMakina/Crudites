<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Query\Insert;

class InsertTest extends TestCase
{
    public function testConstructorWithEmptyData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EMPTY_DATA');

        new Insert('test_table', []);
    }

    public function testConstructorWithValidData()
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $insert = new Insert('test_table', $data);

        $this->assertInstanceOf(Insert::class, $insert);
    }
    public function testValues()
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $insert = new Insert('test_table', $data);

        $insert->values(['field1' => 'value1-2', 'field2' => 'value2-2']);

        $expectedBindings = [
            'test_table_field1_0' => 'value1',
            'test_table_field2_1' => 'value2',
            'test_table_field1_2' => 'value1-2',
            'test_table_field2_3' => 'value2-2'
        ];
        $this->assertEquals($expectedBindings, $insert->bindings());
        $this->assertEquals('INSERT INTO `test_table` (`field1`,`field2`) VALUES (:test_table_field1_0,:test_table_field2_1),(:test_table_field1_2,:test_table_field2_3)', $insert->statement());
    }
    public function testStatement()
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $insert = new Insert('test_table', $data);

        $expectedStatement = 'INSERT INTO `test_table` (`field1`,`field2`) VALUES (:test_table_field1_0,:test_table_field2_1)';
        $this->assertEquals($expectedStatement, $insert->statement());
    }
}
