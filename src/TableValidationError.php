<?php
namespace frictionlessdata\tableschema;

class TableValidationError extends SchemaValidationError
{
    const ROW_VALIDATION_FAILED = 21;

    public function getMessage()
    {
        switch ($this->code) {
            case self::ROW_VALIDATION_FAILED:
                return "row {$this->extraDetails["row"]}.{$this->extraDetails["col"]}({$this->extraDetails["val"]}): {$this->extraDetails["error"]}";
            default:
                return parent::getMessage();
        }
    }
}
