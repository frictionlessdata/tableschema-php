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

Schema can be any parameter valid for the Schema object (See below), so you can use a url or filename which contains the schema

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

Optionally, specify a [CSV Dialect](https://frictionlessdata.io/specs/csv-dialect/):

```php
$table = new Table("tests/fixtures/data.csv", null, ["delimiter" => ";"]);
```

Table::read method allows to get all data as an array, it also supports options to modify reader behavior

```php
$table->read()  // returns all the data as an array
```

read accepts an options parameter, for example:

```php
$table->read(["cast" => false, "limit": 5])
```

The following options are available (the values are the default values):

```php
$table->read([
    "keyed" => true,  // flag to emit keyed rows
    "extended" => false,  // flag to emit extended rows
    "cast" => true,  //flag to disable data casting if false
    "limit" => null,  // integer limit of rows to return
]);
```

Additional methods and functionality

```php
$table->headers()  // ["first_name", "last_name", "order"]
$table->save("output.csv")  // iterate over all the rows and save the to a csv file
$table->schema()  // get the Schema object
$table->read()  // returns all the data as an array
```

### Schema

Schema class provides helpful methods for working with a table schema and related data.

`use frictionlessdata\tableschema\Schema;`

Schema objects can be constructed using any of the following:

* php array (or object)
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
$field = $schema->field("id");  // Field object (See Field reference below)
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

Infer schema based on source data:

```php
$schema = Schema::infer("tests/fixtures/data.csv");
$table->schema()->fields();  // ["first_name" => StringField, "last_name" => StringField, "order" => IntegerField]
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

appropriate Field object is created according to the given descriptor (see below for Field class reference)

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

Finally, you can get the full validated descriptor

```php
$schema->fullDescriptor();
```

And, save it to a json file

```php
$schema->save("my-schema.json");
```

### Field

Field class represents a single table schema field descriptor

Create a field from a descriptor

```php
use frictionlessdata\tableschema\Fields\FieldsFactory;
$field = FieldsFactory::field([
    "name" => "id", "type" => "integer",
    "constraints" => ["required" => true, "minimum" => 5]
]);
```

Cast and validate values using the field

```php
$field->castValue("3");  // exception: value is below minimum
$field->castValue("7");  // 7
```

Additional method to access field data

```php
$field("id")->format();  // "default"
$field("id")->name();  // "id"
$field("id")->type(); // "integer"
$field("id")->constraints();  // (object)["required"=>true, "minimum"=>1, "maximum"=>500]
$field("id")->enum();  // []
$field("id")->required();  // true
$field("id")->unique();  // false
$field("id")->title();  // "Id" (or null if not provided in descriptor)
$field("id")->description();  // "The ID" (or null if not provided in descriptor)
$field("id")->rdfType();  // "http://schema.org/Thing" (or null if not provided in descriptor)
```

## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
