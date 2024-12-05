<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\GroupBy;

class GroupByTest extends TestCase
{
    public function testName()
    {
        $selected = 'column1';
        $groupBy = new GroupBy($selected);
        $this->assertEquals(GroupBy::GROUP, $groupBy->name());
    }

    public function testConstructString()
    {
        $selected = 'column1';
        $groupBy = new GroupBy($selected);
        $this->assertEquals('GROUP BY column1', (string)$groupBy);
    }

    public function testConstructArray()
    {
        $selected = ['table', 'column'];
        $groupBy = new GroupBy($selected);
        $this->assertEquals('GROUP BY `table`.`column`', (string)$groupBy);
    }

    public function testAddString()
    {
        $selected = 'column1';
        $groupBy = new GroupBy($selected);
        $groupBy->add('column2');
        $this->assertEquals('GROUP BY column1,column2', (string)$groupBy);
    }

    public function testAddArray()
    {
        $selected = ['table', 'column'];
        $groupBy = new GroupBy($selected);
        $groupBy->add(['table', 'column2']);
        $this->assertEquals('GROUP BY `table`.`column`,`table`.`column2`', (string)$groupBy);
    }

    public function testStringAddArray()
    {
        $selected = 'column';
        $groupBy = new GroupBy($selected);
        $groupBy->add(['table', 'column2']);
        $this->assertEquals('GROUP BY column,`table`.`column2`', (string)$groupBy);
    }
}
