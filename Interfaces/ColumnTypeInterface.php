<?php

namespace HexMakina\Crudites\Interfaces;

interface ColumnTypeInterface
{
  const TYPE_BOOLEAN = 'boolean';

  const TYPE_INTEGER = 'integer';
  const TYPE_FLOAT = 'float';
  const TYPE_DECIMAL = 'decimal';

  const TYPE_TEXT = 'text';
  const TYPE_STRING = 'char';

  const TYPE_DATETIME = 'datetime';
  const TYPE_DATE = 'date';
  const TYPE_TIMESTAMP = 'timestamp';
  const TYPE_TIME = 'time';
  const TYPE_YEAR = 'year';

  const TYPE_ENUM = 'enum';

  public function isText(): bool;
  public function isString(): bool;
  public function getLength(): int

  public function isBoolean(): bool;
  public function isInteger(): bool;
  public function isDecimal(): bool;
  public function isNumeric(): bool;

  public function isEnum(): bool;
  public function getEnumValues(): array;

  public function isYear(): bool;
  public function isDate(): bool;
  public function isTime(): bool;
  public function isTimestamp(): bool;
  public function isDatetime(): bool;
  public function isDateOrTime(): bool;

  public function validateValue($field_value);

}
