<?php

namespace frictionlessdata\tableschema\Fields;

class NumberField extends BaseField
{
    /**
     * @param mixed $val
     *
     * @return float
     *
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    protected function validateCastValue($val)
    {
        if (isset($this->descriptor()->bareNumber) && false === $this->descriptor()->bareNumber) {
            return mb_ereg_replace('((^\D*)|(\D*$))', '', $val);
        }
        $isPercent = false;
        if (is_string($val)) {
            if ('%' == substr($val, -1)) {
                $val = rtrim($val, '%');
                $isPercent = true;
            }
            if (isset($this->descriptor()->groupChar)) {
                $val = str_replace($this->descriptor()->groupChar, '', $val);
            }
            if (isset($this->descriptor()->decimalChar) && '.' != $this->descriptor()->decimalChar) {
                $val = str_replace($this->descriptor()->decimalChar, '.', $val);
            }
        }
        if (!is_numeric($val)) {
            throw $this->getValidationException('value must be numeric', $val);
        } else {
            $val = (float) $val;
            if ($isPercent) {
                $val = $val / 100;
            }

            return $val;
        }
    }

    public static function type()
    {
        return 'number';
    }
}
