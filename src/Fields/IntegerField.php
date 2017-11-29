<?php

namespace frictionlessdata\tableschema\Fields;

class IntegerField extends BaseField
{
    /**
     * @param mixed $val
     *
     * @return int
     *
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    protected function validateCastValue($val)
    {
        if (isset($this->descriptor()->bareNumber) && $this->descriptor()->bareNumber === false) {
            return mb_ereg_replace('((^\D*)|(\D*$))', '', $val);
        } elseif (!is_numeric($val)) {
            throw $this->getValidationException('value must be numeric', $val);
        } else {
            $intVal = (int) $val;
            if ($intVal != (float) $val) {
                throw $this->getValidationException('value must be an integer', $val);
            } else {
                return $intVal;
            }
        }
    }

    public static function type()
    {
        return 'integer';
    }
}
