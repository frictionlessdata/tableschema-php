<?php

namespace frictionlessdata\tableschema\Fields;

/**
 * Class YearField
 * casts to integer
 */
class YearField extends BaseField
{
    protected function validateCastValue($val)
    {
        $year = (int) $val;
        if ((float) $val != (float) $year) {
            throw $this->getValidationException(null, $val);
        } else {
            return $year;
        }
    }

    public static function type()
    {
        return 'year';
    }
}
