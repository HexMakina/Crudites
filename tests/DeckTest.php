<?php

use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Grammar\Deck;



class DeckTest extends TestCase
{
    public function testConstructWithStringAggregate()
    {
        $deck = new Deck('COUNT(*)');
        $this->assertEquals('COUNT(*)', (string)$deck);
    }

    public function testConstructWithArrayAggregate()
    {
        $deck = new Deck(['table', 'column']);
        $this->assertEquals('`table`.`column`', (string)$deck);
    }

    public function testConstructWithAlias()
    {
        $deck = new Deck('COUNT(*)', 'total');
        $this->assertEquals('COUNT(*) AS `total`', (string)$deck);
    }

    public function testAddAggregate()
    {
        $deck = new Deck('COUNT(*)');
        $deck->add('SUM(column)', 'sum_column');
        $this->assertEquals('COUNT(*),SUM(column) AS `sum_column`', (string)$deck);
    }

    public function testAddRawAggregate()
    {
        $deck = new Deck('COUNT(*)');
        $deck->addRaw('SUM(column) AS `sum_column`');
        $this->assertEquals('COUNT(*),SUM(column) AS `sum_column`', (string)$deck);
    }

    public function testToString()
    {
        $deck = new Deck('COUNT(*)');
        $this->assertEquals('COUNT(*)', (string)$deck);
    }

    public function testEmpty()
    {
        $deck = new Deck('');
        $this->assertTrue($deck->empty());

        $deck = new Deck('COUNT(*)');
        $this->assertFalse($deck->empty());
    }
}