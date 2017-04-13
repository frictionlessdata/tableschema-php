<?php namespace frictionlessdata\tableschema;


class SchemaValidationError {

    const LOAD_FAILED=1;
    const MISSING_FIELDS_ATTRIBUTE=2;
    const PRIMARY_KEY_ATTRIBUTE_MUST_BE_AN_ARRAY=3;
    const ALL_PRIMARY_KEYS_MUST_RELATE_TO_FIELDS=4;
    const FOREIGN_KEYS_ATTRIBUTE_MUST_BE_AN_ARRAY=5;
    const FIELD_MUST_BE_AN_ARRAY=6;
    const FIELD_MISSING_NAME_ATTRIBUTE=7;

    public function __construct($code, $extraDetails=null)
    {
        $this->code = $code;
        $this->extraDetails = $extraDetails;
    }

    public function getMessage()
    {
        switch ($this->code) {
            case self::LOAD_FAILED:
                return "Failed to load from the given descriptor";
            case self::MISSING_FIELDS_ATTRIBUTE:
                return "Descriptor must contain a 'fields' attribute";
            case self::PRIMARY_KEY_ATTRIBUTE_MUST_BE_AN_ARRAY:
                return "primaryKey attribute must be an array of field names";
            case self::ALL_PRIMARY_KEYS_MUST_RELATE_TO_FIELDS:
                return "all field names in primaryKey attribute must relate to fields in the 'fields' attribute (key={$this->extraDetails})";
            case self::FIELD_MUST_BE_AN_ARRAY:
                return "Every field must be an array (field number {$this->extraDetails})";
            default:
                return "code='{$this->code}', extraDetails='{$this->extraDetails}'";
        }
    }

    public static function getErrorMessages($validationErrors)
    {
        return implode(", ", array_map(function($validation_error){
            return $validation_error->getMessage();
        }, $validationErrors));
    }

}
