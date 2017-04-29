<?php
namespace frictionlessdata\tableschema\Fields;

class NumberField extends BaseField
{
    /**
     * @param mixed $val
     * @return float
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    public function validateValue($val)
    {
        if (!is_numeric($val)) {
            throw $this->getValidationException("value must be numeric", $val);
        } else {
            return (float)$val;
        }
    }

    public static function type()
    {
        return "number";
    }
}