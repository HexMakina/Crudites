<?php

namespace HexMakina\Crudites\Interfaces;

interface ConnectionInterface
{
  public function __construct($db_host, $db_port, $db_name, $username='', $password='');

  public function driver_name();
  public function database_name() : string;

  public function prepare($sql_statement, $options = []);

  public function transact() : bool;
  public function commit() : bool;
  public function rollback() : bool;

  public function last_inserted_id($name = null);
  public function error_code() : array;
  public function error_info() : array;
}
