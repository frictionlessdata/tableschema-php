<?php

namespace frictionlessdata\tableschema\Fields;

class GeopointField extends BaseField
{
    protected function validateCastValue($val)
    {
        if (in_array($this->format(), ['array', 'object']) && is_string($val)) {
            try {
                $val = json_decode($val);
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
        }
        switch ($this->format()) {
            case 'default':
                if (!is_string($val)) {
                    throw $this->getValidationException('value must be a string', $val);
                } else {
                    $val = explode(',', $val);
                    if (2 != count($val)) {
                        throw $this->getValidationException('value must be a string with 2 comma-separated elements', $val);
                    } else {
                        return $this->getNativeGeopoint($val);
                    }
                }
                // no break
            case 'array':
                if (!is_array($val) || array_keys($val) != [0, 1]) {
                    throw $this->getValidationException('value must be an array with 2 elements', $val);
                } else {
                    return $this->getNativeGeopoint($val);
                }
                // no break
            case 'object':
                $val = json_decode(json_encode($val), true);
                if (!is_array($val) || !array_key_exists('lat', $val) || !array_key_exists('lon', $val)) {
                    throw $this->getValidationException('object must contain lon and lat attributes', $val);
                } else {
                    return $this->getNativeGeopoint([$val['lon'], $val['lat']]);
                }
                // no break
            default:
                throw $this->getValidationException('invalid format', $val);
        }
    }

    public static function type()
    {
        return 'geopoint';
    }

    protected function getNativeGeopoint($arr)
    {
        list($lon, $lat) = $arr;
        $lon = (int) $lon;
        $lat = (int) $lat;
        if (
            $lon > 180 || $lon < -180
            || $lat > 90 or $lat < -90
        ) {
            throw $this->getValidationException('invalid lon,lat values', json_encode($arr));
        } else {
            return [$lon, $lat];
        }
    }
}
