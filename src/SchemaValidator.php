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

    protected function _validateFields()
    {
        if (!array_key_exists("fields", $this->descriptor)) {
            $this->_addError(SchemaValidationError::MISSING_FIELDS_ATTRIBUTE);
        } else {
            $i = 0;
            $fields = [];
            foreach ($this->descriptor["fields"] as $field) {
                $i++;
                if (is_array($field)) {
                    if (array_key_exists("name", $field)) {
                        $fields[$field["name"]] = $field;
                    } else {
                        $this->_addError(SchemaValidationError::FIELD_MISSING_NAME_ATTRIBUTE, $i);
                    }
                } else {
                    $this->_addError(SchemaValidationError::FIELD_MUST_BE_AN_ARRAY, $i);
                }
            }
            if (array_key_exists("primaryKey", $this->descriptor)) {
                if (!is_array($this->descriptor["primaryKey"])) {
                    $this->_addError(SchemaValidationError::PRIMARY_KEY_ATTRIBUTE_MUST_BE_AN_ARRAY);
                } else {
                    foreach ($this->descriptor["primaryKey"] as $key) {
                        if (!array_key_exists($key, $fields)) {
                            $this->_addError(SchemaValidationError::ALL_PRIMARY_KEYS_MUST_RELATE_TO_FIELDS, $key);
                        }
                    }
                }
            }
        }
    }

    public function get_validation_errors()
    {
        $this->errors = [];
        $this->_validateFields();
        return $this->errors;
    }

    public static function validate($descriptor)
    {
        $validator = new self($descriptor);
        return $validator->get_validation_errors();
    }

}
