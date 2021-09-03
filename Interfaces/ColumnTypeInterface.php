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
}
