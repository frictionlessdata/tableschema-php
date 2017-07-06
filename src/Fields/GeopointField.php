<?php

namespace frictionlessdata\tableschema\Fields;

class GeopointField extends BaseField
{
    protected function validateCastValue($val)
    {
        if (in_array($this->format(), ["array", "object"]) && is_string($val)) {
            try {
                $val = json_decode($val);
            } catch (\Exception $e) {
                throw $this->getValidationException($e->getMessage(), $val);
            }
        };
        switch ($this->format()) {
            case "default":
                if (!is_string($val)) {
                    throw $this->getValidationException("value must be a string", $val);
                } else {
                    return $this->getNativeGeopoint(explode(",", $val));
                }
            case "array":
                if (!is_array($val)) {
                    throw $this->getValidationException("value must be an array", $val);
                } else {
                    return $this->getNativeGeopoint($val);
                }
            case "object":
                if (!is_object($val)) {
                    throw $this->getValidationException("value must be an object", $val);
                } elseif (!isset($val->lat) || !isset($val->lon)) {
                    throw $this->getValidationException("object must contain lon and lat attributes", $val);
                } else {
                    return $this->getNativeGeopoint([$val->lon, $val->lat]);
                }
            default:
                throw $this->getValidationException("invalid format", $val);
        }
    }

    public static function type()
    {
        return 'geopoint';
    }

    protected function getNativeGeopoint($arr) {
        if (count($arr) != 2) {
            throw $this->getValidationException("lon,lat array must contain only lon,lat", json_encode($arr));
        } else {
            list($lon, $lat) = $arr;
            $lon = (int)$lon;
            $lat = (int)$lat;
            if (
                $lon > 180 || $lon < -180
                || $lat > 90 or $lat < -90
            ) {
                throw $this->getValidationException("invalid lon,lat values", json_encode($arr));
            } else {
                return [$lon, $lat];
            }
        }
    }
}
