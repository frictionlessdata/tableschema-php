<?php
namespace frictionlessdata\tableschema\tests;

use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\tableschema\DataSources\NativeDataSource;
use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\Schema;
use PHPUnit\Framework\TestCase;
use frictionlessdata\tableschema\Table;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\InferSchema;

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

    public function testInferSchemaFailsAfterLock()
    {
        $inputRows = [
            //      integer,          string       ==> best inferred: integer, string
            ["id" => "1", "email" => "test1_example_com"],
            //      integer,          string(email)  ==> best inferred: integer, string
            ["id" => "2", "email" => "test2@example.com"],
            //      number,          string(email)  ==> best inferred: integer, string
            ["id" => "3.5", "email" => "test3@example.com"],
        ];
        $dataSource = new NativeDataSource($inputRows);
        $schema = new InferSchema();
        $table = new Table($dataSource, $schema);
        $i = 0;
        $exceptionMessage = "";
        try {
            foreach ($table as $row) {
                $this->assertEquals([
                    // the rows as they come in after casting by the schema inferred so far
                    ["id" => 1, "email" => "test1_example_com"],
                    ["id" => 2, "email" => "test2@example.com"],
                    // next row has:
                    // "id" => "3.5"
                    // this is invalid for integer type which is the inferred schema (locked after 2 rows)
                ][$i], $row);
                if (++$i == 2) { // lock the inferred schema after 2 rows
                    $this->assertEquals([
                        // rows cast using the locked inferred schema (which is the same as the default cast in this case)
                        ["id" => 1, "email" => "test1_example_com"],
                        ["id" => 2, "email" => "test2@example.com"]
                    ], $schema->lock());
                }
            }
        } catch (FieldValidationException $e) {
            $exceptionMessage = $e->getMessage();
        }
        // the 3rd row did not match the inferred schema
        $this->assertEquals("id: value must be an integer (3.5)", $exceptionMessage);
        // try again with the same data source, but this time continue inferring the 3rd row as well
        $dataSource = new NativeDataSource($inputRows);
        $schema = new InferSchema();
        $table = new Table($dataSource, $schema);
        $i = 0;
        foreach ($table as $row) {
            $this->assertEquals([
                // the rows as they come in after casting by the schema inferred so far
                ["id" => 1, "email" => "test1_example_com"],
                ["id" => 2, "email" => "test2@example.com"],
                // here the type changes to number - you can see this below in the rows received from lock
                ["id" => 3.5, "email" => "test3@example.com"],
            ][$i], $row);
            $i++;
        }
        $this->assertEquals(
            [
                ["id" => 1, "email" => "test1_example_com"],
                ["id" => 2, "email" => "test2@example.com"],
                ["id" => 3.5, "email" => "test3@example.com"],
            ],
            $schema->lock()
        );
    }

    public function testInferSchemaWorksWithMoreRows()
    {
        $this->assertInferSchema(
            [
                ["id" => "1", "email" => "test1_example_com"],
                ["id" => "2", "email" => "test2@example.com"],
                // only when infer schema reaches this row it learns that value actually shouldn't be an integer
                ["id" => "3.5", "email" => "test3@example.com"],
            ],
            [
                ["id" => 1, "email" => "test1_example_com"],
                ["id" => 2, "email" => "test2@example.com"],
                ["id" => 3.5, "email" => "test3@example.com"]
            ],
            3
        );
    }

    public function testInferSchemaEmailFormat()
    {
        $inputRows = [
            ["email" => "valid_email@example.com"],
            ["email" => "invalid_email"],
        ];
        $this->assertInferSchemaException(
            // lock after 1 row, so that locked schema will be string with email format
            "email: value is not a valid email (invalid_email)", $inputRows, 1
        );
        // try again with locking after 2nd row - no exception
        // (value is not cast, because it's still a string)
        $this->assertInferSchema($inputRows, $inputRows, 2);
        // can also disable the string format inferring - so the first example will work
        $this->assertInferSchema($inputRows, $inputRows, 1, true);
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

    protected function assertInferSchema($expectedRows, $inputRows, $lockRowNum, $lenient=false)
    {
        $dataSource = new NativeDataSource($inputRows);
        $schema = new InferSchema(null, $lenient);
        $table = new Table($dataSource, $schema);
        $i = 0;
        $lockedRows = [];
        foreach ($table as $row) {
            if (++$i >= $lockRowNum) {
                if (empty($lockedRows)) {
                    $lockedRows = $schema->lock();
                } else {
                    $lockedRows[] = $row;
                }
            }
        }
        $this->assertEquals($expectedRows, $lockedRows);
    }

    protected function assertInferSchemaException($expectedException, $inputRows, $lockRowNum)
    {
        $exceptionMessage = null;
        try {
            $this->assertInferSchema([], $inputRows, $lockRowNum);
        } catch (FieldValidationException $e) {
            $expectionMessage = $e->getMessage();
        }
        $this->assertEquals($expectedException, $expectionMessage);
    }
}