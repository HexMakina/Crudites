<?php

namespace HexMakina\Crudites\Table;

use HexMakina\Crudites\CruditesException;
use HexMakina\BlackBox\Database\ColumnTypeInterface;

class ColumnType implements ColumnTypeInterface
{
    /** @var array<string,string> */
    private static $types_rx = [
    self::TYPE_BOOLEAN => 'tinyint\(1\)|boolean|bit', // is_boolean MUST be tested before is_integer

    self::TYPE_INTEGER => 'int\([\d]+\)|int unsigned|int',
    self::TYPE_FLOAT => 'float|double',
    self::TYPE_DECIMAL => 'decimal\([\d]+,[\d]+\)', // untested rx

    self::TYPE_ENUM => 'enum\(\'(.+)\'\)',

    self::TYPE_YEAR => '^year',
    self::TYPE_DATE => '^date$',
    self::TYPE_DATETIME => '^datetime$',
    self::TYPE_TIMESTAMP => '^timestamp$',
    self::TYPE_TIME => '^time$',

    self::TYPE_TEXT => '.*text',
    self::TYPE_STRING => 'char\((\d+)\)$'
    ];

    private string $name;

    private ?array $enum_values = null;

    private ?int $length = null;


    public function __construct($specs_type)
    {
        foreach (self::$types_rx as $type => $rx) {
            if (preg_match(sprintf('/%s/i', $rx), $specs_type, $m) === 1) {
                $this->name = $type;

                if ($this->isEnum()) {
                    $this->enum_values = explode("','", $m[1]);
                } elseif (preg_match('#([\d]+)#', $specs_type, $m) === 1) {
                    $this->length = (int)$m[0];
                }

                break;
            }
        }

        if (is_null($this->name)) {
            throw new CruditesException('FIELD_TYPE_UNKNOWN');
        }
    }

    public function isText(): bool
    {
        return $this->name === self::TYPE_TEXT;
    }

    public function isString(): bool
    {
        return $this->name === self::TYPE_STRING;
    }

    public function isBoolean(): bool
    {
        return $this->name === self::TYPE_BOOLEAN;
    }

    public function isInteger(): bool
    {
        return $this->name === self::TYPE_INTEGER;
    }

    public function isFloat(): bool
    {
        return $this->name === self::TYPE_FLOAT;
    }

    public function isDecimal(): bool
    {
        return $this->name === self::TYPE_DECIMAL;
    }

    public function isNumeric(): bool
    {
        return in_array($this->name, [self::TYPE_INTEGER, self::TYPE_FLOAT, self::TYPE_DECIMAL]);
    }

    public function isEnum(): bool
    {
        return $this->name === self::TYPE_ENUM;
    }

    public function isYear(): bool
    {
        return $this->name === self::TYPE_YEAR;
    }

    public function isDate(): bool
    {
        return $this->name === self::TYPE_DATE;
    }

    public function isTime(): bool
    {
        return $this->name === self::TYPE_TIME;
    }

    public function isTimestamp(): bool
    {
        return $this->name === self::TYPE_TIMESTAMP;
    }

    public function isDatetime(): bool
    {
        return $this->name === self::TYPE_DATETIME;
    }

    public function isDateOrTime(): bool
    {
        return in_array($this->name, [self::TYPE_DATE, self::TYPE_TIME, self::TYPE_TIMESTAMP, self::TYPE_DATETIME]);
    }



    /**
     * @return mixed[]
     */
    public function getEnumValues(): array
    {
        return $this->enum_values ?? [];
    }

    public function getLength(): int
    {
        return $this->length ?? -1;
    }


    public function validateValue(mixed $field_value)
    {
        $ret = true;

        if ($this->isDateOrTime() && date_create($field_value) === false) {
            $ret = 'ERR_FIELD_FORMAT';
        } elseif ($this->isYear() && preg_match('#^\d{4}$#', $field_value) !== 1) {
            $ret = 'ERR_FIELD_FORMAT';
        } elseif ($this->isNumeric() && !is_numeric($field_value)) {
            $ret = 'ERR_FIELD_FORMAT';
        } elseif ($this->isString() && $this->getLength() < strlen($field_value)) {
            $ret = 'ERR_FIELD_TOO_LONG';
        } elseif ($this->isEnum() && !in_array($field_value, $this->getEnumValues())) {
            $ret = 'ERR_FIELD_VALUE_RESTRICTED_BY_ENUM';
        }

        return $ret;
    }
}
