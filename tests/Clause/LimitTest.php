<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\Limit;

class LimitTest extends TestCase
{
    public function testLimitWithDefaultOffset()
    {
        $limit = new Limit(10);
        $this->assertEquals('LIMIT 10 OFFSET 0', (string)$limit);
    }

    public function testLimitWithCustomOffset()
    {
        $limit = new Limit(10, 5);
        $this->assertEquals('LIMIT 10 OFFSET 5', (string)$limit);
    }

    public function testLimitName()
    {
        $limit = new Limit(10);
        $this->assertEquals('LIMIT', $limit->name());
    }
}