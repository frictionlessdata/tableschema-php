<?php

namespace frictionlessdata\tableschema\Fields;

class YearField extends BaseField
{
    public function validateCastValue($val)
    {
        $val = parent::validateCastValue($val);
        if ($val == "") {
            throw $this->getValidationException(null, $val);
        } else {
            $year = (int)$val;
            if ((float)$val != (float)$year) {
                throw $this->getValidationException(null, $val);
            } else {
                return $year;
            }
        }
    }

    public static function type()
    {
        return 'year';
    }
}
