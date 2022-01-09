<?php

namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;

class StringField extends BaseField
{
    public function inferProperties($val, $lenient = false)
    {
        parent::inferProperties($val, $lenient);
        if (!$lenient) {
            if (is_string($val) && false !== strpos($val, '@')) {
                $this->descriptor->format = 'email';
            }
        }
    }

    /**
     * @param mixed $val
     *
     * @return string
     *
     * @throws FieldValidationException;
     */
    protected function validateCastValue($val)
    {
        try {
            $val = (string) $val;
        } catch (\Throwable $e) {
            $val = json_encode($val);
        }
        switch ($this->format()) {
            case 'email':
                if (false === strpos($val, '@')) {
                    throw $this->getValidationException('value is not a valid email', $val);
                }
                break;
            case 'uri':
                if (false === filter_var($val, FILTER_VALIDATE_URL)) {
                    throw $this->getValidationException(null, $val);
                }
                break;
            case 'binary':
                $decoded = base64_decode($val, true);
                if (false === $decoded) {
                    throw $this->getValidationException(null, $val);
                }
                break;
        }

        return $val;
    }

    public static function type()
    {
        return 'string';
    }

    public function getInferIdentifier($lenient = false)
    {
        $inferId = parent::getInferIdentifier();
        $format = $this->format();
        if (!$lenient && !empty($format)) {
            $inferId .= ':'.$this->format();
        }

        return $inferId;
    }

    protected function checkMinimumConstraint($val, $minConstraint)
    {
        throw $this->getValidationException('minimum constraint is not supported for string fields', $val);
    }

    protected function checkMaximumConstraint($val, $maxConstraint)
    {
        throw $this->getValidationException('maximum constraint is not supported for string fields', $val);
    }
}
