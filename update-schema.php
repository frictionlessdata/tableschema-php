<?php

function update()
{
    $schemaUrl = 'http://specs.frictionlessdata.io/schemas/table-schema.json';
    $base_filename = realpath(dirname(__FILE__))
        .DIRECTORY_SEPARATOR.'src'
        .DIRECTORY_SEPARATOR.'schemas'
        .DIRECTORY_SEPARATOR;
    $filename = $base_filename.'table-schema.json';
    $old_schema = file_exists($filename) ? file_get_contents($filename) : 'FORCE UPDATE';
    echo "downloading schema from {$schemaUrl}\n";
    $new_schema = file_get_contents($schemaUrl);
    if ($old_schema == $new_schema) {
        echo "no update needed\n";
    } else {
        echo "schema changed - updating local file\n";
        file_put_contents($filename, $new_schema);
        file_put_contents($base_filename.'LAST_UPDATE', date('c'));
    }

    return 0;
}

exit(update());
