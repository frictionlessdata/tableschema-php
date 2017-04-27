<?php
namespace frictionlessdata\tableschema\Exceptions;

/**
 * error loading a table schema and converting it to native php object
 */
class SchemaLoadException extends \Exception
{
    /**
     * @param mixed $descriptor
     * @param mixed $descriptorSource
     * @param string $errorMessage
     */
    public function __construct($descriptor, $descriptorSource, $errorMessage)
    {
        if (!empty($descriptor) && empty($descriptorSource)) {
            $message = "error decoding descriptor ".json_encode($descriptor).": {$errorMessage}";
        } elseif (!empty($descriptor) && !empty($descriptorSource)) {
            $message = "error decoding descriptor from source ".json_encode($descriptorSource)
                ." - ".json_encode($descriptor).": {$errorMessage}";
        } elseif (empty($descriptor) && !empty($descriptorSource)) {
            $message = "error loading descriptor from source ".json_encode($descriptorSource)
                .": {$errorMessage}";
        } else {
            $message = "unexpected load error: {$errorMessage}";
        }
        parent::__construct($message);
    }
}