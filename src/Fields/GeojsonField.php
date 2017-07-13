<?php

namespace frictionlessdata\tableschema\Fields;

class GeojsonField extends BaseField
{
    protected function validateCastValue($val)
    {
        if (is_string($val)) {
            try {
                $val = json_decode($val);
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
            if (!$val) {
                throw $this->getValidationException('failed to decode json', $val);
            }
        }
        $val = json_decode(json_encode($val));
        if (!is_object($val)) {
            throw $this->getValidationException('must be an object', $val);
        }
        if ($this->format() == 'default') {
            try {
                // this validates the geojson
                \GeoJson\GeoJson::jsonUnserialize($val);
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
        }

        return $val;
    }

    public static function type()
    {
        return 'geojson';
    }
}
