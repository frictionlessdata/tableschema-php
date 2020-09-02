<?php

namespace frictionlessdata\tableschema;

use frictionlessdata\tableschema\Fields\FieldsFactory;

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
    public function __construct($descriptor = null)
    {
        if (is_null($descriptor)) {
            $this->descriptor = (object) ['fields' => []];
        } else {
            if (Utils::isJsonString($descriptor)) {
                // it's a json encoded string
                try {
                    $this->descriptor = json_decode($descriptor);
                } catch (\Exception $e) {
                    throw new Exceptions\SchemaLoadException($descriptor, null, $e->getMessage());
                }
                if (!$this->descriptor) {
                    throw new Exceptions\SchemaLoadException($descriptor, null, 'invalid json');
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
            if (!is_object($this->descriptor) && !is_array($this->descriptor)) {
                throw new Exceptions\SchemaLoadException($descriptor, null, 'descriptor must be an object or array');
            }
            $this->descriptor = json_decode(json_encode($this->descriptor));
            $validationErrors = SchemaValidator::validate($this->descriptor());
            if (count($validationErrors) > 0) {
                throw new Exceptions\SchemaValidationFailedException($validationErrors);
            }
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

    public static function infer($dataSource, $csvDialect = null, $limit = null)
    {
        $table = new Table($dataSource, null, $csvDialect);

        return $table->schema($limit ? $limit : 100);
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

    public function field($name, $field = null)
    {
        $fields = $this->fields();
        if (!is_null($field)) {
            $fields[$name] = $field;

            return $this->fields($fields);
        } elseif (array_key_exists($name, $fields)) {
            return $fields[$name];
        } else {
            throw new \Exception("unknown field name: {$name}");
        }
    }

    public function removeField($name)
    {
        $fields = $this->fields();
        unset($fields[$name]);

        return $this->fields($fields);
    }

    /**
     * @return Fields\BaseField[]|Schema array of field name => field object or the schema in case of editing
     */
    public function fields($newFields = null)
    {
        if (is_null($newFields)) {
            if (empty($this->fieldsCache)) {
                foreach ($this->descriptor()->fields as $fieldDescriptor) {
                    if ((is_object($fieldDescriptor) && ! isset($fieldDescriptor->type))
                        || (is_array($fieldDescriptor) && !array_key_exists('type', $fieldDescriptor))
                    ) {
                        $field = new $this->DEFAULT_FIELD_CLASS($fieldDescriptor);
                    } else {
                        $field = Fields\FieldsFactory::field($fieldDescriptor);
                    }
                    $this->fieldsCache[$field->name()] = $field;
                }
            }

            return $this->fieldsCache;
        } else {
            $this->descriptor()->fields = [];
            $this->fieldsCache = [];
            foreach ($newFields as $name => $field) {
                $field = FieldsFactory::field($field, $name);
                $this->fieldsCache[$name] = $field;
                $this->descriptor()->fields[] = $field->descriptor();
            }

            return $this->revalidate();
        }
    }

    public function missingValues($missingValues = null)
    {
        if (is_null($missingValues)) {
            return isset($this->descriptor()->missingValues) ? $this->descriptor()->missingValues : [''];
        } else {
            $this->descriptor()->missingValues = $missingValues;

            return $this->revalidate();
        }
    }

    public function primaryKey($primaryKey = null)
    {
        if (is_null($primaryKey)) {
            $primaryKey = isset($this->descriptor()->primaryKey) ? $this->descriptor()->primaryKey : [];

            return is_array($primaryKey) ? $primaryKey : [$primaryKey];
        } else {
            $this->descriptor()->primaryKey = $primaryKey;

            return $this->revalidate();
        }
    }

    public function foreignKeys($foreignKeys = null)
    {
        if (is_null($foreignKeys)) {
            $foreignKeys = isset($this->descriptor()->foreignKeys) ? $this->descriptor()->foreignKeys : [];
            foreach ($foreignKeys as &$foreignKey) {
                if (!is_array($foreignKey->fields)) {
                    $foreignKey->fields = [$foreignKey->fields];
                }
                if (!is_array($foreignKey->reference->fields)) {
                    $foreignKey->reference->fields = [$foreignKey->reference->fields];
                }
            }

            return $foreignKeys;
        } else {
            $this->descriptor()->foreignKeys = $foreignKeys;

            return $this->revalidate();
        }
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

    public function revalidate()
    {
        $validationErrors = SchemaValidator::validate($this->descriptor());
        if (count($validationErrors) > 0) {
            throw new Exceptions\SchemaValidationFailedException($validationErrors);
        } else {
            return $this;
        }
    }

    protected $descriptor;
    protected $fieldsCache = [];
}
