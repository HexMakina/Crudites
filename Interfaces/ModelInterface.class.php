<?php

namespace HexMakina\Crudites\Interfaces;


interface ModelInterface
{
  public function is_new() : bool;
  public function get_id();

  public function get($prop_name);
  public function set($prop_name, $value);

  public function immortal() : bool;

  public function validate() : array;
  public function before_save() : array;
  public function save($operator_id, TracerInterface $tracer=null);
  public function after_save();

  public function before_destroy(): bool;
  public function destroy($operator_id, TracerInterface $tracer=null) : bool;
  public function after_destroy();

  public static function table_name() : string;
  public static function table() : TableManipulationInterface;
  public static function model_type() : string;

  public static function query_retrieve($filters=[], $options=[]) : SelectInterface;

}
