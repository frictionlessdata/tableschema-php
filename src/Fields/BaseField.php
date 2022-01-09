<?php

namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\SchemaValidationError;

abstract class BaseField
{
    public function __construct($descriptor = null)
    {
        $this->descriptor = empty($descriptor) ? (object) [] : $descriptor;
    }

    public function descriptor()
    {
        return $this->descriptor;
    }

    public function fullDescriptor()
    {
        $fullDescriptor = $this->descriptor();
        $fullDescriptor->format = $this->format();
        $fullDescriptor->type = $this->type();

        return $fullDescriptor;
    }

    public function name()
    {
        return $this->descriptor()->name;
    }

    public function title()
    {
        return isset($this->descriptor()->title) ? $this->descriptor()->title : null;
    }

    public function description()
    {
        return isset($this->descriptor()->description) ? $this->descriptor()->description : null;
    }

    public function rdfType()
    {
        return isset($this->descriptor()->rdfType) ? $this->descriptor()->rdfType : null;
    }

    public function format()
    {
        return isset($this->descriptor()->format) ? $this->descriptor()->format : 'default';
    }

    public function constraints()
    {
        if (!$this->constraintsDisabled && isset($this->descriptor()->constraints)) {
            return $this->descriptor()->constraints;
        } else {
            return (object) [];
        }
    }

    public function required()
    {
        return isset($this->constraints()->required) && $this->constraints()->required;
    }

    public function unique()
    {
        return isset($this->constraints()->unique) && $this->constraints()->unique;
    }

    public function disableConstraints()
    {
        $this->constraintsDisabled = true;

        return $this;
    }

    public function enum()
    {
        if (isset($this->constraints()->enum) && !empty($this->constraints()->enum)) {
            return $this->constraints()->enum;
        } else {
            return [];
        }
    }

    /**
     * try to create a field object based on the descriptor
     * by default uses the type attribute
     * return the created field object or false if the descriptor does not match this field.
     *
     * @param object $descriptor
     *
     * @return bool|BaseField
     */
    public static function inferDescriptor($descriptor)
    {
        if (isset($descriptor->type) && $descriptor->type == static::type()) {
            return new static($descriptor);
        } else {
            return false;
        }
    }

    /**
     * try to create a new field object based on the given value.
     *
     * @param mixed       $val
     * @param object|null $descriptor
     * @param bool @lenient
     *
     * @return bool|BaseField
     */
    public static function infer($val, $descriptor = null, $lenient = false)
    {
        $field = new static($descriptor);
        try {
            $field->castValue($val);
        } catch (FieldValidationException $e) {
            return false;
        }
        $field->inferProperties($val, $lenient);

        return $field;
    }

    public function inferProperties($val, $lenient)
    {
        // should be implemented by extending classes
        // allows adding / modfiying descriptor properties based on the given value
        $this->descriptor->type = $this->type();
    }

    /**
     * @param mixed $val
     *
     * @return mixed
     *
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    final public function castValue($val)
    {
        if ($this->isEmptyValue($val)) {
            if ($this->required()) {
                throw $this->getValidationException('field is required', $val);
            }

            return null;
        } else {
            $val = $this->validateCastValue($val);
            if (!$this->constraintsDisabled) {
                $validationErrors = $this->checkConstraints($val);
                if (count($validationErrors) > 0) {
                    throw new FieldValidationException($validationErrors);
                }
            }

            return $val;
        }
    }

    public function validateValue($val)
    {
        try {
            $this->castValue($val);

            return [];
        } catch (FieldValidationException $e) {
            return $e->validationErrors;
        }
    }

    /**
     * get a unique identifier for this field
     * used in the inferring process
     * this is usually the type, but can be modified to support more advanced inferring process.
     *
     * @param bool @lenient
     *
     * @return string
     */
    public function getInferIdentifier($lenient = false)
    {
        return $this->type();
    }

    /**
     * should be implemented by extending classes to return the table schema type of this field.
     *
     * @return string
     */
    public static function type()
    {
        throw new \Exception('must be implemented by extending classes');
    }

    protected $descriptor;
    protected $constraintsDisabled = false;

