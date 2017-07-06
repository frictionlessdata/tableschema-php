<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;

class DateField extends BaseField
{
    protected function validateCastValue($val)
    {
        switch ($this->format()) {
            case "default":
                try {
                    list($year, $month, $day) = explode("-", $val);
                    return Carbon::create($year, $month, $day, 0, 0, 0, "UTC");
                } catch (\Exception $e) {
                    throw $this->getValidationException($e->getMessage(), $val);
                }
            case "any":
                try {
                    $date = new Carbon($val);
                    $date->setTime(0, 0, 0);
                    return $date;
                } catch (\Exception $e) {
                    throw $this->getValidationException($e->getMessage(), $val);
                }
            default:
                $date = strptime($val, $this->format());
                if ($date === false || $date['unparsed'] != '') {
                    throw $this->getValidationException("couldn't parse date/time according to given strptime format '{$this->format()}''", $val);
                } else {
                    return Carbon::create(
                        (int)$date["tm_year"]+1900, (int)$date["tm_mon"]+1, (int)$date["tm_mday"],
                        0, 0, 0
                    );
                }
        }
    }

    public static function type()
    {
        return 'date';
    }
}
