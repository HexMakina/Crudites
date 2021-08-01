<?php

namespace HexMakina\Crudites\Interfaces;

use \HexMakina\Crudites\Queries\BaseQuery;

interface ModelInterface
{
  public function is_new() : bool;

  public function get($prop_name);
  public function set($prop_name, $value);

  public function immortal() : bool;

  public function validate() : array;
  public function before_save() : array;
  public function save($creator_id);
  public function after_save();

  public function before_destroy(): bool;
  public function destroy($destructor_id) : bool;
  public function after_destroy();

  public function traceable() : bool;
  public function track($query_code, int $operator_id) : bool;

  public static function table_name() : string;
  public static function table() : TableManipulationInterface;
  public static function model_type() : string;

  public static function query_retrieve($filters=[], $options=[]) : BaseQuery;

}
