<?php
namespace frictionlessdata\tableschema\tests;

use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\tableschema\DataSources\NativeDataSource;
use frictionlessdata\tableschema\Schema;
use PHPUnit\Framework\TestCase;
use frictionlessdata\tableschema\Table;
use frictionlessdata\tableschema\SchemaValidationError;

class TableTest extends TestCase
{
    public function setUp()
    {
        $this->fixturesPath = dirname(__FILE__).DIRECTORY_SEPARATOR."fixtures";
        $this->validSchema = new Schema((object)[
            "fields" => [
                (object)["name" => "id", "type" => "integer"],
                (object)["name" => "name", "type" => "string"]
            ]
        ]);
    }

    public function testBasicUsage()
    {
        $dataSource = new CsvDataSource($this->fixture("data.csv"));
        $schema = new Schema($this->fixture("data.json"));
        $table = new Table($dataSource, $schema);
        $rows = [];
        foreach ($table as $row) {
            $rows[] = $row;
        }
        $this->assertEquals([
            ["first_name" => "Foo", "last_name" => "Bar", "order" => 1],
            ["first_name" => "Baz", "last_name" => "Bax", "order" => 2],
            ["first_name" => "באך", "last_name" => "ביי", "order" => 3],
        ], $rows);
    }

    public function testValidate()
    {
        $validationErrors = Table::validate(
            $this->dataSourceFixture("data.csv"),
            $this->schemaFixture("data.json")
        );
        $this->assertEquals("", SchemaValidationError::getErrorMessages($validationErrors));
    }

    public function testLoadingFromInvalidSource()
    {
        $this->assertTableValidation(
            [$this->getFopenErrorMessage("--invalid--")],
            "--invalid--", null
        );
    }

    public function testLoadingMissingHeader()
    {
        $this->assertTableValidation([
            'Failed to get header row'
        ], $this->fixture("empty_file"));
    }

    public function testInvalidDataInPeekRows()
    {
        $this->assertNativeTableValidation(
            ['row 2 email: value is not a valid email (bad and invalid email)'],
            [["email" => "good@email.nice"], ["email" => "bad and invalid email"]],
            (object)["fields" => [
                (object)["name" => "email", "type" => "string", "format" => "email"]
            ]]
        );
    }

    public function testMismatchBetweenSchemaAndHeaders()
    {
        $dataSource = new CsvDataSource($this->fixture("data.csv"));
        $schema = new Schema((object)["fields" => [
            (object)["name" => "foo", "type" => "string"]
        ]]);
        // this is fine, as the foo field is not required and other fields in the csv are ignored
        $this->assertEquals([], Table::validate($dataSource, $schema));
        // the missing values are empty
        $table = new Table($dataSource, $schema);
        $lines = [];
        foreach ($table as $line) {
            $lines[] = $line;
        }
        $this->assertEquals([["foo" => null], ["foo" => null], ["foo" => null]], $lines);
    }

    protected $fixturesPath;
    protected $validSchema;

    protected function fixture($file)
    {
        return $this->fixturesPath.DIRECTORY_SEPARATOR.$file;
    }

    protected function dataSourceFixture($file)
    {
        return new CsvDataSource($this->fixture($file));
    }

    protected function schemaFixture($file)
    {
        return new Schema($this->fixture($file));
    }

    protected function assertTableValidation($expectedErrors, $csvDataSource, $schemaSource=null, $numPeekLines=10)
    {
        $actualErrors = [];
        $dataSource = new CsvDataSource($csvDataSource);
        if ($schemaSource) {
            $schema = new Schema($schemaSource);
        } else {
            $schema = $this->validSchema;
        }
        foreach (Table::validate($dataSource, $schema, $numPeekLines) as $error) {
            $actualErrors[] = $error->getMessage();
        };
        $this->assertEquals($expectedErrors, $actualErrors);
    }

    protected function assertNativeTableValidation($expectedErrors, $data, $schemaSource, $numPeekLines=10)
    {
        $actualErrors = [];
        $dataSource = new NativeDataSource($data);
        if ($schemaSource) {
            $schema = new Schema($schemaSource);
        } else {
            $schema = $this->validSchema;
        }
        foreach (Table::validate($dataSource, $schema, $numPeekLines) as $error) {
            $actualErrors[] = $error->getMessage();
        };
        $this->assertEquals($expectedErrors, $actualErrors);
    }

    protected function getFopenErrorMessage($in)
    {
        try {
            fopen($in, "r");
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        throw new \Exception();
    }
}