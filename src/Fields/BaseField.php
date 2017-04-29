<?php
namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\SchemaValidationError;

abstract class BaseField
{
    public function __construct($descriptor=null)
    {
        $this->descriptor = empty($descriptor) ? (object)[] : $descriptor;
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
     * try to create a field object based on the descriptor
     * by default uses the type attribute
     * return the created field object or false if the descriptor does not match this field
     * @param object $descriptor
     * @return bool|BaseField
     */
    public static function inferDescriptor($descriptor)
    {
        if ($descriptor->type == static::type()) {
            return new static($descriptor);
        } else {
            return false;
        }
    }

    /**
     * try to create a new field object based on the given value
     * @param mixed $val
     * @param null|object $descriptor
     * @param bool @lenient
     * @return bool|BaseField
     */
    public static function infer($val, $descriptor=null, $lenient=false)
    {
        $field = new static($descriptor);
        try {
            $field->validateValue($val);
        } catch (FieldValidationException $e) {
            return false;
        }
        $field->inferProperties($val, $lenient);
        return $field;
    }

    public function inferProperties($val, $lenient)
    {
        // should be implemented by extending classes
        // allows adding / modfiying descriptor properties based on the given value
        $this->descriptor->type = $this->type();
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

    /**
     * get a unique identifier for this field
     * used in the inferring process
     * this is usually the type, but can be modified to support more advanced inferring process
     * @param bool @lenient
     * @return string
     */
    public function getInferIdentifier($lenient=false)
    {
        return $this->type();
    }

    /**
     * should be implemented by extending classes to return the table schema type of this field
     * @return string
     */
    abstract static public function type();

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