<?php

namespace frictionlessdata\tableschema\Fields;

/**
 * Class YearField
 * casts to integer
 */
class YearField extends BaseField
{
    public function validateCastValue($val)
    {
        $val = parent::validateCastValue($val);
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

    protected function isEmptyValue($val)
    {
        return (parent::isEmptyValue($val) || trim($val) == "");
    }
}
