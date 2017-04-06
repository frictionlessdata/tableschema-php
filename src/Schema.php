<?php namespace frictionlessdata\tableschema;


/**
 *  Table Schema schema representation.
 *
 *  Loads and validates a Table Schema descriptor from a descirptor / path to file / url containing the descriptor
 */
class Schema{

    public $descriptor;

    public function __construct($descriptor)
    {
        $descriptor = Utils::load_json_resource($descriptor);

        $validation_errors = self::_validate($descriptor);
        if (count(self::_validate($descriptor)) == 0) {
            $this->descriptor = $descriptor;
        } else {
            throw new \Exception("descriptor failed validation: ".implode(", ", $validation_errors));
        };
    }

    public static function validate($descriptor)
    {
        try {
            $descriptor = Utils::load_json_resource($descriptor);
            return self::_validate($descriptor);
        } catch (\Exception $e) {
            return ["Failed to load resource: ".$e->getMessage()];
        }
    }

    protected static function _validate($descriptor)
    {
        $errors = [];
        if (!array_key_exists("fields", $descriptor)) {
            $errors[] = "Failed schema validation";
        } else {
            $i = 0;
            $fields = Utils::array_map_with_keys(function($field) use (&$errors, &$i) {
                $i++;
                if (is_array($field)) {
                    if (array_key_exists("name", $field)) {
                        return [$field["name"], $field];
                    } else {
                        $errors[] = "field {$i} is missing name attribute";
                        return null;
                    }
                } else {
                    $errors[] = "field {$i} is not an array";
                    return null;
                }
            }, $descriptor["fields"]);
            if (array_key_exists("primaryKey", $descriptor)) {
                if (!is_array($descriptor["primaryKey"])) {
                    $errors[] = "primaryKey must be an array";
                } else {
                    foreach ($descriptor["primaryKey"] as $key) {
                        if (!array_key_exists($key, $fields)) {
                            $errors[] = "primaryKey {$key} must relate to a field";
                        }
                    }
                }
            }
        }
        return $errors;
    }

}
