<?php

namespace frictionlessdata\tableschema;

/**
 * validates a table schema descriptor object
 * returns a list of validation errors.
 */
class SchemaValidator
{
    /**
     * @param object $descriptor
     *
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
        if (count($this->errors) == 0) {
            $this->validateKeys();
        }

        return $this->errors;
    }

    /**
     * @param int   $code
     * @param mixed $extraDetails
     */
    protected function addError($code, $extraDetails = null)
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
            (object) ['$ref' => 'file://'.realpath(dirname(__FILE__)).'/schemas/table-schema.json']
        );
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->addError(
                    SchemaValidationError::SCHEMA_VIOLATION,
                    sprintf('[%s] %s', $error['property'], $error['message'])
                );
            }
        }
    }

    protected function validateKeys()
    {
        $fieldNames = array_map(function ($field) {
            return $field->name;
        }, $this->descriptor->fields);
        if (isset($this->descriptor->primaryKey)) {
            $primaryKey = is_array($this->descriptor->primaryKey) ? $this->descriptor->primaryKey : [$this->descriptor->primaryKey];
            foreach ($primaryKey as $primaryKeyField) {
                if (!in_array($primaryKeyField, $fieldNames)) {
                    $this->addError(
                        SchemaValidationError::SCHEMA_VIOLATION,
                        "primary key must refer to a field name ({$primaryKeyField})"
                    );
                }
            }
        }
        if (isset($this->descriptor->foreignKeys)) {
            foreach ($this->descriptor->foreignKeys as $foreignKey) {
                $fields = is_array($foreignKey->fields) ? $foreignKey->fields : [$foreignKey->fields];
                foreach ($fields as $field) {
                    if (!in_array($field, $fieldNames)) {
                        $this->addError(
                            SchemaValidationError::SCHEMA_VIOLATION,
                            "foreign key fields must refer to a field name ({$field})"
                        );
                    }
                }
                if ($foreignKey->reference->resource == '') {
                    // empty resource = reference to self
                    foreach ($foreignKey->reference->fields as $field) {
                        if (!in_array($field, $fieldNames)) {
                            $this->addError(
                                SchemaValidationError::SCHEMA_VIOLATION,
                                "foreign key reference to self must refer to a field name ({$field})"
                            );
                        }
                    }
                }
            }
        }
    }

    protected function applyForeignKeysResourceHack($descriptor)
    {
        if (isset($descriptor->foreignKeys) && is_array($descriptor->foreignKeys)) {
            foreach ($descriptor->foreignKeys as $foreignKey) {
                // the resource field of foreign keys has problems validating as standard uri string
                // we just override the validation entirely by placing a valid uri
                if (
                    is_object($foreignKey)
                    && isset($foreignKey->reference) && is_object($foreignKey->reference)
                    && isset($foreignKey->reference->resource) && !empty($foreignKey->reference->resource)
                ) {
                    $foreignKey->reference->resource = 'void://'.$foreignKey->reference->resource;
                }
            }
        }
    }
}
