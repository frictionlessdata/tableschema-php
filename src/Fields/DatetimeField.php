<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;
use frictionlessdata\tableschema\Utility\StrptimeFormatTransformer;

class DatetimeField extends BaseField
{
    protected function validateCastValue($val)
    {
        $val = trim($val);
        switch ($this->format()) {
            case 'default':
                if ('Z' != substr($val, -1)) {
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
                // no break
            case 'any':
                try {
                    return new Carbon($val);
                } catch (\Exception $e) {
                    throw $this->getValidationException($e->getMessage(), $val);
                }
            default:
                $date = date_parse_from_format(StrptimeFormatTransformer::transform($this->format()), $val);
                if ($date['error_count'] > 0) {
                    throw $this->getValidationException("couldn't parse date/time according to given strptime format '{$this->format()}''", $val);
                } else {
                    return Carbon::create(
                        (int) $date['year'], (int) $date['month'], (int) $date['day'],
                        (int) $date['hour'], (int) $date['minute'], (int) $date['second']
                    );
                }
        }
    }

    public static function type()
    {
        return 'datetime';
    }
}
