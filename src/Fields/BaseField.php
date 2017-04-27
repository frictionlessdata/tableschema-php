<?php
namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\TableValidationError;

class BaseField
{
    public function __construct($descriptor)
    {
        $this->descriptor = $descriptor;
    }

    public function descriptor()
    {
        return $this->descriptor;
    }

    public function name()
    {
        return $this->descriptor()->name;
    }

    /**
     * @param mixed $val
     * @return mixed
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    public function validateValue($val)
    {
        // extending classes should raise FieldValidationException on any errors here
        // can use getValidationException function to get a simple exception with single validation error message
        // you can also throw an exception with multiple validation errors manually
        // must make sure all validation is done in this function and ensure castValue doesn't raise any errors
        return $val;
    }

    /**
     * @param mixed $val
     * @return mixed
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    public function castValue($val)
    {
        return $this->validateValue($val);
    }

    protected $descriptor;

    protected function getValidationException($errorMsg, $val=null)
    {
        return new FieldValidationException([
            new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                "field" => $this->name(),
                "value" => $val,
                "error" => $errorMsg
            ])
        ]);
    }
}