<?php

namespace HexMakina\Crudites\Interfaces;

interface TableColumnInterface
{
  public function name() : string;

  public function type($setter=null);
  public function length($setter=null) : int;

  public function is_primary() : bool;
  public function is_auto_incremented() : bool;
  public function is_nullable() : bool;

  public function is_boolean() : bool;
  public function is_integer() : bool;
  public function is_float() : bool;

  public function is_year() : bool;
  public function is_date_or_time() : bool;

  public function is_enum() : bool;
  public function enum_values() : array;

  public function is_text() : bool;
  public function is_string() : bool;

  public function default();

  public function foreign_table_name();
}
