<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Predicate;

class PredicateTest extends TestCase
{
    public function testConstructorWithStrings()
    {
        $predicate = new Predicate('expression');
        $this->assertEquals('expression', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());

        $predicate = new Predicate('expression', 'IS NULL');
        $this->assertEquals('expression IS NULL', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());

        $predicate = new Predicate('expression', '=', 'other_expression');
        $this->assertEquals('expression = other_expression', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());
    }

    public function testConstructorWithArray()
    {
        $predicate = new Predicate(['column']);
        $this->assertEquals('`column`', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());


        $predicate = new Predicate(['column'], '=', 'expression');
        $this->assertEquals('`column` = expression', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());


        $predicate = new Predicate(['table', 'column'], '=', 'expression');
        $this->assertEquals('`table`.`column` = expression', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());


        $predicate = new Predicate(['column'], '=', ['column_2']);
        $this->assertEquals('`column` = `column_2`', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());


        $predicate = new Predicate(['table', 'column'], '=', ['table_2', 'column_2']);
        $this->assertEquals('`table`.`column` = `table_2`.`column_2`', (string)$predicate);
        $this->assertEquals([], $predicate->bindings());
    }

    public function testWithValue()
    {
        $predicate = new Predicate(['table', 'column'], '=', 'placeholder');
        $predicate->withValue(3);
        $this->assertEquals('`table`.`column` = :placeholder', (string)$predicate);
        $this->assertEquals(['placeholder' => 3], $predicate->bindings());

        $predicate = new Predicate(['table', 'column'], '=', 'placeholder');
        $predicate->withValue(3, 'prefix');
        $this->assertEquals('`table`.`column` = :prefix_placeholder', (string)$predicate);
        $this->assertEquals(['prefix_placeholder' => 3], $predicate->bindings());
        
        $predicate = new Predicate(['table', 'column'], '=');
        $predicate->withValue(3, 'prefix');
        $this->assertEquals('`table`.`column` = :prefix_table_column', (string)$predicate);
        $this->assertEquals(['prefix_table_column' => 3], $predicate->bindings());
    }

    public function testWithValues()
    {
        $predicate = new Predicate('column');
        $predicate->withValues(['value1', 'value2'], 'prefix');

        $this->assertEquals('column IN (:prefix_column_0,:prefix_column_1)', (string)$predicate);
        $this->assertEquals(['prefix_column_0' => 'value1', 'prefix_column_1' => 'value2'], $predicate->bindings());

        $predicate = new Predicate('column', 'IN', 'placeholder');
        $predicate->withValues(['value1', 'value2'], 'prefix');
        $this->assertEquals('column IN (:prefix_placeholder_0,:prefix_placeholder_1)', (string)$predicate);
        $this->assertEquals(['prefix_placeholder_0' => 'value1', 'prefix_placeholder_1' => 'value2'], $predicate->bindings());
    }

    public function testIsNotEmpty()
    {
        $expected = '(expression IS NOT NULL AND expression <> \'\')';

        $predicate = new Predicate('expression');
        $predicate->isNotEmpty();
        $this->assertEquals($expected, (string)$predicate);

        $predicate = new Predicate('expression', '=');
        $predicate->isNotEmpty();
        $this->assertEquals($expected, (string)$predicate);

        $predicate = new Predicate('expression', '=', 'something');
        $predicate->isNotEmpty();
        $this->assertEquals($expected, (string)$predicate);


        $predicate = new Predicate(['column']);
        $predicate->isNotEmpty();
        $this->assertEquals('(`column` IS NOT NULL AND `column` <> \'\')', (string)$predicate);

        $predicate = new Predicate(['table', 'column']);
        $predicate->isNotEmpty();
        $this->assertEquals('(`table`.`column` IS NOT NULL AND `table`.`column` <> \'\')', (string)$predicate);
    }

    public function testIsEmpty()
    {
        $expected = '(expression IS NULL OR expression = \'\')';

        $predicate = new Predicate('expression');
        $predicate->isEmpty();
        $this->assertEquals($expected, (string)$predicate);

        $predicate = new Predicate('expression', '=');
        $predicate->isEmpty();
        $this->assertEquals($expected, (string)$predicate);

        $predicate = new Predicate('expression', '=', 'something');
        $predicate->isEmpty();
        $this->assertEquals($expected, (string)$predicate);
    }
}