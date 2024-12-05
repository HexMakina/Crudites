<?php
use PHPUnit\Framework\TestCase;
use HexMakina\Crudites\Connection;
use HexMakina\Crudites\Schema\Schema;
use HexMakina\Crudites\Schema\SchemaAttribute;
use HexMakina\BlackBox\Database\SchemaAttributeInterface;


class SchemaAttributeTest extends TestCase
{
    private Connection $connection;
    private Schema $schema;
    // setup
    public function setUp(): void
    {
        // code to execute before each test

        $dsn = 'mysql:host=localhost;dbname=crudites;charset=utf8';
        $this->connection = new Connection($dsn, 'root', 'changeme0');
        $this->schema = $this->connection->schema();
    }

    public function testAttributesForNonExistentColumn()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CANNOT FIND COLUMN description IN TABLE products');
        $attributes = $this->schema->attributes('products', 'description');
    }

    public function testAttributesForUserId()
    {
        $attributes = $this->schema->attributes('users', 'id');
        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        $this->assertFalse($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 10);
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals($attributes->type(), SchemaAttributeInterface::TYPE_INTEGER);
        
        $this->assertTrue($attributes->isAuto());
    }

    public function testAttributesForOrderStatus()
    {
        $attributes = $this->schema->attributes('orders', 'status');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertFalse($attributes->isAuto());

        $this->assertEquals($attributes->type(), SchemaAttributeInterface::TYPE_ENUM);
        $this->assertEquals($attributes->default(), 'pending');

        $this->assertEquals($attributes->length(), 9);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEquals($attributes->enums(), ['pending', 'completed', 'cancelled']);
    }

    public function testAttributesForProductPrice()
    {
        $attributes = $this->schema->attributes('products', 'price');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertFalse($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 10);
        $this->assertEquals($attributes->scale(), 2);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals($attributes->type(), SchemaAttributeInterface::TYPE_DECIMAL);
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributesForProductCategory()
    {
        $attributes = $this->schema->attributes('products', 'category');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertFalse($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 100);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_STRING, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableTinyInt()
    {
        $attributes = $this->schema->attributes('data_types_table', 'tinyint');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 3);
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_INTEGER, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableSmallInt()
    {
        $attributes = $this->schema->attributes('data_types_table', 'smallint');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 5);
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_INTEGER, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableMediumInt()
    {
        $attributes = $this->schema->attributes('data_types_table', 'mediumint');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 7);
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_INTEGER, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableInt()
    {
        $attributes = $this->schema->attributes('data_types_table', 'int');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 10);
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_INTEGER, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableBigInt()
    {
        $attributes = $this->schema->attributes('data_types_table', 'bigint');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 19);
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_INTEGER, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableFloat()
    {
        $attributes = $this->schema->attributes('data_types_table', 'float');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 12);
        $this->assertNull($attributes->scale());

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_FLOAT, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableDouble()
    {
        $attributes = $this->schema->attributes('data_types_table', 'double');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 22);
        $this->assertNull($attributes->scale());

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_FLOAT, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableDecimal()
    {
        $attributes = $this->schema->attributes('data_types_table', 'decimal');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertEquals($attributes->precision(), 10);
        $this->assertEquals($attributes->scale(), 2);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_DECIMAL, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableChar()
    {
        $attributes = $this->schema->attributes('data_types_table', 'char');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 10);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_STRING, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableVarchar()
    {
        $attributes = $this->schema->attributes('data_types_table', 'varchar');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 255);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_STRING, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableText()
    {
        $attributes = $this->schema->attributes('data_types_table', 'text');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 65535);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_TEXT, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableTinyText()
    {
        $attributes = $this->schema->attributes('data_types_table', 'tinytext');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 255);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_TEXT, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableMediumText()
    {
        $attributes = $this->schema->attributes('data_types_table', 'mediumtext');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 16777215);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_TEXT, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableLongText()
    {
        $attributes = $this->schema->attributes('data_types_table', 'longtext');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());


        $this->assertEquals($attributes->length(), 4294967295);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_TEXT, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableDate()
    {
        $attributes = $this->schema->attributes('data_types_table', 'date');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_DATE, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableDateTime()
    {
        $attributes = $this->schema->attributes('data_types_table', 'datetime');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_DATETIME, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableTimestamp()
    {
        $attributes = $this->schema->attributes('data_types_table', 'timestamp');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_TIMESTAMP, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableTime()
    {
        $attributes = $this->schema->attributes('data_types_table', 'time');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_TIME, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableYear()
    {
        $attributes = $this->schema->attributes('data_types_table', 'year');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertNull($attributes->length());

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEmpty($attributes->enums());
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_YEAR, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

    public function testAttributeForDataTypeTableEnum()
    {
        $attributes = $this->schema->attributes('data_types_table', 'enum_example');

        $this->assertInstanceOf(SchemaAttribute::class, $attributes);
        
        $this->assertTrue($attributes->nullable());
        $this->assertNull($attributes->default());

        $this->assertEquals($attributes->length(), 6);

        $this->assertNull($attributes->precision());
        $this->assertEquals($attributes->scale(), 0);

        $this->assertIsArray($attributes->enums());
        $this->assertEquals($attributes->enums(), ['value1', 'value2', 'value3']);
        
        $this->assertEquals(SchemaAttributeInterface::TYPE_ENUM, $attributes->type());
        
        $this->assertFalse($attributes->isAuto());
    }

}
