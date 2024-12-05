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

    public function testStatement()
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $insert = new Insert('test_table', $data);

        $expectedStatement = 'INSERT INTO `test_table` (`field1`, `field2`) VALUES (:test_table_field1, :test_table_field2)';
        $this->assertEquals($expectedStatement, $insert->statement());
    }
}
