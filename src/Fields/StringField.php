<?php
namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;

class StringField extends BaseField
{
    public function format()
    {
        return isset($this->descriptor()->format) ? $this->descriptor()->format : null;
    }

    public function inferProperties($val, $lenient=false)
    {
        parent::inferProperties($val, $lenient);
        if (!$lenient) {
            if (strpos($val, "@") !== false) {
                $this->descriptor->format = "email";
            }
        }
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

    public static function type()
    {
        return "string";
    }

    public function getInferIdentifier($lenient=false)
    {
        $inferId = parent::getInferIdentifier();
        $format = $this->format();
        if (!$lenient && !empty($format)) {
            $inferId .= ":".$this->format();
        };
        return $inferId;
    }
}