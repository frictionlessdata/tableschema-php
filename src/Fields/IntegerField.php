<?php
namespace frictionlessdata\tableschema\Fields;

class IntegerField extends BaseField
{
    public function castValue($val)
    {
        return (integer)$val;
    }
}