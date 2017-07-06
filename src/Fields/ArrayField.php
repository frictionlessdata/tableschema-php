<?php

namespace frictionlessdata\tableschema\Fields;

class ArrayField extends BaseField
{
    protected function validateCastValue($val)
    {
        if (is_array($val)) {
            return $val;
        } elseif (is_string($val)) {
            try {
                $val = json_decode($val);
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
            if (!is_array($val)) {
                throw $this->getValidationException("json string must decode to array", $val);
            } else {
                return $val;
            }
        } else {
            throw $this->getValidationException("must be array or string", $val);
        }
    }

    public static function type()
    {
        return 'array';
    }
}
