<?php

namespace frictionlessdata\tableschema\Fields;

use Carbon\CarbonInterval;

class DurationField extends BaseField
{
    protected function validateCastValue($val)
    {
        if (!is_string($val)) {
            throw $this->getValidationException('must be string', $val);
        } else {
            $val = trim($val);
            try {
                // we create a dateInterval first, because it's more restrictive
                return CarbonInterval::instance(new \DateInterval($val));
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
        }
    }

    public static function type()
    {
        return 'duration';
    }

    protected function checkAllowedValues($allowedValues, $val)
    {
        foreach ($allowedValues as $allowedValue) {
            if (
                $val->years == $allowedValue->years
                && $val->months == $allowedValue->months
                && $val->days == $allowedValue->days
                && $val->hours == $allowedValue->hours
                && $val->minutes == $allowedValue->minutes
                && $val->seconds == $allowedValue->seconds
            ) {
                return true;
            }
        }

        return false;
    }
}
