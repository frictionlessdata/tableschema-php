<?php

namespace frictionlessdata\tableschema\Fields;

class BooleanField extends BaseField
{
    protected function validateCastValue($val)
    {
        if (isset($this->descriptor()->trueValues)) {
            $trueValues = $this->descriptor()->trueValues;
        } else {
            $trueValues = ['true', 'True', 'TRUE', '1'];
        }
        if (isset($this->descriptor()->falseValues)) {
            $falseValues = $this->descriptor()->falseValues;
        } else {
            $falseValues = ['false', 'False', 'FALSE', '0'];
        }
        if (is_bool($val)) {
            return $val;
        } elseif (is_string($val)) {
            if (in_array($val, $trueValues)) {
                return true;
            } elseif (in_array($val, $falseValues)) {
                return false;
            } else {
                throw $this->getValidationException('invalid bool value', $val);
            }
        } else {
            throw $this->getValidationException('value must be a bool or string', $val);
        }
    }

    public static function type()
    {
        return 'boolean';
    }
}
