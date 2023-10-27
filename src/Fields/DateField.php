<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;
use frictionlessdata\tableschema\Utility\StrptimeFormatTransformer;

class DateField extends BaseField
{
    protected const DEFAULT_FORMAT = '%Y-%m-%d';

    protected function validateCastValue($val)
    {
        if ('any' === $this->format()) {
            try {
                $date = new Carbon($val);
                $date->setTime(0, 0, 0);

                return $date;
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
        } else {
            $format = 'default' === $this->format() ? self::DEFAULT_FORMAT : $this->format();
            $date = date_parse_from_format(StrptimeFormatTransformer::transform($format), $val);

            if ($date['error_count'] > 0) {
                throw $this->getValidationException("couldn't parse date/time according to given strptime format '{$format}''", $val);
            } else {
                return Carbon::create(
                    (int) $date['year'], (int) $date['month'], (int) $date['day'],
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
