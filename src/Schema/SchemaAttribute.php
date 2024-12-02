<?php

namespace HexMakina\Crudites\Schema;

use HexMakina\BlackBox\Database\{SchemaInterface, SchemaAttributeInterface};

class SchemaAttribute implements SchemaAttributeInterface
{
    private array $column;

    /** 
     * @param SchemaInterface $schema The schema to which the column belongs.
     * @param string $table The table to which the column belongs.
     * @param string $column The column name.
     * 
     * @throws \InvalidArgumentException If the column does not exist.
     */
    public function __construct(SchemaInterface $schema, string $table, string $column)
    {
        $this->column = $schema->column($table, $column);
    }

    /**
     * @return bool True if the column is nullable, false otherwise.
     */
    public function nullable(): bool
    {
        return !empty($this->column['nullable']);
    }

    /**
     * @return mixed The default value of the column, or null if no default is set.
     */
    public function default()
    {
        return $this->column['default'] ?? null;
    }

    /**
     * @return array<string> The possible values of the column, or an empty array if the column is not an enum.
     */
    public function enums(): array
    {
        $ret = [];

        $rx = '/enum\(\'(.+)\'\)/i';
        $column_type = $this->column['column_type'];

        $m = [];
        if (preg_match($rx, $column_type, $m) === 1) {
            $ret = explode("','", $m[1]);
        }

        return $ret;
    }

    /**
     * @return The SchemaInterface type of the column.
     * 
     * @see https://dev.mysql.com/doc/refman/8.0/en/data-types.html
     */
    public function type(): ?string
    {

        return $this->column['type'];
    }

    /**
     * @return int The length of the column, or -1 if the column is not a string.
     * 
     * @see https://dev.mysql.com/doc/refman/8.0/en/char.html
     */
    public function length(): ?int
    {
        return $this->column['length'] ?? null;
    }

    /**
     * @return int The precision of the column, or -1 if the column is not numeric.
     * 
     * @see https://dev.mysql.com/doc/refman/8.0/en/precision-math-decimal-characteristics.html
     */
    public function precision(): ?int
    {
        return $this->column['precision'] ?? null;
    }

    /**
     * @return int The scale of the column, or nullif the column is not numeric.
     * 
     * @see https://dev.mysql.com/doc/refman/8.0/en/precision-math-decimal-characteristics.html
     */
    public function scale(): ?int
    {
        return $this->column['scale'] ?? null;
    }

    /**
     * @return bool True if the column is auto-incremented, false otherwise.
     * 
     * @see https://dev.mysql.com/doc/refman/8.0/en/example-auto-increment.html
     */
    public function isAuto(): bool
    {
        return !empty($this->column['auto_increment']);
    }

    public function validateValue($value = null): ?string
    {
        if ($value !== null) {
            $error = $this->validateValueWithType($value);
            if (!empty($error)) {
                return $error;
            }
        } else if (!$this->nullable() && $this->default() === null) {
            return 'ERR_REQUIRED_VALUE';
        }

        return null;
    }

    public function validateValueWithType($value = null): ?string
    {
        switch ($this->type()) {
            case SchemaAttributeInterface::TYPE_DATE:
            case SchemaAttributeInterface::TYPE_TIME:
            case SchemaAttributeInterface::TYPE_TIMESTAMP:
            case SchemaAttributeInterface::TYPE_DATETIME:
                if (date_create($value) === false) {
                    return 'ERR_DATETIME_FORMAT';
                }
                break;

            case SchemaAttributeInterface::TYPE_YEAR:
                if (preg_match('#^\d{4}$#', $value) !== 1) {
                    return 'ERR_YEAR_FORMAT';
                }
                break;

            case SchemaAttributeInterface::TYPE_INTEGER:
            case SchemaAttributeInterface::TYPE_FLOAT:
            case SchemaAttributeInterface::TYPE_DECIMAL:
                if (!is_numeric($value)) {
                    return 'ERR_NUMERIC_FORMAT';
                }
                break;

            case SchemaAttributeInterface::TYPE_STRING:
            case SchemaAttributeInterface::TYPE_TEXT:
                if (strlen($value) > $this->length()) {
                    return 'ERR_TEXT_TOO_LONG';
                }
                break;

            case SchemaAttributeInterface::TYPE_ENUM:
                if (!in_array($value, $this->enums())) {
                    return 'ERR_INVALID_ENUM_VALUE';
                }
                break;

            default:
                return null;
        }
    }
}
