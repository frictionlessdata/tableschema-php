<?php

namespace frictionlessdata\tableschema\Fields;

class AnyField extends BaseField
{
    public static function type()
    {
        return 'any';
    }

    protected function validateCastValue($val)
    {
        return $val;
    }

    protected function isEmptyValue($val)
    {
        return false;
    }
}
