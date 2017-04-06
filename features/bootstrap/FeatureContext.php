<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    protected $descriptors = [];

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
    public function someValidDescriptors()
    {
        $this->descriptors[] = [
            "fields" => [
                ["name" => "id"],
                ["name" => "height", "type" => "integer"]
            ]
        ];
        $this->descriptors[] = [
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
    }

    /**
     * @When initializing the Schema object with the descriptors
     */
    public function initializingTheSchemaObject()
    {
        foreach ($this->descriptors as &$descriptor) {
            try {
                $descriptor["__schema"] = new \frictionlessdata\tableschema\Schema($descriptor);
            } catch (Exception $e) {
                $descirptor["__exception"] = $e;
            }

        }
    }

    /**
     * @Then object should be initialized without exceptions
     */
    public function objectShouldBeInitializedWithoutExceptions()
    {
        foreach ($this->descriptors as $descriptor) {
            Assert::assertArrayHasKey("__schema", $descriptor);
            Assert::assertArrayNotHasKey("__exception", $descriptor);
        }
    }
}
