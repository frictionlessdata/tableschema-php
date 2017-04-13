<?php


use PHPUnit\Framework\TestCase;

use frictionlessdata\tableschema\Schema;


class SchemaTest extends TestCase
{

    public function testInitializeFromJsonString()
    {
        $schema = new Schema('{
            "fields": [
                {"name": "id"},
                {"name": "height", "type": "integer"}
            ]
        }');
        $this->assertEquals((object)[
            "fields" => [
                (object)["name" => "id"],
                (object)["name" => "height", "type" => "integer"]
            ]
        ], $schema->descriptor);
        $this->assertEquals("id", $schema->descriptor->fields[0]->name);
    }

    public function testInitializeFromPhpObject()
    {
        $schema = new Schema((object)[
            "fields" => [
                (object)["name" => "id"],
                (object)["name" => "height", "type" => "integer"]
            ]
        ]);
        $this->assertEquals((object)[
            "fields" => [
                (object)["name" => "id"],
                (object)["name" => "height", "type" => "integer"]
            ]
        ], $schema->descriptor);
    }

    public function testInitializeFromRemoteResource()
    {
        $validationErrors = Schema::validate("https://raw.githubusercontent.com/frictionlessdata/testsuite-extended/ecf1b2504332852cca1351657279901eca6fdbb5/datasets/synthetic/schema.json");
        $errorMessages = array_map(function($validationError){
            return $validationError->getMessage();
        }, $validationErrors);
        $this->assertEquals(["[primaryKey] String value found, but an array is required"], $errorMessages);
    }

}
