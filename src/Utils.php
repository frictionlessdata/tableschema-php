<?php namespace frictionlessdata\tableschema;


class Utils
{
    public static function array_map_with_keys($callback, $array)
    {
        $res = [];
        foreach (array_map($callback, $array) as $tmp) {
            if (!empty($tmp)) {
                $res[$tmp[0]] = $tmp[1];
            }
        }
        return $res;
    }


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
                $resource = json_decode($resource, $assoc = true);
            } catch (\Exception $e) {
                $json_decode_exception = $e;
                throw new \Exception("Failed to load resource " . json_encode($resource) . " " . $get_contents_exception . " \n\n " . $json_decode_exception);
            }
        }
        if (is_array($resource) && !empty($resource)) {
            return $resource;
        } else {
            throw new \Exception("Invalid resource: " . json_encode($original_resource));
        }
    }
}
