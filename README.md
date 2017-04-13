# Table Schema

[![Travis](https://travis-ci.org/frictionlessdata/tableschema-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/tableschema-php)
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/tableschema-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/tableschema-php?branch=master)
<!-- 
[![Packagist](https://img.shields.io/packagist/dm/oki/tableschema.svg)](https://packagist.org/packages/oki/tableschema)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
 --> [![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Table Schema](https://specs.frictionlessdata.io/table-schema/) in php.


## Features

### Schema

A model of a schema with helpful methods for working with the schema and supported data.

Schema objects can be constructed using any of the following:
* php object
* string containing json
* string containg value supported by [file_get_contents](http://php.net/manual/en/function.file-get-contents.php)

You can use the Schema::validate static function to load and validate a schema. It returns a list of loading or validation errors encountered.

## Important Notes

- Table schema is in transition to v1 - but many datapackage in the wild are still pre-v1
  - At the moment I am developing this library with support only for v1
  - See [this Gitter discussion](https://gitter.im/frictionlessdata/chat?at=58df75bfad849bcf423e5d80) about this transition


## Getting Started

### Installation

```bash
$ composer require frictionlessdata/tableschema
```

### Usage

```php
use frictionlessdata\tableschema;

// construct schema from json string
$schema = new Schema('{
    "fields": [
        {"name": "id"},
        {"name": "height", "type": "integer"}
    ]
}');

// schema will be parsed and validated against the json schema (under src/schemas/table-schema.json)
// will raise exception in case of validation error

// access in php after validation
$schema->descriptor->fields[0]->name == "id"

// validate a schema from a remote resource and getting list of validation errors back
$validationErrors = Schema::validate("https://raw.githubusercontent.com/frictionlessdata/testsuite-extended/ecf1b2504332852cca1351657279901eca6fdbb5/datasets/synthetic/schema.json");
foreach ($validationErrors as $validationError) {
    print(validationError->getMessage();
};
```


## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
