<?php
/**
 * Simple PDO connection wrapper for Crudites
 *
 * Sets required defaults, ERRMODE_EXPCETION, ATTR_CASE
 * Sets prefered fetch mode: associative array
 *
 * Throws \PDOException when DSN is wrong
 */
namespace HexMakina\Crudites;

class Connection extends \PDO implements Interfaces\ConnectionInterface
{
  private $database_name = null;

	static private $driver_options = [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // mandatory in CRUDITES error handler
				\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
			];

  public function __construct($db_host, $db_port, $db_name, $charset='utf8', $username='', $password='')
  {
    $this->database_name = $db_name;
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$charset";
    $this->validate_dsn($dsn); //throws \PDOException
    parent::__construct($dsn, $username, $password, self::$driver_options);
  }

  public function driver_name()
  {
    return $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
  }

  public function database_name()
  {
    return $this->database_name;
  }

  public function transact() : bool
  {
    return $this->beginTransaction();
  }

  public function commit() : bool
  {
    return parent::commit();
  }

  public function rollback() : bool
  {
    return parent::rollback();
  }


  private function validate_dsn($dsn)
  {
    $matches = null;
		if(preg_match('/^([a-z]+)\:/', $dsn, $matches) !== 1)
      throw new \PDOException('DSN Error: bad format');

    $dsn_driver = $matches[1];
    $available_drivers = \PDO::getAvailableDrivers();
    if(!in_array($dsn_driver, $available_drivers, true))
      throw new \PDOException(sprintf('DSN Error: "%s" was given, "%s" are available', $dsn_driver, implode(', ', \PDO::getAvailableDrivers())));

    return true;
  }
}
