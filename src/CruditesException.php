<?php

namespace HexMakina\Crudites;

class CruditesException extends \Exception
{
    /**
     * Constructor for the CruditesException class. 
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Exception|null $previous The previous exception if applicable
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct('CRUDITES_ERR_' . $message, $code, $previous);
    }

}
