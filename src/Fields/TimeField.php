<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\Carbon;
use frictionlessdata\tableschema\Utility\StrptimeFormatTransformer;

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
                if (3 != count($time)) {
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
                $date = date_parse_from_format(
                    StrptimeFormatTransformer::transform($this->format()),
                    $val
                );

                if ($date['error_count'] > 0) {
                    throw $this->getValidationException(null, $val);
                } else {
                    return $this->getNativeTime($date['hour'], $date['minute'], $date['second']);
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
