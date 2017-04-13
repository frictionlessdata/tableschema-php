<?php namespace frictionlessdata\tableschema;


class SchemaValidator
{

    public function __construct($descriptor)
    {
        $this->descriptor = $descriptor;
        $this->errors = [];
    }

    protected function _addError($code, $extraDetails=null)
    {
        $this->errors[] = new SchemaValidationError($code, $extraDetails);
    }

    protected function _validateSchema()
    {
        // Validate
        $validator = new \JsonSchema\Validator();
        $validator->validate($this->descriptor, (object)['$ref' => 'file://' . realpath(dirname(__FILE__)).'/schemas/table-schema.json']);
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->_addError(SchemaValidationError::SCHEMA_VIOLATION, sprintf("[%s] %s", $error['property'], $error['message']));
            }
        }
    }

    public function get_validation_errors()
    {
        $this->errors = [];
        $this->_validateSchema();
        return $this->errors;
    }

    public static function validate($descriptor)
    {
        $validator = new self($descriptor);
        return $validator->get_validation_errors();
    }

}
