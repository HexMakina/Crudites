<?php

use PHPUnit\Framework\TestCase;

use HexMakina\Crudites\Connection;
use HexMakina\Crudites\Result;

class ResultTest extends TestCase
{
    // setup
    private $connection;
    private $result;
    private $result_fetch_num;
    private $result_fetch_ass;

    public function setUp(): void
    {
        // code to execute before each test
        $this->connection = new Connection('mysql:host=localhost;dbname=crudites;charset=utf8', 'crudites', '2ce!fNe8(weVz3k4TN#');
        $this->result = new Result($this->connection->pdo(), 'SELECT id, username, email, created_at FROM users');

        $this->result_fetch_num = [
            [
                0 => "1",
                1 => "john_doe",
                2 => "john@example.com",
                3 => "2024-11-25 21:23:46"
            ],
            [
                0 => "2",
                1 => "alice_smith",
                2 => "alice@example.com",
                3 => "2024-11-25 21:23:46"
            ],
            [
                0 => "3",
                1 => "bob_jones",
                2 => "bob@example.com",
                3 => "2024-11-25 21:23:46"
            ]
        ];

        $this->result_fetch_ass = [
            [
                "id" => "1",
                "username" => "john_doe",
                "email" => "john@example.com",
                "created_at" => "2024-11-25 21:23:46"
            ],
            [
                "id" => "2",
                "username" => "alice_smith",
                "email" => "alice@example.com",
                "created_at" => "2024-11-25 21:23:46"
            ],
            [
                "id" => "3",
                "username" => "bob_jones",
                "email" => "bob@example.com",
                "created_at" => "2024-11-25 21:23:46"
            ]
        ];
    }

    public function tearDown(): void
    {
        // code to execute after each test
        $this->connection = null;
        $this->result = null;
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Result::class, $this->result);
    }

    public function testRan()
    {
        $this->assertTrue($this->result->ran());
    }

    public function testRetWithoutParamIsAssoc()
    {
        $this->assertEquals($this->result->ret(), $this->result_fetch_ass);
    }

    public function testRetWithParamFetchNum()
    {
        $this->assertEquals($this->result->ret(\PDO::FETCH_NUM), $this->result_fetch_num);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->result->count());
    }

    public function testCallLastInsertId()
    {
        $this->assertEquals(0, $this->result->lastInsertId());
    }

    public function testErrorInfo()
    {
        $this->assertIsArray($this->result->errorInfo());
    }
}
