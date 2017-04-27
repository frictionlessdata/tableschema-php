<?php
namespace frictionlessdata\tableschema;

class SchemaValidationError
{
    const LOAD_FAILED=1;
    const SCHEMA_VIOLATION=8;

    /**
     * @param integer $code
     * @param mixed $extraDetails
     */
    public function __construct($code, $extraDetails=null)
    {
        $this->code = $code;
        $this->extraDetails = $extraDetails;
    }

    /**
     * returns a string representation of the error
     * @return string
     * @throws \Exception
     */
    public function getMessage()
    {
        switch ($this->code) {
            case self::LOAD_FAILED:
                return $this->extraDetails;
            case self::SCHEMA_VIOLATION:
                return $this->extraDetails;
            default:
                throw new \Exception("Invalid schema validation code: {$this->code}");
        }
    }

    /**
     * given a list of validation errors, returns a single combined string message
     * @param SchemaValidationError[] $validationErrors
     * @return string
     */
    public static function getErrorMessages($validationErrors)
    {
        return implode(", ", array_map(function($validationError){
            /** @var SchemaValidationError $validationError */
            return $validationError->getMessage();
        }, $validationErrors));
    }
}
