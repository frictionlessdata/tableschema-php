<?php

namespace frictionlessdata\tableschema\Fields;

class StringField extends BaseField
{
    public function inferProperties($val, $lenient = false)
    {
        parent::inferProperties($val, $lenient);
        if (!$lenient) {
            if (is_string($val) && strpos($val, '@') !== false) {
                $this->descriptor->format = 'email';
            }
        }
    }

    /**
     * @param mixed $val
     *
     * @return string
     *
     * @throws \frictionlessdata\tableschema\Exceptions\FieldValidationException;
     */
    public function validateCastValue($val)
    {
        $val = parent::validateCastValue($val);
        try {
            $val = (string)$val;
        } catch (\Exception $e) {
            $val = json_encode($val);
        }
        switch ($this->format()) {
            case 'email':
                if (strpos($val, '@') === false) {
                    throw $this->getValidationException('value is not a valid email', $val);
                }
                break;
            case 'uri':
                if (filter_var($val, FILTER_VALIDATE_URL) === false) {
                    throw $this->getValidationException(null, $val);
                }
                break;
            case 'binary':
                $decoded = base64_decode($val, true);
                if ($decoded === false) {
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
