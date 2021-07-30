<?php

namespace HexMakina\Crudites\Interfaces;

interface TableColumnInterface
{
  public function name() : string;

  public function type();
  // public function length($setter=null) : int;

  public function is_primary() : bool;
  public function is_auto_incremented() : bool;
  public function is_nullable() : bool;

  public function default();

  public function foreign_table_name();
}
