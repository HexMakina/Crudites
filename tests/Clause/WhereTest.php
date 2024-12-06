<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Clause\Where;
use HexMakina\Crudites\Grammar\Predicate;

class WhereTest extends TestCase
{
    public function testConstructor()
    {
        $where = new Where();
        $this->assertInstanceOf(Where::class, $where);
        $this->assertEquals('', $where->__toString());

    }

    public function testAnd()
    {
        $where = new Where();
        $where->and('1 = 1');
        $this->assertEquals('WHERE 1 = 1', (string)$where);
        $this->assertEquals([], $where->bindings());


        $where = new Where();
        $where->and(new Predicate('1', '=', '1'));
        $this->assertEquals('WHERE 1 = 1', (string)$where);
        $this->assertEquals([], $where->bindings());

    }

    public function testAndPredicate()
    {
        $where = new Where();
        $this->assertEquals([], $where->bindings());

        $where->andPredicate((new Predicate(['table', 'field'], '='))->withValue(3));
        $this->assertEquals(['table_field' => 3], $where->bindings());
    }

    public function testAndIsNull()
    {
        $where = new Where();
        $where->andIsNull('expression');
        $this->assertEquals('WHERE expression IS NULL', (string)$where);

        $where = new Where();
        $where->andIsNull(['field']);
        $this->assertEquals('WHERE `field` IS NULL', (string)$where);

        $where = new Where();
        $where->andIsNull(['table', 'field']);
        $this->assertEquals('WHERE `table`.`field` IS NULL', (string)$where);
    }

    public function testAndFields()
    {
        $where = new Where();
        $where->andFields(['field' => 'value']);
        $this->assertEquals('WHERE `field` = :andFields_field', (string)$where);
        $this->assertEquals(['andFields_field' => 'value'], $where->bindings());
    }

    public function testAndIn()
    {
        $where = new Where();
        $where->andIn('field', ['value1', 'value2']);
        $this->assertEquals('WHERE field IN (:andIn_field_0,:andIn_field_1)', (string)$where);
        
    }
}