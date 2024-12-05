<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\Set;
use HexMakina\Crudites\Grammar\Predicate;

class SetTest extends TestCase
{
    public function testConstructor()
    {
        $alterations = ['field1' => 'value1'];
        $setClause = new Set($alterations);
        $this->assertEquals('SET `field1` = :set_field1', (string)$setClause);
        $this->assertEquals(['set_field1' => 'value1'], $setClause->bindings());

        $alterations = ['field1' => 'value1', 'field2' => 'value2'];
        $setClause = new Set($alterations);
        $this->assertEquals('SET `field1` = :set_field1,`field2` = :set_field2', (string)$setClause);
        $this->assertEquals(['set_field1' => 'value1','set_field2' => 'value2'], $setClause->bindings());
        
    }

    public function testName()
    {
        $alterations = ['field1' => 'value1'];
        $setClause = new Set($alterations);

        $this->assertEquals(Set::SET, $setClause->name());
    }
}