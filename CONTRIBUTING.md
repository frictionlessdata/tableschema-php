# Contributing

The project follows the [Open Knowledge International coding standards](https://github.com/okfn/coding-standards).

All PHP Code MUST conform to [PHP-FIG](http://www.php-fig.org/psr/) accepted PSRs.

Flow Framework has a nice guide regarding coding standards:
* [Printable summary of most important coding guidelines on one page **(.pdf)**](http://flowframework.readthedocs.io/en/stable/_downloads/Flow_Coding_Guidelines_on_one_page.pdf)
* [The full guide **(.html)**](http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartV/CodingGuideLines/PHP.html)


## Getting Started

1. Clone the repo
2. Run the tests
```
$ composer install
$ composer test
```


## Phpunit - for unit tests

[![Travis](https://travis-ci.org/frictionlessdata/tableschema-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/tableschema-php)

Phpunit is used for unit tests, you can find the tests under tests directory

Running Phpunit directly: `vendor/bin/phpunit --bootstrap tests/autoload.php tests/`


## Coveralls - for coverage

[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/tableschema-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/tableschema-php?branch=master)

when running `composer test` phpunit generates coverage report in coverage-clover.xml - this is then sent to Coveralls via Travis.


## Scrutinizer-ci - for code analysis

[![Scrutinizer-ci](https://scrutinizer-ci.com/g/OriHoch/tableschema-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/OriHoch/tableschema-php/)

Scrutinizer-ci integrates with GitHub and runs on commits.

It does static code analysis and ensure confirmation to the coding stnadards.

At the moment, the integration with frictionlessdata repo is not working, you can setup a Scrutinizer-ci account for your fork and run against that.


## Publishing to Packagist

[![Packagist](https://img.shields.io/packagist/dm/frictionlessdata/tableschema.svg)](https://packagist.org/packages/frictionlessdata/tableschema)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)

* Publish a release (versioning based on SemVer) on GitHub
* go to https://packagist.org/packages/frictionlessdata/tableschema
* Login with GitHub which has permissions
* click "Update"
* all releases from GitHub appear as releases on Packagist
