<?php
namespace frictionlessdata\tableschema\Fields;

class StringField extends BaseField
{
    public function format()
    {
        return isset($this->descriptor()->format) ? $this->descriptor()->format : null;
    }

    /**
     * @param mixed $val
     * @return string
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    public function validateValue($val)
    {
        if ($this->format() == "email" && strpos($val, "@") === false) {
            throw $this->getValidationException("value is not a valid email", $val);
        } else {
            return $val;
        }
    }
}