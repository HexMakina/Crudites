<?php

namespace HexMakina\Crudites;

use \HexMakina\Crudites\Queries\BaseQuery;

class CruditesException extends \Exception
{
  private $sql_state=null;
  private $sql_code=null;
  private $sql_message=null;

  public function __construct($message, $code=0, $previous = null)
  {
    parent::__construct('CRUDITES_ERR_'.$message, $code, $previous);
  }

  public function fromQuery(BaseQuery $Query)
  {
    list($state, $code, $message) = $Query->error_info();
    $this->message = $this->transcript($state, $code, $message);
    return $this;
  }

  private function transcript($state, $code, $message)
  {
    $ret = '';

    switch($code)
    {
      case 1062:
        preg_match("/for key '(.+)'$/", $message, $m);
        $ret = $m[1];
      break;

      case 1264:
        preg_match("/for column '(.+)'/", $message, $m);
        $ret = $m[1];
      break;

      case 1451:
        preg_match("/CONSTRAINT `(.+)` FOREIGN/", $message, $m);
        $ret = $m[1];
        break;

      case 1146:
        $ret = "Table doesn't exist";
        break;

      default:

        $ret = 'FUBAR #'.$state.'-'.$code;
      break;
    }

    return $ret;
  }


}

?>
