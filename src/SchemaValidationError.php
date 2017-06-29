<?php

namespace frictionlessdata\tableschema;

class SchemaValidationError
{
    const LOAD_FAILED = 1;
    const SCHEMA_VIOLATION = 8;
    const FIELD_VALIDATION = 21;
    const ROW_FIELD_VALIDATION = 22;
    const ROW_VALIDATION = 23;

    public $code;
    public $extraDetails;

    /**
     * @param int   $code
     * @param mixed $extraDetails
     */
    public function __construct($code, $extraDetails = null)
    {
        $this->code = $code;
        $this->extraDetails = $extraDetails;
    }

    /**
     * returns a string representation of the error.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getMessage()
    {
        switch ($this->code) {
            case self::LOAD_FAILED:
                return $this->extraDetails;
            case self::SCHEMA_VIOLATION:
                return $this->extraDetails;
            case self::FIELD_VALIDATION:
                $field = $this->extraDetails['field'];
                $error = $this->extraDetails['error'];
                $value = $this->extraDetails['value'];

                return "{$field}: {$error} ({$value})";
            case self::ROW_FIELD_VALIDATION:
                $row = $this->extraDetails['row'];
                $field = $this->extraDetails['field'];
                $error = $this->extraDetails['error'];
                $value = $this->extraDetails['value'];

                return "row {$row} {$field}: {$error} ({$value})";
            case self::ROW_VALIDATION:
                $row = $this->extraDetails['row'];
                $error = $this->extraDetails['error'];

                return "row {$row}: {$error}";
            default:
                throw new \Exception("Invalid schema validation code: {$this->code}");
        }
    }

    /**
     * given a list of validation errors, returns a single combined string message.
     *
     * @param SchemaValidationError[] $validationErrors
     *
     * @return string
     */
    public static function getErrorMessages($validationErrors)
    {
        return implode(', ', array_map(function ($validationError) {
            /* @var SchemaValidationError $validationError */
            return $validationError->getMessage();
        }, $validationErrors));
    }
}
