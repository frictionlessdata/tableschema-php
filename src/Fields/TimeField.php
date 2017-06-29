<?php

namespace frictionlessdata\tableschema\Fields;

class TimeField extends BaseField
{
    public function validateCastValue($val)
    {
        $val = parent::validateCastValue($val);
        switch ($this->format()) {
            case 'default':
                $time = explode(':', $val);
                if (count($time) != 3) {
                    throw $this->getValidationException(null, $val);
                } else {
                    list($hour, $minute, $second) = $time;
                    $nativeTime = mktime($hour, $minute, $second);
                }
                break;
            case 'any':
                $nativeTime = strtotime($val);
                break;
            default:
                $date = strptime($val, $this->format());
                if ($date === false || $date['unparsed'] != '') {
                    throw $this->getValidationException(null, $val);
                } else {
                    $nativeTime = mktime($date['tm_hour'], $date['tm_min'], $date['tm_sec']);
                }
        }
        if ($nativeTime === false) {
            throw $this->getValidationException(null, $val);
        } else {
            return $nativeTime;
        }
    }

    public static function type()
    {
        return 'time';
    }
}
