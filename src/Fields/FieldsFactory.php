<?php
namespace frictionlessdata\tableschema\Fields;

class FieldsFactory
{
    /**
     * @param object $descriptor
     * @return BaseField
     * @throws \Exception
     */
    public static function field($descriptor)
    {
        switch ($descriptor->type) {
            case "integer": return new IntegerField($descriptor);
            case "string": return new StringField($descriptor);
            default:
                throw new \Exception("Unknown field type: ".$descriptor->type);
        }
    }
}