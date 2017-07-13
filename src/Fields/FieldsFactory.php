<?php

namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\Utils;

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
        '\\frictionlessdata\\tableschema\\Fields\\ObjectField',
        '\\frictionlessdata\\tableschema\\Fields\\GeopointField',
        '\\frictionlessdata\\tableschema\\Fields\\GeojsonField',
        '\\frictionlessdata\\tableschema\\Fields\\DurationField',
        '\\frictionlessdata\\tableschema\\Fields\\DatetimeField',
        '\\frictionlessdata\\tableschema\\Fields\\DateField',
        '\\frictionlessdata\\tableschema\\Fields\\BooleanField',
        '\\frictionlessdata\\tableschema\\Fields\\ArrayField',
        '\\frictionlessdata\\tableschema\\Fields\\AnyField',
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
    public static function field($descriptor, $name = null)
    {
        if (is_a($descriptor, 'frictionlessdata\\tableschema\\Fields\\BaseField')) {
            return $descriptor;
        } else {
            if (Utils::isJsonString($descriptor)) {
                $descriptor = json_decode($descriptor);
            } elseif (is_array($descriptor)) {
                $descriptor = json_decode(json_encode($descriptor));
            }
            if (!isset($descriptor->name) && !is_null($name)) {
                $descriptor->name = $name;
            }
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
