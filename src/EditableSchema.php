<?php
namespace frictionlessdata\tableschema;

class EditableSchema extends Schema
{
    public function __construct($descriptor=null)
    {
        $this->descriptor = empty($descriptor) ? (object)["fields" => []] : $descriptor;
    }

    public function fields($newFields=null)
    {
        if (!is_null($newFields)) {
            $this->fieldsCache = $newFields;
            $this->descriptor()->fields = [];
            foreach ($newFields as $field) {
                $this->descriptor()->fields[] = $field->descriptor();
            }
            return $this->revalidate();
        } else {
            return is_null($this->fieldsCache) ? [] : $this->fieldsCache;
        }
    }

    public function field($name, $field=null)
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

    public function primaryKey($primaryKey=null)
    {
        if (is_null($primaryKey)) {
            return parent::primaryKey();
        } else {
            $this->descriptor()->primaryKey = $primaryKey;
            return $this->revalidate();
        }
    }

    public function foreignKeys($foreignKeys=null)
    {
        if (is_null($foreignKeys)) {
            return parent::foreignKeys();
        } else {
            $this->descriptor()->foreignKeys = $foreignKeys;
            return $this->revalidate();
        }
    }

    public function missingValues($missingValues=null)
    {
        if (is_null($missingValues)) {
            return parent::missingValues();
        } else {
            $this->descriptor()->missingValues = $missingValues;
            return $this->revalidate();
        }
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
}
