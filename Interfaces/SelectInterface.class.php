<?php

namespace HexMakina\Crudites\Interfaces;

interface SelectInterface extends QueryInterface
{
  public function table_alias($setter = null); // TODO should only be a setter
  public function table_label($forced_value=null);

  public function columns($setter = null);
  public function select_also($setter);

  public function group_by($clause);
  public function having($condition);
  public function order_by($clause);
  public function limit($number, $offset=null);

  public function ret_obj($c=null);
  public function ret_num();
  public function ret_ass();
  public function ret_col();
  public function ret_par();
  public function ret_key();

}
