<?php
namespace frictionlessdata\tableschema;

/**
 * validates a table schema descriptor object
 * returns a list of validation errors
 */
class SchemaValidator
{
    /**
     * @param object $descriptor
     * @return SchemaValidationError[]
     */
    public static function validate($descriptor)
    {
        $validator = new self($descriptor);
        return $validator->getValidationErrors();
    }

    /**
     * @param object $descriptor
     */
    public function __construct($descriptor)
    {
        $this->descriptor = $descriptor;
        $this->errors = [];
    }

    /**
     * @return SchemaValidationError[]
     */
    public function getValidationErrors()
    {
        $this->errors = [];
        $this->validateSchema();
        return $this->errors;
    }

    /**
     * @param integer $code
     * @param mixed $extraDetails
     */
    protected function addError($code, $extraDetails=null)
    {
        $this->errors[] = new SchemaValidationError($code, $extraDetails);
    }

    protected function validateSchema()
    {
        // Validate
        $validator = new \JsonSchema\Validator();
        $descriptor = json_decode(json_encode($this->descriptor));
        $this->applyForeignKeysResourceHack($descriptor);
        $validator->validate(
            $descriptor,
            (object)['$ref' => 'file://' . realpath(dirname(__FILE__)).'/schemas/table-schema.json']
        );
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->addError(
                    SchemaValidationError::SCHEMA_VIOLATION,
                    sprintf("[%s] %s", $error['property'], $error['message'])
                );
            }
        }
    }

    protected function applyForeignKeysResourceHack($descriptor)
    {
        if (isset($descriptor->foreignKeys) && is_array($descriptor->foreignKeys)) {
            foreach ($descriptor->foreignKeys as $foreignKey) {
                if (
                    is_object($foreignKey)
                    && isset($foreignKey->reference) && is_object($foreignKey->reference)
                    && isset($foreignKey->reference->resource) && empty($foreignKey->reference->resource)
                ) {
                    $foreignKey->reference->resource = "self";
                }
            }
        }
    }
}
