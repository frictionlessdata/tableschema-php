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
        $this->fixturesPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures';
        $this->validSchema = new Schema((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'integer'],
                (object) ['name' => 'name', 'type' => 'string'],
            ],
        ]);
    }

    public function testBasicUsage()
    {
        $table = new Table($this->fixture('data.csv'), $this->fixture('data.json'));
        $rows = [];
        foreach ($table as $row) {
            $rows[] = $row;
        }
        $this->assertEquals([
            ['first_name' => 'Foo', 'last_name' => 'Bar', 'order' => 1],
            ['first_name' => 'Baz', 'last_name' => 'Bax', 'order' => 2],
            ['first_name' => 'באך', 'last_name' => 'ביי', 'order' => 3],
        ], $rows);
    }

    public function testValidate()
    {
        $validationErrors = Table::validate(
            $this->dataSourceFixture('data.csv'),
            $this->schemaFixture('data.json')
        );
        $this->assertEquals('', SchemaValidationError::getErrorMessages($validationErrors));
    }

    public function testLoadingFromInvalidSource()
    {
        $this->assertTableValidation(
            [$this->getFopenErrorMessage('--invalid--')],
            '--invalid--', null
        );
    }

    public function testLoadingMissingHeader()
    {
        $this->assertTableValidation([
            'Failed to get header row',
        ], $this->fixture('empty_file'));
    }

    public function testInvalidDataInPeekRows()
    {
        $this->assertNativeTableValidation(
            ['row 2 email: value is not a valid email (bad and invalid email)'],
            [['email' => 'good@email.nice'], ['email' => 'bad and invalid email']],
            (object) ['fields' => [
                (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
            ]]
        );
    }

    public function testMismatchBetweenSchemaAndHeaders()
    {
        $dataSource = new CsvDataSource($this->fixture('data.csv'));
        $schema = new Schema((object) ['fields' => [
            (object) ['name' => 'foo', 'type' => 'string'],
        ]]);
        // this is fine, as the foo field is not required and other fields in the csv are ignored
        $this->assertEquals([], Table::validate($dataSource, $schema));
        // the missing values are empty
        $table = new Table($dataSource, $schema);
        $lines = [];
        foreach ($table as $line) {
            $lines[] = $line;
        }
        $this->assertEquals([['foo' => null], ['foo' => null], ['foo' => null]], $lines);
    }

    public function testInferSchemaFailsAfterLock()
    {
        $this->assertInferSchemaException('id: value must be an integer ("3.5")', [
            ['id' => '1', 'email' => 'test1_example_com'],
            ['id' => '2', 'email' => 'test2@example.com'],
            ['id' => '3.5', 'email' => 'test3@example.com'],
        ], 2);
    }

    public function testAutoInferSchemaWhenNullSchema()
    {
        $table = new Table($this->fixture('data.csv'));
        $this->assertTrue(is_a($table->schema(), 'frictionlessdata\\tableschema\\InferSchema'));
    }

    public function testHeaders()
    {
        $table = new Table($this->fixture('data.csv'));
        $this->assertEquals(['first_name', 'last_name', 'order'], $table->headers());
        $this->assertEquals([
            ['first_name' => 'Foo', 'last_name' => 'Bar', 'order' => 1],
            ['first_name' => 'Baz', 'last_name' => 'Bax', 'order' => 2],
            ['first_name' => 'באך', 'last_name' => 'ביי', 'order' => 3],
        ], $table->read());

        $table = new Table($this->fixture('data.csv'));
        $this->assertEquals(['first_name', 'last_name', 'order'], $table->headers(1));
        $this->assertEquals([
            ['first_name' => 'Foo', 'last_name' => 'Bar', 'order' => 1],
            ['first_name' => 'Baz', 'last_name' => 'Bax', 'order' => 2],
            ['first_name' => 'באך', 'last_name' => 'ביי', 'order' => 3],
        ], $table->read());
        $this->assertEquals([
            ['first_name' => 'Foo', 'last_name' => 'Bar', 'order' => 1],
            ['first_name' => 'Baz', 'last_name' => 'Bax', 'order' => 2],
            ['first_name' => 'באך', 'last_name' => 'ביי', 'order' => 3],
        ], $table->read());
    }

    public function testTableSave()
    {
        $table = new Table($this->fixture('data.csv'));
        $table->save('test-table-save-data.csv');
        $this->assertEquals(
            "first_name,last_name,order\nFoo,Bar,1\nBaz,Bax,2\nבאך,ביי,3\n",
            file_get_contents('test-table-save-data.csv')
        );
        unlink('test-table-save-data.csv');
    }

    public function testInferSchemaWorksWithMoreRows()
    {
        $this->assertInferSchema(
            [
                ['id' => '1', 'email' => 'test1_example_com'],
                ['id' => '2', 'email' => 'test2@example.com'],
                // only when infer schema reaches this row it learns that value actually shouldn't be an integer
                ['id' => '3.5', 'email' => 'test3@example.com'],
            ],
            [
                ['id' => 1, 'email' => 'test1_example_com'],
                ['id' => 2, 'email' => 'test2@example.com'],
                ['id' => 3.5, 'email' => 'test3@example.com'],
            ],
            3
        );
    }

    public function testSimpleInferSchema()
    {
        $table = new Table($this->fixture('data.csv'));
        $this->assertEquals((object) [
            'fields' => [
                (object) ['name' => 'first_name', 'type' => 'string'],
                (object) ['name' => 'last_name', 'type' => 'string'],
                (object) ['name' => 'order', 'type' => 'integer'],
            ],
        ], $table->schema()->descriptor());
    }

    public function testInferSchemaEmailFormat()
    {
        $inputRows = [
            ['email' => 'valid_email@example.com'],
            ['email' => 'invalid_email'],
        ];
        $this->assertInferSchemaException(
            // lock after 1 row, so that locked schema will be string with email format
            'email: value is not a valid email ("invalid_email")', $inputRows, 1
        );
        // try again with locking after 2nd row - no exception
        // (value is not cast, because it's still a string)
        $this->assertInferSchema($inputRows, $inputRows, 2);
        // can also disable the string format inferring - so the first example will work
        $this->assertInferSchema($inputRows, $inputRows, 1, true);
    }

    public function testInferSchema()
    {
        $this->assertInferSchemaTypes([
            'id' => 'integer',
            'age' => 'integer',
            'name' => 'string',
        ], 'data_infer.csv');
    }

    public function testInferSchemaUtf8()
    {
        $this->assertInferSchemaTypes([
            'id' => 'integer',
            'age' => 'integer',
            'name' => 'string',
        ], 'data_infer_utf8.csv');
    }

    public function testInferSchemaRowLimit()
    {
        $this->assertInferSchemaTypes([
            'id' => 'integer',
            'age' => 'integer',
            'name' => 'string',
        ], 'data_infer_utf8.csv', 4);
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

    protected function assertTableValidation($expectedErrors, $csvDataSource, $schemaSource = null, $numPeekLines = 10)
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
        }
        $this->assertEquals($expectedErrors, $actualErrors);
    }

    protected function assertNativeTableValidation($expectedErrors, $data, $schemaSource, $numPeekLines = 10)
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
        }
        $this->assertEquals($expectedErrors, $actualErrors);
    }

    protected function getFopenErrorMessage($in)
    {
        try {
            fopen($in, 'r');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        throw new \Exception();
    }

    protected function assertInferSchema($expectedRows, $inputRows, $lockRowNum, $lenient = false)
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
            $this->fail();
        } catch (FieldValidationException $e) {
            $this->assertEquals($expectedException, $e->getMessage());
        }
    }

    protected function assertInferSchemaTypes($expectedTypes, $filename, $numRows = 1)
    {
        $schema = $this->getInferSchema($filename, $numRows);
        foreach ($expectedTypes as $fieldName => $expectedType) {
            $this->assertEquals($expectedType, $schema->field($fieldName)->type());
        }
    }

    protected function getInferSchema($filename, $numRows = 1)
    {
        $dataSource = new CsvDataSource(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.$filename);
        $schema = new InferSchema();
        $table = new Table($dataSource, $schema);
        $i = 0;
        foreach ($table as $row) {
            if (++$i > $numRows) {
                break;
            }
        }

        return $schema;
    }
}
