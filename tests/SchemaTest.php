<?php
namespace frictionlessdata\tableschema\tests;

use PHPUnit\Framework\TestCase;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\SchemaValidationError;

class SchemaTest extends TestCase
{
    public $simpleDescriptorJson;
    public $simpleDescriptor;
    public $fullDescriptor;

    public function setUp()
    {
        $this->simpleDescriptorJson = '{
            "fields": [
                {"name": "id"},
                {"name": "height", "type": "integer"}
            ]
        }';
        $this->simpleDescriptor = json_decode($this->simpleDescriptorJson);
        $this->fullDescriptor = (object)[
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
    }

    public function testInitializeFromJsonString()
    {
        $schema = new Schema($this->simpleDescriptorJson);
        $this->assertEquals($this->simpleDescriptor, $schema->descriptor());
        $this->assertEquals("id", $schema->descriptor()->fields[0]->name);
    }

    public function testInitializeFromPhpObject()
    {
        $schema = new Schema($this->simpleDescriptor);
        $this->assertEquals($this->simpleDescriptor, $schema->descriptor());
    }

    public function testInitializeFromRemoteResource()
    {
        if (getenv("TABLESCHEMA_ENABLE_FRAGILE_TESTS")) {
            $this->assertValidationErrors(
                "[primaryKey] String value found, but an array is required",
                "https://raw.githubusercontent.com/frictionlessdata/testsuite-extended/ecf1b2504332852cca1351657279901eca6fdbb5/datasets/synthetic/schema.json"
            );
        } else {
            $this->markTestSkipped("skipping fragile tests, to enable set TABLESCHEMA_ENABLE_FRAGILE_TESTS=1");
        }
    }

    public function testValidateInvalidResources()
    {
        $this->assertValidationErrors(
            'error loading descriptor from source "--invalid--": file_get_contents(--invalid--): failed to open stream: No such file or directory',
            "--invalid--"

        );
        $this->assertValidationErrors(
            'error decoding descriptor {"fields":[]}: descriptor must be an object',
            ["fields" => []]
        );
    }

    public function testConstructFromInvalidResource()
    {
        try {
            new Schema("--invalid--");
            $this->fail("constructing from invalid descriptor should throw exception");
        } catch (\frictionlessdata\tableschema\Exceptions\SchemaLoadException $e) {
            $this->assertEquals(
                'error loading descriptor from source "--invalid--": file_get_contents(--invalid--): failed to open stream: No such file or directory',
                $e->getMessage()
            );
        }
        try {
            new Schema(["fields" => []]);
            $this->fail("constructing from invalid descriptor should throw exception");
        } catch (\frictionlessdata\tableschema\Exceptions\SchemaLoadException $e) {
            $this->assertEquals(
                'error decoding descriptor {"fields":[]}: descriptor must be an object',
                $e->getMessage());
        }
        try {
            new Schema((object)["fields" => []]);
            $this->fail("constructing from invalid descriptor should throw exception");
        } catch (\frictionlessdata\tableschema\Exceptions\SchemaValidationFailedException $e) {
            $this->assertEquals(
                'Schema failed validation: [fields] There must be a minimum of 1 items in the array',
                $e->getMessage()
            );
        }
    }

    public function testDifferentValidDescriptorSources()
    {
        $simpleFile = tempnam(sys_get_temp_dir(), "tableschema-php-tests");
        $fullFile = tempnam(sys_get_temp_dir(), "tableschema-php-tests");
        file_put_contents($simpleFile, json_encode($this->simpleDescriptor));
        file_put_contents($fullFile, json_encode($this->fullDescriptor));
        $descriptors = [
            ["descriptor" => $this->simpleDescriptor, "expected" => $this->simpleDescriptor],
            ["descriptor" => $this->fullDescriptor, "expected" => $this->fullDescriptor],
            ["descriptor" => json_encode($this->simpleDescriptor), "expected" => $this->simpleDescriptor],
            ["descriptor" => json_encode($this->fullDescriptor), "expected" => $this->fullDescriptor],
            ["descriptor" => $simpleFile, "expected" => $this->simpleDescriptor],
            ["descriptor" => $fullFile, "expected" => $this->fullDescriptor],
        ];
        foreach ($descriptors as $data) {
            $schema = new Schema($data["descriptor"]);
            $this->assertEquals($data["expected"], $schema->descriptor());
        }
    }

    public function testInvalidDescriptor()
    {
        $descriptors = [
            [
                "descriptor" => [],
                "expected_errors" => 'unexpected load error: descriptor must be an object'
            ],
            [
                "descriptor" => "foobar",
                "expected_errors" => 'error loading descriptor from source "foobar": file_get_contents(foobar): failed to open stream: No such file or directory'
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
                "expected_errors" => implode(", ", [
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
                ])
            ],
            [
                "descriptor" => (object)[
                    "fields" => [1, 2, 3],
                    "primaryKey" => ["foobar", "bazbax"],
                ],
                "expected_errors" => implode(", ", [
                    "[fields[0]] Integer value found, but an object is required",
                    "[fields[0]] Failed to match at least one schema",
                    "[fields[1]] Integer value found, but an object is required",
                    "[fields[1]] Failed to match at least one schema",
                    "[fields[2]] Integer value found, but an object is required",
                    "[fields[2]] Failed to match at least one schema"
                ])
            ]
        ];
        foreach ($descriptors as $data) {
            $this->assertValidationErrors($data["expected_errors"], $data["descriptor"]);
        }

    }

    protected function assertValidationErrors($expectedValidationErrors, $descriptor)
    {
        $this->assertEquals(
            $expectedValidationErrors,
            SchemaValidationError::getErrorMessages(
                Schema::validate($descriptor)
            )
        );
    }
}
