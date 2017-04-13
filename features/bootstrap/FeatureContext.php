<?php

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\Schema;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    protected $descriptors;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given a list of valid descriptors
     */
    public function givenValidDescriptors()
    {
        $simpleDescriptor = (object)[
            "fields" => [
                (object)["name" => "id"],
                (object)["name" => "height", "type" => "integer"]
            ]
        ];
        $fullDescriptor = (object)[
            "fields" => [
                (object)["name" => "id", "type" => "string", "constraints" => (object)["required" => true]],
                (object)["name" => "height", "type" => "number"],
                (object)["name" => "age", "type" => "integer"],
                (object)["name" => "name", "type" => "string"],
                (object)["name" => "occupation", "type" => "string"],
            ],
            "primaryKey" => ["id"],
            "foreignKeys" => [
                (object)[
                    "fields" => ["name"],
                    "reference" => (object)[
                        "resource" => "related-resource-idntifier-or-url", "fields" => ["id"]
                    ]
                ]
            ],
        ];
        $simpleFile = tempnam(sys_get_temp_dir(), "tableschema-php-tests");
        $fullFile = tempnam(sys_get_temp_dir(), "tableschema-php-tests");
        file_put_contents($simpleFile, json_encode($simpleDescriptor));
        file_put_contents($fullFile, json_encode($fullDescriptor));
        $this->descriptors = [
            ["descriptor" => $simpleDescriptor, "expected" => $simpleDescriptor],
            ["descriptor" => $fullDescriptor, "expected" => $fullDescriptor],
            ["descriptor" => json_encode($simpleDescriptor), "expected" => $simpleDescriptor],
            ["descriptor" => json_encode($fullDescriptor), "expected" => $fullDescriptor],
            ["descriptor" => $simpleFile, "expected" => $simpleDescriptor],
            ["descriptor" => $fullFile, "expected" => $fullDescriptor],
        ];
    }

    /**
     * @Given a list of invalid descriptors
     */
    public function givenInvalidDescriptors()
    {
        $this->descriptors = [
            [
                "descriptor" => [],
                "expected_errors" => ["Failed to load from the given descriptor"]
            ],
            [
                "descriptor" => "foobar",
                "expected_errors" => ["Failed to load from the given descriptor"]
            ],
            [
                "descriptor" => (object)[
                    "fields" => [
                        (object)["name" => "id", "title" => "Identifier", "type" => "magical_unicorn"],
                        (object)["name" => "title", "title" => "Title", "type" => "string"]
                    ],
                    "primaryKey" => "identifier",
                    "foreignKeys" => "foobar"
                ],
                "expected_errors" => [
                    "[fields[0].type] Does not have a value in the enumeration [\"string\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"number\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"integer\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"date\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"time\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"datetime\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"year\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"yearmonth\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"boolean\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"object\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"geopoint\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"geojson\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"array\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"duration\"]",
                    "[fields[0].type] Does not have a value in the enumeration [\"any\"]",
                    "[fields[0]] Failed to match at least one schema",
                    "[primaryKey] String value found, but an array is required",
                    "[foreignKeys] String value found, but an array is required"
                ]
            ],
            [
                "descriptor" => (object)[
                    "fields" => [1, 2, 3],
                    "primaryKey" => ["foobar", "bazbax"],
                ],
                "expected_errors" => [
                    "[fields[0]] Integer value found, but an object is required",
                    "[fields[0]] Failed to match at least one schema",
                    "[fields[1]] Integer value found, but an object is required",
                    "[fields[1]] Failed to match at least one schema",
                    "[fields[2]] Integer value found, but an object is required",
                    "[fields[2]] Failed to match at least one schema"
                ]
            ]
        ];
    }

    /**
     * @When initializing the Schema object with the descriptors
     */
    public function initializingTheSchemaObject()
    {
        foreach ($this->descriptors as &$data) {
            try {
                $data["__schema"] = new Schema($data["descriptor"]);
            } catch (Exception $e) {
                $data["__exception"] = $e;
            }
        }
    }

    /**
     * @When validating the descriptors
     */
    public function validatingTheDescriptors()
    {
        foreach ($this->descriptors as &$data) {
            try {
                $data["__validation_errors"] = array_map(function($validationError){
                    return $validationError->getMessage();
                }, Schema::validate($data["descriptor"]));
            } catch (Exception $e) {
                $data["__exception"] = $e;
            }
        }
    }

    /**
     * @Then all schemas should be initialized without exceptions and return the expected descriptor
     */
    public function objectShouldBeInitializedWithoutExceptions()
    {
        foreach ($this->descriptors as $data) {
            if (array_key_exists("__exception", $data)) {
                throw new Exception("unexpected exception: ".$data["__exception"]." for descriptor: ".json_encode($data["descriptor"]));
            }
            Assert::assertEquals($data["expected"], $data["__schema"]->descriptor);
        }
    }

    /**
     * @Then all schemas should raise an exception
     */
    public function allDescriptorSchemasShouldRaiseException()
    {
        foreach ($this->descriptors as $data) {
            $msg = "create schema from the descriptor did not raise an exception: ".json_encode($data);
            Assert::assertArrayNotHasKey("__schema", $data, $msg);
            Assert::assertArrayHasKey("__exception", $data, $msg);
        }
    }

    /**
     * @Then validation results should be as expected
     */
    public function validationResultsShouldBeAsExpected()
    {
        foreach ($this->descriptors as $data) {
            if (array_key_exists("__exception", $data)) {
                throw new Exception("unexpected exception: ".$data["__exception"]." for descriptor: ".json_encode($data["descriptor"]));
            }
            $msg = "validation for descriptor is not as expected: ".json_encode($data);
            Assert::assertEquals($data["expected_errors"], $data["__validation_errors"], $msg);
        }
    }
}
