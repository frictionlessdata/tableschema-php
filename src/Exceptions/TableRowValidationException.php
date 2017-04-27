<?php
namespace frictionlessdata\tableschema\Exceptions;

use frictionlessdata\tableschema\SchemaValidationError;

/**
 * error in validation of a data row
 * the array of validation errors is available
 */
class TableRowValidationException extends \Exception
{
    /**
     * @var SchemaValidationError[]
     */
    public $validationErrors;

    /**
     * @param SchemaValidationError[] $validationErrors
     */
    public function __construct($validationErrors)
    {
        $this->validationErrors = $validationErrors;
        parent::__construct(SchemaValidationError::getErrorMessages($validationErrors));
    }
}
