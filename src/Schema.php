<?php

namespace frictionlessdata\tableschema;

/**
 *  Table Schema representation.
 *  Loads and validates a Table Schema descriptor from a descriptor / path to file / url containing the descriptor.
 */
class Schema
{
    protected $DEFAULT_FIELD_CLASS = '\\frictionlessdata\\tableschema\\Fields\\StringField';

    /**
     * Schema constructor.
     *
     * @param mixed $descriptor
     *
     * @throws Exceptions\SchemaLoadException
     * @throws Exceptions\SchemaValidationFailedException
     */
    public function __construct($descriptor)
    {
        if (Utils::isJsonString($descriptor)) {
            // it's a json encoded string
            try {
                $this->descriptor = json_decode($descriptor);
            } catch (\Exception $e) {
                throw new Exceptions\SchemaLoadException($descriptor, null, $e->getMessage());
            }
        } elseif (is_string($descriptor)) {
            // it's a url or file path
            $descriptorSource = $descriptor;
            try {
                $descriptor = file_get_contents($descriptorSource);
            } catch (\Exception $e) {
                throw new Exceptions\SchemaLoadException(null, $descriptorSource, $e->getMessage());
            }
            try {
                $this->descriptor = json_decode($descriptor);
            } catch (\Exception $e) {
                throw new Exceptions\SchemaLoadException($descriptor, $descriptorSource, $e->getMessage());
            }
        } else {
            $this->descriptor = $descriptor;
        }
        if (!is_object($this->descriptor())) {
            throw new Exceptions\SchemaLoadException($descriptor, null, 'descriptor must be an object');
        }
        $validationErrors = SchemaValidator::validate($this->descriptor());
        if (count($validationErrors) > 0) {
            throw new Exceptions\SchemaValidationFailedException($validationErrors);
        }
    }

    /**
     * loads and validates the given descriptor source (php object / string / path to file / url)
     * returns an array of validation error objects.
     *
     * @param mixed $descriptor
     *
     * @return array
     */
    public static function validate($descriptor)
    {
        try {
            new static($descriptor);

            return [];
        } catch (Exceptions\SchemaLoadException $e) {
            return [
                new SchemaValidationError(SchemaValidationError::LOAD_FAILED, $e->getMessage()),
            ];
        } catch (Exceptions\SchemaValidationFailedException $e) {
            return $e->validationErrors;
        }
    }

    /**
     * @return object
     */
    public function descriptor()
    {
        return $this->descriptor;
    }

    public function fullDescriptor()
    {
        $fullDescriptor = $this->descriptor();
        $fullFieldDescriptors = [];
        foreach ($this->fields() as $field) {
            $fullFieldDescriptors[] = $field->fullDescriptor();
        }
        $fullDescriptor->fields = $fullFieldDescriptors;
        $fullDescriptor->missingValues = $this->missingValues();

        return $fullDescriptor;
    }

    public function field($name)
    {
        $fields = $this->fields();
        if (array_key_exists($name, $fields)) {
            return $fields[$name];
        } else {
            throw new \Exception("unknown field name: {$name}");
        }
    }

    /**
     * @return Fields\BaseField[] array of field name => field object
     */
    public function fields()
    {
        if (empty($this->fieldsCache)) {
            foreach ($this->descriptor()->fields as $fieldDescriptor) {
                if (!array_key_exists('type', $fieldDescriptor)) {
                    $field = new $this->DEFAULT_FIELD_CLASS($fieldDescriptor);
                } else {
                    $field = Fields\FieldsFactory::field($fieldDescriptor);
                }
                $this->fieldsCache[$field->name()] = $field;
            }
        }

        return $this->fieldsCache;
    }

    public function missingValues()
    {
        return isset($this->descriptor()->missingValues) ? $this->descriptor()->missingValues : [''];
    }

    public function primaryKey()
    {
        return isset($this->descriptor()->primaryKey) ? $this->descriptor()->primaryKey : [];
    }

    public function foreignKeys()
    {
        return isset($this->descriptor()->foreignKeys) ? $this->descriptor()->foreignKeys : [];
    }

    /**
     * @param mixed[] $row
     *
     * @return mixed[]
     *
     * @throws Exceptions\FieldValidationException
     */
    public function castRow($row)
    {
        $outRow = [];
        $validationErrors = [];
        foreach ($this->fields() as $fieldName => $field) {
            $value = array_key_exists($fieldName, $row) ? $row[$fieldName] : null;
            if (in_array($value, $this->missingValues())) {
                $value = null;
            }
            try {
                $outRow[$fieldName] = $field->castValue($value);
            } catch (Exceptions\FieldValidationException $e) {
                $validationErrors = array_merge($validationErrors, $e->validationErrors);
            }
        }
        if (count($validationErrors) > 0) {
            throw new Exceptions\FieldValidationException($validationErrors);
        }

        return $outRow;
    }

    /**
     * @param array $row
     *
     * @return SchemaValidationError[]
     */
    public function validateRow($row)
    {
        try {
            $this->castRow($row);

            return [];
        } catch (Exceptions\FieldValidationException $e) {
            return $e->validationErrors;
        }
    }

    public function save($filename)
    {
        file_put_contents($filename, json_encode($this->fullDescriptor()));
    }

    protected $descriptor;
    protected $fieldsCache = null;
}
