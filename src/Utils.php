<?php
namespace frictionlessdata\tableschema;

// TODO: refactor to an independenct package (used by both tableschema and datapackage)
class Utils
{
    public static function isJsonString($json)
    {
        return (
            is_string($json)
            && (strpos(ltrim($json), "{") === 0)
        );
    }
}
