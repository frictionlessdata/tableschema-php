<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;

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
            $date = strptime($val, $format);

            if (false === $date || '' != $date['unparsed']) {
                throw $this->getValidationException("couldn't parse date/time according to given strptime format '{$format}''", $val);
            } else {
                return Carbon::create(
                    (int) $date['tm_year'] + 1900, (int) $date['tm_mon'] + 1, (int) $date['tm_mday'],
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
