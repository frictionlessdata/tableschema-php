<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;

/**
 * Class TimeField
 * casts to array [hour, minute, second].
 */
class TimeField extends BaseField
{
    protected function validateCastValue($val)
    {
        switch ($this->format()) {
            case 'default':
                $time = explode(':', $val);
                if (count($time) != 3) {
                    throw $this->getValidationException(null, $val);
                } else {
                    list($hour, $minute, $second) = $time;

                    return $this->getNativeTime($hour, $minute, $second);
                }
                break;
            case 'any':
                try {
                    $dt = Carbon::parse($val);
                } catch (\Exception $e) {
                    throw $this->getValidationException($e->getMessage(), $val);
                }

                return $this->getNativeTime($dt->hour, $dt->minute, $dt->second);
            default:
                $date = strptime($val, $this->format());
                if ($date === false || $date['unparsed'] != '') {
                    throw $this->getValidationException(null, $val);
                } else {
                    return $this->getNativeTime($date['tm_hour'], $date['tm_min'], $date['tm_sec']);
                }
        }
    }

    public static function type()
    {
        return 'time';
    }

    protected function getNativeTime($hour, $minute, $second)
    {
        $parts = [$hour, $minute, $second];
        foreach ($parts as &$part) {
            $part = (int) $part;
        }

        return $parts;
    }
}
