<?php

namespace frictionlessdata\tableschema\Fields;

/**
 * Class YearMonthField
 * casts to array [year, month]
 */
class YearMonthField extends BaseField
{
    protected function validateCastValue($val)
    {
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
                    return $this->getNativeYearMonth($year, $month);
                }
            }
        }
    }

    public static function type()
    {
        return 'yearmonth';
    }

    protected function getNativeYearMonth($year, $month)
    {
        return [$year, $month];
    }
}
