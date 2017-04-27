<?php
namespace frictionlessdata\tableschema\Fields;

class BaseField
{
    public function __construct($descriptor)
    {
        $this->descriptor = $descriptor;
    }

    public function name()
    {
        return $this->descriptor->name;
    }

    public function castValue($val)
    {
        return $val;
    }

    protected $descriptor;
}