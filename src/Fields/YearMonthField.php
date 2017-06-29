<?php

namespace frictionlessdata\tableschema\Fields;

class YearMonthField extends BaseField
{
    public function validateCastValue($val)
    {
        $val = parent::validateCastValue($val);
        if (!is_array($val)) {
            $val = explode('-', $val);
        }
        if (count($val) != 2) {
            throw $this->getValidationException(null, $val);
        } else {
            list($year, $month) = $val;
            if ($year == '' || $month == '') {
                throw $this->getValidationException(null, $val);
            } else {
                $year = (int) $year;
                $month = (int) $month;
                if ($month < 1 || $month > 12) {
                    throw $this->getValidationException(null, $val);
                } else {
                    return [$year, $month];
                }
            }
        }
    }

    public static function type()
    {
        return 'yearmonth';
    }
}
