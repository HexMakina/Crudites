<?php

namespace HexMakina\Crudites\Interfaces;

interface ConnectionInterface
{
  public function __construct($db_host, $db_port, $db_name, $username='', $password='');

  public function driver_name();
  public function database_name();

  public function transact() : bool;
  public function commit() : bool;
  public function rollback() : bool;
}
