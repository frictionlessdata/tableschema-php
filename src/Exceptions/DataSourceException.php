<?php
namespace frictionlessdata\tableschema\Exceptions;

/**
 * this exception should be raised from DataSourceInterface classes
 * it will usually represent some kind of file system error in reading
 * or an error in matching value to a column
 */
class DataSourceException extends \Exception
{
    public function __construct($message, $rowNum=0)
    {
        if (!empty($rowNum)) $message = "row {$rowNum}: {$message}";
        parent::__construct($message);
    }
}
