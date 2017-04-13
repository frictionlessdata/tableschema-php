<?php namespace frictionlessdata\tableschema;


/**
 *  Table Schema schema representation.
 *
 *  Loads and validates a Table Schema descriptor from a descirptor / path to file / url containing the descriptor
 */
class Schema {

    public $descriptor;
    public $validationErrors;

    public function __construct($descriptor)
    {
        $descriptor = Utils::load_json_resource($descriptor);
        $this->validationErrors = SchemaValidator::validate($descriptor);
        if (count($this->validationErrors) == 0) {
            $this->descriptor = $descriptor;
        } else {
            throw new SchemaException("descriptor failed validation: ".SchemaValidationError::getErrorMessages($this->validationErrors));
        };
    }

    /**
     * loads and validates the given descriptor (string / path to file / url)
     * returns an array of validation error objects encountered or a Schema object if valid
     * @param $descriptor
     * @return array
     */
    public static function validate($descriptor)
    {
        try {
            $descriptor = Utils::load_json_resource($descriptor);
            return SchemaValidator::validate($descriptor);
        } catch (\Exception $e) {
            return [new SchemaValidationError(SchemaValidationError::LOAD_FAILED, $e->getMessage())];
        }
    }

}


class SchemaException extends \Exception
{

}
