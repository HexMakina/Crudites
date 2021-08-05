<?php

namespace HexMakina\Crudites\Interfaces;

interface RelationManyToManyInterface
{
  public function has_models() : bool;
  // public function set_many($linked_models, $join_info);
  // public function set_many_by_ids($linked_ids, $join_info);
  public static function set_many($linked_models, ModelInterface $m);
  public static function set_many_by_ids($linked_ids, ModelInterface $m);

  public static function otm($k=null);
  public static function model_type() : string;
}
