<?php

namespace frictionlessdata\tableschema\Exceptions;

use frictionlessdata\tableschema\SchemaValidationError;

/**
 * schema validation failed
 * the array of validation errors is available.
 */
class SchemaValidationFailedException extends \Exception
{
    /**
     * @var array
     */
    public $validationErrors;

    /**
     * @param SchemaValidationError[] $validationErrors
     */
    public function __construct($validationErrors)
    {
        parent::__construct('Schema failed validation: '.SchemaValidationError::getErrorMessages($validationErrors));
        $this->validationErrors = $validationErrors;
    }
}
