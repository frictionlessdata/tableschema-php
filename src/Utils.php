<?php namespace frictionlessdata\tableschema;


class Utils
{

    public static function load_json_resource($resource)
    {
        $original_resource = $resource;
        if (is_string($resource) && !empty($resource)) {
            try {
                $resource = file_get_contents($resource);
                $get_contents_exception = null;
            } catch (\Exception $e) {
                $get_contents_exception = $e;
            };
            try {
                $resource = json_decode($resource);
            } catch (\Exception $e) {
                $json_decode_exception = $e;
                throw new LoadException("Failed to load resource " . json_encode($resource) . " " . $get_contents_exception . " \n\n " . $json_decode_exception);
            }
        }
        if (is_object($resource)) {
            return $resource;
        } else {
            throw new LoadException("Invalid resource: " . json_encode($original_resource));
        }
    }
}


class LoadException extends \Exception
{

}
