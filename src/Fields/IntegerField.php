<?php
namespace frictionlessdata\tableschema\Fields;

class IntegerField extends BaseField
{
    /**
     * @param mixed $val
     * @return integer
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    public function validateValue($val)
    {
        if (!is_numeric($val)) {
            throw $this->getValidationException("value must be numeric", $val);
        } else {
            $intVal = (integer)$val;
            if ($intVal != (float)$val) {
                throw $this->getValidationException("value must be an integer", $val);
            } else {
                return $intVal;
            }
        }
    }
}