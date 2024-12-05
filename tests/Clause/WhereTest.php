<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\Where;
use HexMakina\Crudites\Grammar\Predicate;

class WhereTest extends TestCase
{
    public function testConstructor()
    {
        $where = new Where('default_table');
        $this->assertInstanceOf(Where::class, $where);
    }

    public function testBindings()
    {
        $where = new Where('default_table');
        $this->assertIsArray($where->bindings());
        $this->assertEmpty($where->bindings());

        $where->andPredicate((new Predicate(['table', 'field'], '='))->withValue(3));
        $this->assertNotEmpty($where->bindings());
        $this->assertArrayHasKey('table_field', $where->bindings());
    }

    public function testToString()
    {
        $where = new Where('default_table');
        $this->assertEquals('', $where->__toString());

        $predicate = new Predicate('1', '=', '1');
        $where->andPredicate($predicate);
        $this->assertEquals('WHERE 1 = 1', $where->__toString());
    }

    public function testAndRaw()
    {
        $where = new Where('default_table');
        $where->andRaw('1=1');
        $this->assertEquals('WHERE 1=1', $where->__toString());
    }

    public function testAndPredicate()
    {
        $where = new Where('default_table');
        $where->andPredicate(new Predicate('1', '=', '1'));

        $this->assertEquals('WHERE 1 = 1', (string)$where);
        $this->assertEquals([], $where->bindings());
    }

    public function testAndIsNull()
    {
        $where = new Where('default_table');
        $where->andIsNull('field');
        $this->assertStringContainsString('IS NULL', $where->__toString());
    }

    public function testAndFields()
    {
        $where = new Where('default_table');
        $where->andFields(['field' => 'value']);
        $this->assertStringContainsString('=', $where->__toString());
    }

    public function testAndIn()
    {
        $where = new Where('default_table');
        $where->andIn('field', ['value1', 'value2']);
        $this->assertStringContainsString('IN', $where->__toString());
    }
}