    protected function getValidationException($errorMsg = null, $val = null)
    {
        return new FieldValidationException([
            new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                'field' => isset($this->descriptor()->name) ? $this->name() : 'unknown',
                'value' => $val,
                'error' => is_null($errorMsg) ? 'invalid value' : $errorMsg,
            ]),
        ]);
    }

    protected function isEmptyValue($val)
    {
        return is_null($val);
    }

    /**
     * @param mixed $val
     *
     * @return mixed
     *
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    // extending classes should extend this method
    // value is guaranteed not to be an empty value, that is handled elsewhere
    // should raise FieldValidationException on any validation errors
    // can use getValidationException function to get a simple exception with single validation error message
    // you can also throw an exception with multiple validation errors manually
    abstract protected function validateCastValue($val);

    protected function checkConstraints($val)
    {
        $validationErrors = [];
        $allowedValues = $this->getAllowedValues();
        if (!empty($allowedValues) && !$this->checkAllowedValues($allowedValues, $val)) {
            $validationErrors[] = new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                'field' => $this->name(),
                'value' => $val,
                'error' => 'value not in enum',
            ]);
        }
        $constraints = $this->constraints();
        if (isset($constraints->pattern)) {
            if (!$this->checkPatternConstraint($val, $constraints->pattern)) {
                $validationErrors[] = new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                    'field' => $this->name(),
                    'value' => $val,
                    'error' => 'value does not match pattern',
                ]);
            }
        }
        if (
            isset($constraints->minimum)
            && !$this->checkMinimumConstraint($val, $this->castValueNoConstraints($constraints->minimum))
        ) {
            $validationErrors[] = new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                'field' => $this->name(),
                'value' => $val,
                'error' => 'value is below minimum',
            ]);
        }
        if (
            isset($constraints->maximum)
            && !$this->checkMaximumConstraint($val, $this->castValueNoConstraints($constraints->maximum))
        ) {
            $validationErrors[] = new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                'field' => $this->name(),
                'value' => $val,
                'error' => 'value is above maximum',
            ]);
        }
        if (
            isset($constraints->minLength) && !$this->checkMinLengthConstraint($val, $constraints->minLength)
        ) {
            $validationErrors[] = new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                'field' => $this->name(),
                'value' => $val,
                'error' => 'value is below minimum length',
            ]);
        }
        if (
            isset($constraints->maxLength) && !$this->checkMaxLengthConstraint($val, $constraints->maxLength)
        ) {
            $validationErrors[] = new SchemaValidationError(SchemaValidationError::FIELD_VALIDATION, [
                'field' => $this->name(),
                'value' => $val,
                'error' => 'value is above maximum length',
            ]);
        }

        return $validationErrors;
    }

    protected function checkPatternConstraint($val, $pattern)
    {
        return preg_match('/^'.$pattern.'$/', $val) === 1;
    }

    protected function checkMinimumConstraint($val, $minConstraint)
    {
        return $val >= $minConstraint;
    }

    protected function checkMaximumConstraint($val, $maxConstraint)
    {
        return $val <= $maxConstraint;
    }

    protected function getLengthForConstraint($val)
    {
        if (is_string($val)) {
            return strlen($val);
        } elseif (is_array($val)) {
            return count($val);
        } elseif (is_object($val)) {
            return count((array) $val);
        } else {
            throw $this->getValidationException('invalid value for length constraint', $val);
        }
    }

    protected function checkMinLengthConstraint($val, $minLength)
    {
        return $this->getLengthForConstraint($val) >= $minLength;
    }

    protected function checkMaxLengthConstraint($val, $maxLength)
    {
        return $this->getLengthForConstraint($val) <= $maxLength;
    }

    protected function getAllowedValues()
    {
        $allowedValues = [];
        foreach ($this->enum() as $val) {
            $allowedValues[] = $this->castValueNoConstraints($val);
        }

        return $allowedValues;
    }

    protected function checkAllowedValues($allowedValues, $val)
    {
        return in_array($val, $allowedValues, !is_object($val));
    }

    protected function castValueNoConstraints($val)
    {
        $this->disableConstraints();
        $val = $this->castValue($val);
        $this->constraintsDisabled = false;

        return $val;
    }
}
