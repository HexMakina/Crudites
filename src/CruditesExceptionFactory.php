<?php

namespace HexMakina\Crudites;

use HexMakina\BlackBox\Database\QueryInterface;

/**
 * The CruditesExceptionFactory class make CruditesExcepton, a custom exception class that extends the built-in \Exception class in PHP. 
 * The purpose of this class is to provide a more descriptive and meaningful error message when an exception is thrown in the CRUD operations. 
 * It takes a message, an optional error code, and an optional previous exception as its parameters. 
 * It also provides a method called transcript() which extracts error information from a QueryInterface object 
 * and sets the error message to a more readable and understandable format. 
 * 
 * This is useful when dealing with database errors that may not be very clear or descriptive on their own.
 */

class CruditesExceptionFactory
{
    public static function make(QueryInterface $query, \PDOException $exception = null): CruditesException
    {
        $errorInfo = null;

        if (!$query->isSuccess())
            $errorInfo = $query->errorInfo();
        elseif ($exception !== null)
            $errorInfo = $exception->errorInfo;

        if (!is_array($errorInfo))
            return new CruditesException('ERROR_INFO_UNAVAILABLE', 0, $exception);

        list($message, $code) = self::transcript($errorInfo);

        // TODO: losing the parsing work from transcript. IMPROVE
        if (is_array($message))
            $message = array_shift($message);

        return new CruditesException($message, $code, null); // exception could reveal database credentials
    }

    /**
     * Attempts to humanize database errors
     * @param ?array errorInfo, consists of at least the following fields:
     *      0	SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
     *      1	Driver-specific error code.
     *      2	Driver-specific error message.
     *
     * 
     * @return array consists of the following fields:
     *      0   the transcripted error message.
     *      1   the error code
     * 
     */
    private static function transcript(array $errorInfo): array
    {
        list($state, $code, $message) = $errorInfo;
        $functs = [
            // violation: column cannot be null
            1048 => function ($message) {
                preg_match("#Column '(.+)' cannot be null#", $message, $m);
                return ['FIELD_REQUIRED', $m[1]];
            },

            1054 => function ($message) {
                return ['COLUMN_DOES_NOT_EXIST', $message];
            },

            // violation: duplicate key
            1062 => function ($message) {
                if (preg_match("#'([^']+)' for key '([^']+)'#", $message, $m)) {
                    $entry = $m[1];
                    $key = $m[2];
                    return ["DUPLICATE_KEY:$key:$entry"];
                }
                
                if (preg_match("#for key '[a-z]+\.(.+)'$#", $message, $m) !== 1) {
                    preg_match("#for key '(.+)'$#", $message, $m);
                }

                return ["DUPLICATE_KEY:".$m[1]];
            },

            1064 => function ($message) {
                preg_match("#right syntax to use near '(.+)'#", $message, $m);
                return ['SYNTAX_ERROR', $m[1]];
            },

            1146 => function ($message) {
                return ['TABLE_DOES_NOT_EXIST', $message];
            },

            1264 => function ($message) {
                preg_match("#for column '(.+)'#", $message, $m);
                return ['VALUE_OUT_OF_RANGE', $m[1]];
            },

            1364 => function ($message) {
                return ['FIELD_REQUIRED', $message];
            },

            1451 => function ($message) {
                preg_match("#CONSTRAINT `(.+)` FOREIGN#", $message, $m);
                return ['RELATIONAL_INTEGRITY', $m[1]];
            },

        ];
        if (isset($functs[$code]))
            return [call_user_func($functs[$code], $message), $code];

        return ['FUBAR #' . $state . '-' . $code . '-' . $message, $code];
    }
}
