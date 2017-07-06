<?php

namespace frictionlessdata\tableschema\Fields;

/**
 * Class ObjectField
 * casts to php object.
 */
class ObjectField extends BaseField
{
    protected function validateCastValue($val)
    {
        try {
            if (is_string($val)) {
                $object = json_decode($val);
            } elseif (is_object($val)) {
                $object = json_decode(json_encode($val));
            } else {
                $object = null;
            }
        } catch (\Exception $e) {
            throw $this->getValidationException($e->getMessage(), $val);
        }
        if (is_object($object)) {
            return $object;
        } else {
            throw $this->getValidationException(null, $val);
        }
    }

    public static function type()
    {
        return 'object';
    }
}
