<?php

namespace frictionlessdata\tableschema\Exceptions;

use frictionlessdata\tableschema\SchemaValidationError;

class FieldValidationException extends \Exception
{
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
