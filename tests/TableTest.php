<?php

declare(strict_types=1);

namespace frictionlessdata\tableschema\tests;

use Carbon\Carbon;
use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\tableschema\DataSources\NativeDataSource;
use frictionlessdata\tableschema\Exceptions\DataSourceException;
use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\InferSchema;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\Table;
use Generator;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function setUp(): void
    {
        $this->fixturesPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures';
        $this->validSchema = new Schema((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'integer'],
                (object) ['name' => 'name', 'type' => 'string'],
            ],
        ]);
    }

    public function testBasicUsage(): void
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

    public function testValidate(): void
    {
        $validationErrors = Table::validate(
            $this->dataSourceFixture('data.csv'),
            $this->schemaFixture('data.json')
        );
        $this->assertEquals('', SchemaValidationError::getErrorMessages($validationErrors));
    }

    public function testLoadingFromInvalidSource(): void
    {
        $this->assertTableValidation(
            [$this->getFopenErrorMessage('--invalid--')],
            '--invalid--', null
        );
    }

    public function testLoadingMissingHeader(): void
    {
        $this->assertTableValidation([
            'Failed to get header row',
        ], $this->fixture('empty_file'));
    }

    public function testInvalidDataInPeekRows(): void
    {
        $this->assertNativeTableValidation(
            ['row 2 email: value is not a valid email (bad and invalid email)'],
            [['email' => 'good@email.nice'], ['email' => 'bad and invalid email']],
            (object) ['fields' => [
                (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
            ]]
        );
    }

    public function testMismatchBetweenSchemaAndHeaders(): void
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

    public function testInferSchemaFailsAfterLock(): void
    {
        $this->assertInferSchemaException('id: value must be an integer ("3.5")', [
            ['id' => '1', 'email' => 'test1_example_com'],
            ['id' => '2', 'email' => 'test2@example.com'],
            ['id' => '3.5', 'email' => 'test3@example.com'],
        ], 2);
    }

    public function testAutoInferSchemaWhenNullSchema(): void
    {
        $table = new Table($this->fixture('data.csv'));
        $this->assertTrue(is_a($table->schema(), 'frictionlessdata\\tableschema\\InferSchema'));
    }

    public function testHeaders(): void
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

    /**
     * @dataProvider provideDuplicatePrimaryKeyTestData
     *
     * @param string|array|stdClass $descriptor
     */
    public function testEnforcePrimaryKey(string $expectedExceptionMessage, $descriptor, array $duplicateRows): void
    {
        $this->expectException(DataSourceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $tableData = new NativeDataSource($duplicateRows);
        $schema = new Schema($descriptor);
        $table = new Table($tableData, $schema);

        // Traverse all the rows.
        iterator_to_array($table);
    }

    public static function provideDuplicatePrimaryKeyTestData(): Generator
    {
        yield 'Null value not allowed for field in Primary Key' => [
            'row 1: value for id field cannot be null because it is part of the primary key',
            <<<JSON
{
"fields": [ {"name": "id"} ],
"primaryKey": ["id"]
}
JSON
            ,
            [['id' => '']],
        ];

        yield 'Missing value not allowed for field in Primary Key' => [
            'row 1: value for id field cannot be null because it is part of the primary key',
            <<<JSON
{
    "fields": [ {"name": "id"} ],
    "primaryKey": ["id"],
    "missingValues": ["n/a"]
}
JSON
            ,
            [['id' => 'n/a']],
        ];

        yield 'Duplicate row on single primary key for single field' => [
            'row 2: duplicate row for the primary key id',
            <<<JSON
{
    "fields": [ {"name": "id"} ],
    "primaryKey": ["id"]
}
JSON
            ,
            [
                ['id' => 'foo'],
                ['id' => 'foo'],
            ],
        ];

        yield 'Duplicate row on primary key for multiple fields' => [
            'row 3: duplicate row for the primary key id/age',
            <<<JSON
{
    "fields": [ {"name": "id"}, {"name": "age"} ],
    "primaryKey": [ "id", "age" ]
}
JSON
            ,
            [
                ['id' => 'foo', 'age' => '123'],
                ['id' => 'foo', 'age' => '234'],
                ['id' => 'foo', 'age' => '123'],
            ],
        ];

        yield 'Duplicate row on primary key for datetime object' => [
            'row 3: duplicate row for the primary key date',
            <<<JSON
{
    "fields": [ {"name": "date", "type": "date"} ],
    "primaryKey": [ "date" ]
}
JSON
            ,
            [
                ['date' => '2022-01-16'],
                ['date' => '2022-01-10'],
                ['date' => '2022-01-16'],
            ],
        ];

        yield 'Duplicate row on primary key with type-insensitivity' => [
            'row 3: duplicate row for the primary key id',
            <<<JSON
{
    "fields": [ {"name": "id", "type": "integer"} ],
    "primaryKey": [ "id" ]
}
JSON
            ,
            [
                ['id' => '123'],
                ['id' => 234],
                ['id' => 123],
            ],
        ];
    }

    public function testTableSave(): void
    {
        $table = new Table($this->fixture('data.csv'));
        $table->save('test-table-save-data.csv');
        $this->assertEquals(
            "first_name,last_name,order\nFoo,Bar,1\nBaz,Bax,2\nבאך,ביי,3\n",
            file_get_contents('test-table-save-data.csv')
        );
        unlink('test-table-save-data.csv');
    }

    public function testInferSchemaWorksWithMoreRows(): void
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

    public function testSimpleInferSchema(): void
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

    public function testInferSchemaEmailFormat(): void
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

    public function testInferSchema(): void
    {
        $this->assertInferSchemaTypes([
            'id' => 'integer',
            'age' => 'integer',
            'name' => 'string',
        ], 'data_infer.csv');
    }

    public function testInferSchemaUtf8(): void
    {
        $this->assertInferSchemaTypes([
            'id' => 'integer',
            'age' => 'integer',
            'name' => 'string',
        ], 'data_infer_utf8.csv');
    }

    public function testInferSchemaRowLimit(): void
    {
        $this->assertInferSchemaTypes([
            'id' => 'integer',
            'age' => 'integer',
            'name' => 'string',
        ], 'data_infer_utf8.csv', 4);
    }

    public function testCsvDialectLolsv(): void
    {
        $table = new Table($this->fixture('data.lolsv'), null, [
            'delimiter' => 'o',
            'quoteChar' => 'L',
            'header' => true,
            'caseSensitiveHeader' => false,
        ]);
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

    public function testCsvLineBreak(): void
    {
        $table = new Table($this->fixture('data_linebreaks.csv'));
        $this->assertEquals([
            ['aaa' => 'test a', 'bbb' => 'test b', 'ccc' => 'test c'],
        ], $table->read());
    }

    public function testCsvDialectDatapackagePipelines(): void
    {
        $datapackage = json_decode(file_get_contents($this->fixture('committees/datapackage.json')));
        $resource = $datapackage->resources[0];
        $table = new Table($this->fixture('committees/kns_committee.csv'), $resource->schema, $resource->dialect);
        $rows = [];
        $rowNum = 0;
        foreach ($table as $row) {
            if (in_array($rowNum, [0, 1, 132])) {
                $rows[] = $row;
            }
            ++$rowNum;
        }
        $this->assertEquals([[
            'CommitteeID' => 97,
            'Name' => 'ה"ח המדיניות הכלכלית לשנת הכספים 2004',
            'CategoryID' => null,
            'CategoryDesc' => null,
            'KnessetNum' => 16,
            'CommitteeTypeID' => 73,
            'CommitteeTypeDesc' => 'ועדה  משותפת',
            'Email' => null,
            'StartDate' => Carbon::create(2004, 8, 12, 0, 0, 0),
            'FinishDate' => null,
            'AdditionalTypeID' => null,
            'AdditionalTypeDesc' => null,
            'ParentCommitteeID' => null,
            'CommitteeParentName' => null,
            'IsCurrent' => true,
            'LastUpdatedDate' => Carbon::create(2015, 3, 20, 12, 2, 57),
        ], [
            'CommitteeID' => 314,
            'Name' => 'המיוחדת לענין לקחי אסון גשר המכביה',
            'CategoryID' => null,
            'CategoryDesc' => null,
            'KnessetNum' => 14,
            'CommitteeTypeID' => 72,
            'CommitteeTypeDesc' => 'ועדה מיוחדת',
            'Email' => null,
            'StartDate' => Carbon::create(1988, 10, 19, 0, 0, 0),
            'FinishDate' => null,
            'AdditionalTypeID' => 992,
            'AdditionalTypeDesc' => 'מיוחדת',
            'ParentCommitteeID' => null,
            'CommitteeParentName' => null,
            'IsCurrent' => true,
            'LastUpdatedDate' => Carbon::create(2015, 3, 20, 12, 2, 57),
        ], [
            'CommitteeID' => 679,
            'Name' => 'משותפת לכלכלה וחינוך לדיון בחוק הרשות השניה לטלויזיה ורדיו התש"ן-1990',
            'CategoryID' => 317,
            'CategoryDesc' => 'ועדה משותפת לכלכלה וחינוך לדיון בחוק הרשות השניה לטלוויזיה ורדיו, התש"ן-1990',
            'KnessetNum' => 18,
            'CommitteeTypeID' => 73,
            'CommitteeTypeDesc' => 'ועדה  משותפת',
            'Email' => 'vkalkala@knesset.gov.il',
            'StartDate' => Carbon::create(2009, 6, 30, 0, 0, 0),
            'FinishDate' => null,
            'AdditionalTypeID' => 991,
            'AdditionalTypeDesc' => 'קבועה',
            'ParentCommitteeID' => null,
            'CommitteeParentName' => null,
            'IsCurrent' => true,
            'LastUpdatedDate' => Carbon::create(2015, 3, 20, 12, 2, 57),
        ]], $rows);
    }

    public function testReadOptions(): void
    {
        $datapackage = json_decode(file_get_contents($this->fixture('committees/datapackage.json')));
        $resource = $datapackage->resources[0];
        $table = new Table($this->fixture('committees/kns_committee.csv'), $resource->schema, $resource->dialect);
        $this->assertEquals([
            [
                0,
                [
                    'CommitteeID', 'Name', 'CategoryID', 'CategoryDesc', 'KnessetNum', 'CommitteeTypeID',
                    'CommitteeTypeDesc', 'Email', 'StartDate', 'FinishDate', 'AdditionalTypeID',
                    'AdditionalTypeDesc', 'ParentCommitteeID', 'CommitteeParentName', 'IsCurrent', 'LastUpdatedDate',
                ], [
                    '97', 'ה"ח המדיניות הכלכלית לשנת הכספים 2004', '', '', '16', '73', 'ועדה  משותפת', '',
                    '2004-08-12 00:00:00', '', '',
                    '', '', '', 'True', '2015-03-20 12:02:57',
                ],
            ],
            [
                1,
                [
                    'CommitteeID', 'Name', 'CategoryID', 'CategoryDesc', 'KnessetNum', 'CommitteeTypeID',
                    'CommitteeTypeDesc', 'Email', 'StartDate', 'FinishDate', 'AdditionalTypeID',
                    'AdditionalTypeDesc', 'ParentCommitteeID', 'CommitteeParentName', 'IsCurrent', 'LastUpdatedDate',
                ],
                [
                    '314', 'המיוחדת לענין לקחי אסון גשר המכביה', '', '', '14', '72', 'ועדה מיוחדת', '',
                    '1988-10-19 00:00:00', '', '992',
                    'מיוחדת', '', '', 'True', '2015-03-20 12:02:57',
                ],
            ],
        ], $table->read(['keyed' => false, 'extended' => true, 'cast' => false, 'limit' => 2]));
    }

    public function testInvalidTabularData(): void
    {
        $schema = new Schema((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'integer'],
                (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
            ],
        ]);
        $dataSource = new CsvDataSource($this->fixture('invalid_tabular_data.csv'));
        $table = new Table($dataSource, $schema);
        try {
            foreach ($table as $row) {
            }
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals(
                'email: value is not a valid email ("bad.email")',
                $e->getMessage()
            );
        }
        $dataSource = new CsvDataSource($this->fixture('invalid_tabular_data.csv'));
        $table = new Table($dataSource, $schema);
        try {
            $table->read();
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals(
                'email: value is not a valid email ("bad.email")',
                $e->getMessage()
            );
        }
        $this->assertEquals(
            ['id' => '1', 'email' => 'good@email.and.nice'],
            $table->read(['cast' => false])[0]
        );
    }

    public function testEmailsTabularData(): void
    {
        $schema = new Schema((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'integer'],
                (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
            ],
        ]);
        $dataSource = new CsvDataSource($this->fixture('valid_emails_tabular_data.csv'));
        $table = new Table($dataSource, $schema);
        foreach ($table as $row) {
        }
        $dataSource = new CsvDataSource($this->fixture('valid_emails_tabular_data.csv'));
        $table = new Table($dataSource, $schema);
        $this->assertEquals(
            ['id' => '1', 'email' => 'good@email.and.nice'],
            $table->read(['cast' => false])[0]
        );
    }

    public function testIterateWithoutEof(): void
    {
        $dataSource = new CsvDataSource($this->fixture('valid_emails_tabular_data.csv'));
        $row = $dataSource->getNextLine();
        $this->assertTrue('1' === $row['id']);
        $this->assertTrue('good@email.and.nice' === $row['email']);
    }

    protected $fixturesPath;
    protected $validSchema;

    protected function fixture($file): string
    {
        return $this->fixturesPath.DIRECTORY_SEPARATOR.$file;
    }

    protected function dataSourceFixture($file): CsvDataSource
    {
        return new CsvDataSource($this->fixture($file));
    }

    protected function schemaFixture($file): Schema
    {
        return new Schema($this->fixture($file));
    }

    protected function assertTableValidation($expectedErrors, $csvDataSource, $schemaSource = null, $numPeekLines = 10): void
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

    protected function assertNativeTableValidation($expectedErrors, $data, $schemaSource, $numPeekLines = 10): void
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

    protected function getFopenErrorMessage($in): string
    {
        try {
            fopen($in, 'r');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        throw new \Exception();
    }

    protected function assertInferSchema($expectedRows, $inputRows, $lockRowNum, $lenient = false): void
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

    protected function assertInferSchemaException($expectedException, $inputRows, $lockRowNum): void
    {
        $exceptionMessage = null;
        try {
            $this->assertInferSchema([], $inputRows, $lockRowNum);
            $this->fail();
        } catch (FieldValidationException $e) {
            $this->assertEquals($expectedException, $e->getMessage());
        }
    }

    protected function assertInferSchemaTypes($expectedTypes, $filename, $numRows = 1): void
    {
        $schema = $this->getInferSchema($filename, $numRows);
        foreach ($expectedTypes as $fieldName => $expectedType) {
            $this->assertEquals($expectedType, $schema->field($fieldName)->type());
        }
    }

    protected function getInferSchema($filename, $numRows = 1): InferSchema
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
