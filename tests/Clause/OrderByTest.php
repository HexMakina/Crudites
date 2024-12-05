<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\OrderBy;

class OrderByTest extends TestCase
{
    public function testConstruct()
    {
        $selected = 'column1';
        $direction = 'ASC';
        $orderBy = new OrderBy($selected, $direction);

        $this->assertInstanceOf(OrderBy::class, $orderBy);
        $this->assertEquals('ORDER BY column1 ASC', (string)$orderBy);
    }

    public function testAdd()
    {
        $selected1 = 'column1';
        $direction1 = 'ASC';
        $orderBy = new OrderBy($selected1, $direction1);

        $selected2 = 'column2';
        $direction2 = 'DESC';
        $orderBy->add($selected2, $direction2);

        $this->assertEquals('ORDER BY column1 ASC,column2 DESC', (string)$orderBy);
    }

    public function testName()
    {
        $orderBy = new OrderBy('column1', 'ASC');
        $this->assertEquals(OrderBy::ORDER, $orderBy->name());
    }
}