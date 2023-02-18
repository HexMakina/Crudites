<?php

namespace HexMakina\Crudites;

use HexMakina\Crudites\Queries\BaseQuery;

/**
 * The CruditesException class is a custom exception class that extends the built-in \Exception class in PHP. 
 * The purpose of this class is to provide a more descriptive and meaningful error message when an exception is thrown in the CRUD operations. 
 * It takes a message, an optional error code, and an optional previous exception as its parameters. 
 * It also provides a method called fromQuery() which extracts error information from a BaseQuery object 
 * and sets the error message to a more readable and understandable format. 
 * 
 * This is useful when dealing with database errors that may not be very clear or descriptive on their own.
 */

class CruditesException extends \Exception
{
    /**
     * Constructor for the CruditesException class
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Exception|null $previous The previous exception if applicable
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct('CRUDITES_ERR_' . $message, $code, $previous);
    }

    /**
     * Creates a new instance of CruditesException from a BaseQuery
     *
     * @param BaseQuery $baseQuery The BaseQuery instance to create the exception from
     * @return CruditesException The new instance of CruditesException
     */
    public function fromQuery(BaseQuery $baseQuery): self
    {
        list($state, $code, $message) = $baseQuery->errorInfo();
        $this->message = $this->transcript($state, $code, $message);
        return $this;
    }

    /**
     * Transcribes a database error code into a more readable message
     *
     * @param int $state The error state
     * @param int $code The error code
     * @param string $message The error message
     * @return string The transcribed error message
     */
    private function transcript($state, $code, $message): string
    {
        $ret = '';

        switch ($code) {
            case 1062:
                if (preg_match("#for key '[a-z]+\.(.+)'$#", $message, $m) !== 1) {
                    preg_match("#for key '(.+)'$#", $message, $m);
                }

                $ret = $m[1];
                break;

            case 1264:
                preg_match("#for column '(.+)'#", $message, $m);
                $ret = $m[1];
                break;

            case 1451:
                preg_match("#CONSTRAINT `(.+)` FOREIGN#", $message, $m);
                $ret = $m[1];
                break;

            case 1146:
                $ret = "Table doesn't exist";
                break;

            default:
                $ret = 'FUBAR #' . $state . '-' . $code . '-' . $message;
                break;
        }

        return $ret;
    }
}
