<?php

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

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
        $simpleDescriptor = [
            "fields" => [
                ["name" => "id"],
                ["name" => "height", "type" => "integer"]
            ]
        ];
        $fullDescriptor = [
            "fields" => [
                ["name" => "id", "type" => "string", "constraints" => ["required" => true]],
                ["name" => "height", "type" => "number"],
                ["name" => "age", "type" => "integer"],
                ["name" => "name", "type" => "string"],
                ["name" => "occupation", "type" => "string"],
            ],
            "primaryKey" => ["id"],
            "foreignKeys" => [
                ["fields" => ["name"], "reference" => ["resource" => "", "fields" => ["id"]]]
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
                "expected_errors" => ["Failed schema validation"]
            ],
            [
                "descriptor" => "foobar",
                "expected_errors" => ["Failed to load resource: Invalid resource: \"foobar\""]
            ],
            [
                "descriptor" => [
                    "fields" => [
                        ["name" => "id", "title" => "Identifier", "type" => "magical_unicorn"],
                        ["name" => "title", "title" => "Title", "type" => "string"]
                    ],
                    "primaryKey" => "identifier",
                    "foreignKeys" => [
                        [
                            "fields" => ["id", "notafield"],
                            "reference" => ["datapackage" => "http://data.okfn.org/data/mydatapackage/", "fields" => "no"]
                        ]
                    ]
                ],
                "expected_errors" => ["primaryKey must be an array"]
            ],
            [
                "descriptor" => [
                    "fields" => [1, 2, 3],
                    "primaryKey" => ["foobar", "bazbax"],
                ],
                "expected_errors" => [
                    "field 1 is not an array", "field 2 is not an array", "field 3 is not an array",
                    "primaryKey foobar must relate to a field",
                    "primaryKey bazbax must relate to a field"
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
                $data["__schema"] = new \frictionlessdata\tableschema\Schema($data["descriptor"]);
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
                $data["__validation"] = \frictionlessdata\tableschema\Schema::validate($data["descriptor"]);
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
            Assert::assertEquals($data["expected_errors"], $data["__validation"], $msg);
        }
    }
}
