# Table Schema

[![Travis](https://travis-ci.org/frictionlessdata/tableschema-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/tableschema-php)
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/tableschema-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/tableschema-php?branch=master)
[![Scrutinizer-ci](https://scrutinizer-ci.com/g/OriHoch/tableschema-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/OriHoch/tableschema-php/)
[![Packagist](https://img.shields.io/packagist/dm/frictionlessdata/tableschema.svg)](https://packagist.org/packages/frictionlessdata/tableschema)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
[![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Table Schema](https://specs.frictionlessdata.io/table-schema/) in php.


## Features

### Schema

A model of a schema with helpful methods for working with the schema and supported data.

Schema objects can be constructed using any of the following:
* php object
* string containing json
* string containg value supported by [file_get_contents](http://php.net/manual/en/function.file-get-contents.php)

You can use the Schema::validate static function to load and validate a schema.
It returns a list of loading or validation errors encountered.

### Table

Provides methods for loading any fopen compatible data source and iterating over the data.

* Data is validated according to a given table schema
* Data is converted to native types according to the schema


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
use frictionlessdata\tableschema\Schema;

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
$validationErrors = tableschema\Schema::validate("https://raw.githubusercontent.com/frictionlessdata/testsuite-extended/ecf1b2504332852cca1351657279901eca6fdbb5/datasets/synthetic/schema.json");
foreach ($validationErrors as $validationError) {
    print(validationError->getMessage();
};

// validate and cast a row according to schema
$schema = new Schema('{"fields": ["name": "id", "type": "integer"]}');
$row = $schema->castRow(["id" => "1"]);
// raise exception if row fails validation
// returns row with all native values

// validate a row
$validationErrors = $schema->validateRow(["id" => "foobar"]);
// error that id is not numeric

// iterate over a remote data source conforming to a table schema
$table = new tableschema\Table(
    new tableschema\DataSources\CsvDataSource("http://www.example.com/data.csv"), 
    new tableschema\Schema("http://www.example.com/data-schema.json")
);
foreach ($table as $person) {
    print($person["first_name"]." ".$person["last_name"]);
}

// validate a remote data source
$validationErrors = tableschema\Table::validate($dataSource, $schema);
print(tableschema\SchemaValidationError::getErrorMessages($validationErrors));

// infer schema of a remote data source
$dataSource = new tableschema\DataSources\CsvDataSource("http://www.example.com/data.csv");
$schema = new tableschema\InferSchema();
$table = new tableschema\Table($dataSource, $schema);
foreach ($table as $row) {
    var_dump($row); // row will be in inferred native values
    var_dump($schema->descriptor()); // will contain the inferred schema descriptor
    // the more iterations you make, the more accurate the inferred schema might be
    // once you are satisifed with the schema, lock it
    $rows = $schema->lock();
    // it returns all the rows received until the lock, casted to the final inferred schema
    // you may now continue to iterate over the rest of the rows
};

// schema creation, editing and saving

// EditableSchema extends the Schema object with editing capabilities
$schema = new EditableSchema();
// set fields
$schema->fields([
    "id" => FieldsFactory::field((object)["name" => "id", "type" => "integer"])
]);
// remove field
$schema->removeField("age");
// edit primaryKey
$schema->primaryKey(["id"]);

// after every change - schema is validated and will raise Exception in case of validation errors
// finally, you can save the schema to a json file
$schema->save("my-schema.json");
```

## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
