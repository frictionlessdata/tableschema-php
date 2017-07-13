# Table Schema

[![Travis](https://travis-ci.org/frictionlessdata/tableschema-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/tableschema-php)
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/tableschema-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/tableschema-php?branch=master)
[![Scrutinizer-ci](https://scrutinizer-ci.com/g/OriHoch/tableschema-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/OriHoch/tableschema-php/)
[![Packagist](https://img.shields.io/packagist/dm/frictionlessdata/tableschema.svg)](https://packagist.org/packages/frictionlessdata/tableschema)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
[![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Table Schema](https://specs.frictionlessdata.io/table-schema/) in php.


## Features summary and Usage guide

### Installation

```bash
$ composer require frictionlessdata/tableschema
```

### Schema

Schema class provides helpful methods for working with a table schema and related data.

`use frictionlessdata\tableschema\Schema;`

Schema objects can be constructed using any of the following:

* php array
```php
$schema = new Schema([
    'fields' => [
        [
            'name' => 'id', 'title' => 'Identifier', 'type' => 'integer', 
            'constraints' => [
                "required" => true,
                "minimum" => 1,
                "maximum" => 500
            ]
        ],
        ['name' => 'name', 'title' => 'Name', 'type' => 'string'],
    ],
    'primaryKey' => 'id'
]);
```

* string containing json
```php
$schema = new Schema("{
    \"fields\": [
        {\"name\": \"id\"},
        {\"name\": \"height\", \"type\": \"integer\"}
    ]
}");
```

* string containg value supported by [file_get_contents](http://php.net/manual/en/function.file-get-contents.php)
```php
$schema = new Schema("https://raw.githubusercontent.com/frictionlessdata/testsuite-extended/ecf1b2504332852cca1351657279901eca6fdbb5/datasets/synthetic/schema.json");
```

The schema is loaded, parsed and validated and will raise exceptions in case of any problems.

access the schema data, which is ensured to conform to the specs.

```php
$schema->missingValues(); // [""]
$schema->primaryKey();  // ["id"]
$schema->foreignKeys();  // []
$schema->fields(); // ["id" => IntegerField, "name" => StringField]
$field = $schema->field("id");
$field("id")->format();  // "default"
$field("id")->name();  // "id"
$field("id")->type(); // "integer"
$field("id")->constraints();  // (object)["required"=>true, "minimum"=>1, "maximum"=>500]
$field("id")->enum();  // []
$field("id")->required();  // true
$field("id")->unique();  // false
```

validate function accepts the same arguemnts as the Schema constructor but returns a list of errors instead of raising exceptions

```php
// validate functions accepts the same arguments as the Schema constructor
$validationErrors = Schema::validate("http://invalid.schema.json");
foreach ($validationErrors as $validationError) {
    print(validationError->getMessage();
};
```

validate and cast a row of data according to the schema

```php
$row = $schema->castRow(["id" => "1", "name" => "First Name"]);
```

will raise exception if row fails validation

it returns the row with all native values

```php
$row  // ["id" => 1, "name" => "First Name"];
```

validate the row to get a list of errors

```php
$schema->validateRow(["id" => "foobar"]);  // ["id is not numeric", "name is required" .. ]
```

You can also create a new empty schema for editing

```php
$schema = new Schema();
```

set fields

```php
$schema->fields([
    "id" => (object)["type" => "integer"],
    "name" => (object)["type" => "string"],
]);
```

appropriate field object is created according to the given descriptor

```php
$schema->field("id");  // IntegerField object
```

add / update or remove fields

```php
$schema->field("email", ["type" => "string", "format" => "email"]);
$schema->field("name", ["type" => "string"]);
$schema->removeField("name");
```

set or update other table schema attributes

```php
$schema->primaryKey(["id"]);
```

after every change - schema is validated and will raise Exception in case of validation errors

finally, save the schema to a json file

```php
$schema->save("my-schema.json");
```

### Table

Table class allows to iterate over data conforming to a table schema

Instantiate a Table object based on a data source and a table schema.

```php
use frictionlessdata\tableschema\Table;

$table = new Table("tests/fixtures/data.csv", ["fields" => [
    ["name" => "first_name"],
    ["name" => "last_name"],
    ["name" => "order"]
]]);
```

Schema can be any parameter valid for the Schema object, so you can use a url or filename which contains the schema

```php
$table = new Table("tests/fixtures/data.csv", "tests/fixtures/data.json");
```

iterate over the data, all the values are cast and validated according to the schema

```php
foreach ($table as $row) {
    print($row["order"]." ".$row["first_name"]." ".$row["last_name"]."\n");
};
```

validate function will validate the schema and get some sample of the data itself to validate it as well
 
```php
Table::validate(new CsvDataSource("http://invalid.data.source/"), $schema);
```

You can instantiate a table object without schema, in this case the schema will be inferred automatically based on the data

```php
$table = new Table("tests/fixtures/data.csv");
$table->schema()->fields();  // ["first_name" => StringField, "last_name" => StringField, "order" => IntegerField]
```

Additional methods and functionality

```php
$table->headers()  // ["first_name", "last_name", "order"]
$table->save("output.csv")  // iterate over all the rows and save the to a csv file
$table->schema()  // get the Schema object
$table->read()  // returns all the data as an array
```

## Important Notes

- Table schema is in transition to v1 - but many datapackage in the wild are still pre-v1
  - At the moment I am developing this library with support only for v1
  - See [this Gitter discussion](https://gitter.im/frictionlessdata/chat?at=58df75bfad849bcf423e5d80) about this transition


## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
