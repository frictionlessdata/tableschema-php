<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;

class DatetimeField extends BaseField
{
    protected function validateCastValue($val)
    {
        $val = trim($val);
        switch ($this->format()) {
            case 'default':
                if (substr($val, -1) != 'Z') {
                    throw $this->getValidationException('must have trailing Z', $val);
                } else {
                    try {
                        $val = rtrim($val, 'Z');
                        $val = explode('T', $val);
                        list($year, $month, $day) = explode('-', $val[0]);
                        list($hour, $minute, $second) = explode(':', $val[1]);

                        return Carbon::create($year, $month, $day, $hour, $minute, $second, 'UTC');
                    } catch (\Exception $e) {
                        throw $this->getValidationException($e->getMessage(), $val);
                    }
                }
            case 'any':
                try {
                    return new Carbon($val);
                } catch (\Exception $e) {
                    throw $this->getValidationException($e->getMessage(), $val);
                }
            default:
                $date = strptime($val, $this->format());
                if ($date === false || $date['unparsed'] != '') {
                    throw $this->getValidationException("couldn't parse date/time according to given strptime format '{$this->format()}''", $val);
                } else {
                    return Carbon::create(
                        (int) $date['tm_year'] + 1900, (int) $date['tm_mon'] + 1, (int) $date['tm_mday'],
                        (int) $date['tm_hour'], (int) $date['tm_min'], (int) $date['tm_sec']
                    );
                }
        }
    }

    public static function type()
    {
        return 'datetime';
    }
}
