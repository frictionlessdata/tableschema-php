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
    public function validateCastValue($val)
    {
        $val = parent::validateCastValue($val);
        if (!is_numeric($val)) {
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

    protected function isEmptyValue($val)
    {
        return !is_numeric($val) && empty($val);
    }
}
