# Table Schema

[![Travis](https://travis-ci.org/frictionlessdata/tableschema-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/tableschema-php)<!-- 
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/tableschema-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/tableschema-php?branch=master)
[![Packagist](https://img.shields.io/packagist/dm/oki/tableschema.svg)](https://packagist.org/packages/oki/tableschema)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
 --> [![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Table Schema](https://specs.frictionlessdata.io/table-schema/) in php.


## Features

**work in progress** - library is in active development and not ready for general use yet.

### Schema

A model of a schema with helpful methods for working with the schema and supported data.


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

$schema = new Schema([
    "fields" => [
        ["name" => "id"],
        ["name" => "height", "type" => "integer"]
    ]
]);
```


## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
