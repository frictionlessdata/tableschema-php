<?php

namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\SchemaValidationError;

class FieldsFactory
{
    /**
     * list of all the available field classes.
     *
     * this list is used when inferring field type from a value
     * infer works by trying to case the value to the field, in the fieldClasses order
     * first field that doesn't raise exception on infer wins
     */
    public static $fieldClasses = [
        '\\frictionlessdata\\tableschema\\Fields\\IntegerField',
        '\\frictionlessdata\\tableschema\\Fields\\NumberField',
        '\\frictionlessdata\\tableschema\\Fields\\StringField',

        // these fields will not be inferred - StringField will catch all values before it reaches these
        '\\frictionlessdata\\tableschema\\Fields\\YearMonthField',
        '\\frictionlessdata\\tableschema\\Fields\\YearField',
        '\\frictionlessdata\\tableschema\\Fields\\TimeField',
    ];

    /**
     * get a new field object in the correct type according to the descriptor.
     *
     * @param object $descriptor
     *
     * @return BaseField
     *
     * @throws \Exception
     */
    public static function field($descriptor)
    {
        foreach (static::$fieldClasses as $fieldClass) {
            /** @var BaseField $fieldClass */
            if ($field = $fieldClass::inferDescriptor($descriptor)) {
                return $field;
            }
        }
        throw new FieldValidationException([
            new SchemaValidationError(
                SchemaValidationError::SCHEMA_VIOLATION,
                'Could not find a valid field for descriptor: '.json_encode($descriptor)),
        ]);
    }

    /**
     * @param $val
     * @param null $descriptor
     *
     * @return mixed
     *
     * @throws FieldValidationException
     */
    public static function infer($val, $descriptor = null, $lenient = false)
    {
        foreach (static::$fieldClasses as $fieldClass) {
            /** @var BaseField $fieldClass */
            if ($field = $fieldClass::infer($val, $descriptor, $lenient)) {
                return $field;
            }
        }
        throw new FieldValidationException([
            new SchemaValidationError(
                SchemaValidationError::SCHEMA_VIOLATION,
                'Could not find a valid field for value: '.json_encode($val)),
        ]);
    }
}
