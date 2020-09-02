<?php

namespace frictionlessdata\tableschema\tests;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use PHPUnit\Framework\TestCase;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\Fields\FieldsFactory;

class SchemaTest extends TestCase
{
    public $simpleDescriptorJson;
    public $simpleDescriptor;
    public $fullDescriptor;
    public $minDescriptorJson;
    public $maxDescriptorJson;
    public $schemaValidFullFilename;
    public $schemaValidSimpleFilename;
    public $schemaInvalidMultipleErrorsFilename;

    public function setUp(): void
    {
        $this->simpleDescriptorJson = '{
            "fields": [
                {"name": "id"},
                {"name": "height", "type": "integer"}
            ]
        }';
        $this->minDescriptorJson = '{"fields": [{"name": "id"}, {"name": "height", "type": "integer"}]}';
        $this->maxDescriptorJson = '{
            "fields": [
                {"name": "id", "type": "string", "constraints": {"required": true}},
                {"name": "height", "type": "number"},
                {"name": "age", "type": "integer"},
                {"name": "name", "type": "string"},
                {"name": "occupation", "type": "string"}
            ],
            "primaryKey": ["id"],
            "foreignKeys": [{"fields": ["name"], "reference": {"resource": "data.csv", "fields": ["id"]}}],
            "missingValues": ["", "-", "null"]
        }';
        $this->simpleDescriptor = json_decode($this->simpleDescriptorJson);
        $this->fullDescriptor = (object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'string', 'constraints' => (object) ['required' => true]],
                (object) ['name' => 'height', 'type' => 'number'],
                (object) ['name' => 'age', 'type' => 'integer'],
                (object) ['name' => 'name', 'type' => 'string'],
                (object) ['name' => 'occupation', 'type' => 'string'],
            ],
            'primaryKey' => ['id'],
            'foreignKeys' => [
                (object) [
                    'fields' => ['name'],
                    'reference' => (object) [
                        'resource' => 'related-resource-idntifier-or-url', 'fields' => ['id'],
                    ],
                ],
            ],
        ];
        $this->schemaValidFullFilename = dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema_valid_full.json';
        $this->schemaValidSimpleFilename = dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema_valid_simple.json';
        $this->schemaInvalidMultipleErrorsFilename = dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema_invalid_multiple_errors.json';
    }

    public function testInitializeFromJsonString()
    {
        $schema = new Schema($this->simpleDescriptorJson);
        $this->assertEquals($this->simpleDescriptor, $schema->descriptor());
        $this->assertEquals('id', $schema->descriptor()->fields[0]->name);
    }

    public function testInitializeFromPhpObject()
    {
        $schema = new Schema($this->simpleDescriptor);
        $this->assertEquals($this->simpleDescriptor, $schema->descriptor());
    }

    public function testInitializeFromPhpArray()
    {
        $schema = new Schema(json_decode($this->simpleDescriptorJson, true));
        $this->assertEquals($this->simpleDescriptor, $schema->descriptor());
    }

    public function testInitializeFromRemoteResource()
    {
        if (getenv('TABLESCHEMA_ENABLE_FRAGILE_TESTS')) {
            $this->assertValidationErrors(
                '',
                'https://raw.githubusercontent.com/frictionlessdata/testsuite-extended/ecf1b2504332852cca1351657279901eca6fdbb5/datasets/synthetic/schema.json'
            );
        } else {
            $this->markTestSkipped('skipping fragile tests, to enable set TABLESCHEMA_ENABLE_FRAGILE_TESTS=1');
        }
    }

    public function testValidateInvalidResources()
    {
        $this->assertValidationErrors(
            'error loading descriptor from source "--invalid--": '.$this->getFileGetContentsErrorMessage('--invalid--'),
            '--invalid--'
        );
    }

    public function testConstructFromInvalidResource()
    {
        try {
            new Schema('--invalid--');
            $this->fail('constructing from invalid descriptor should throw exception');
        } catch (\frictionlessdata\tableschema\Exceptions\SchemaLoadException $e) {
            $this->assertEquals(
                'error loading descriptor from source "--invalid--": '.$this->getFileGetContentsErrorMessage('--invalid--'),
                $e->getMessage()
            );
        }
        try {
            new Schema((object) ['fields' => []]);
            $this->fail('constructing from invalid descriptor should throw exception');
        } catch (\frictionlessdata\tableschema\Exceptions\SchemaValidationFailedException $e) {
            $this->assertEquals(
                'Schema failed validation: [fields] There must be a minimum of 1 items in the array',
                $e->getMessage()
            );
        }
    }

    public function testDifferentValidDescriptorSources()
    {
        $simpleFile = $this->getTempFile();
        $fullFile = $this->getTempFile();
        file_put_contents($simpleFile, json_encode($this->simpleDescriptor));
        file_put_contents($fullFile, json_encode($this->fullDescriptor));
        $descriptors = [
            ['descriptor' => $this->simpleDescriptor, 'expected' => $this->simpleDescriptor],
            ['descriptor' => $this->fullDescriptor, 'expected' => $this->fullDescriptor],
            ['descriptor' => json_encode($this->simpleDescriptor), 'expected' => $this->simpleDescriptor],
            ['descriptor' => json_encode($this->fullDescriptor), 'expected' => $this->fullDescriptor],
            ['descriptor' => $simpleFile, 'expected' => $this->simpleDescriptor],
            ['descriptor' => $fullFile, 'expected' => $this->fullDescriptor],
        ];
        foreach ($descriptors as $data) {
            $schema = new Schema($data['descriptor']);
            $this->assertEquals($data['expected'], $schema->descriptor());
        }
    }

    public function testInvalidDescriptor()
    {
        $descriptors = [
            [
                'descriptor' => [],
                'expected_errors' => '[] Array value found, but an object is required',
            ],
            [
                'descriptor' => 'foobar',
                'expected_errors' => 'error loading descriptor from source "foobar": '.$this->getFileGetContentsErrorMessage('foobar'),
            ],
            [
                'descriptor' => '{"fields": ["name": "id", "type": "integer"]}',
                'expected_errors' => 'error decoding descriptor "{\"fields\": [\"name\": \"id\", \"type\": \"integer\"]}": invalid json',
            ],
            [
                'descriptor' => (object) [
                    'fields' => [
                        (object) ['name' => 'id', 'title' => 'Identifier', 'type' => 'magical_unicorn'],
                        (object) ['name' => 'title', 'title' => 'Title', 'type' => 'string'],
                    ],
                    'primaryKey' => 'identifier',
                    'foreignKeys' => 'foobar',
                ],
                'expected_errors' => implode(', ', [
                    '[fields[0].type] Does not have a value in the enumeration ["string"]',
                    '[fields[0].type] Does not have a value in the enumeration ["number"]',
                    '[fields[0].type] Does not have a value in the enumeration ["integer"]',
                    '[fields[0].type] Does not have a value in the enumeration ["date"]',
                    '[fields[0].type] Does not have a value in the enumeration ["time"]',
                    '[fields[0].type] Does not have a value in the enumeration ["datetime"]',
                    '[fields[0].type] Does not have a value in the enumeration ["year"]',
                    '[fields[0].type] Does not have a value in the enumeration ["yearmonth"]',
                    '[fields[0].type] Does not have a value in the enumeration ["boolean"]',
                    '[fields[0].type] Does not have a value in the enumeration ["object"]',
                    '[fields[0].type] Does not have a value in the enumeration ["geopoint"]',
                    '[fields[0].type] Does not have a value in the enumeration ["geojson"]',
                    '[fields[0].type] Does not have a value in the enumeration ["array"]',
                    '[fields[0].type] Does not have a value in the enumeration ["duration"]',
                    '[fields[0].type] Does not have a value in the enumeration ["any"]',
                    '[fields[0]] Failed to match at least one schema',
                    '[foreignKeys] String value found, but an array is required',
                ]),
            ],
            [
                'descriptor' => (object) [
                    'fields' => [1, 2, 3],
                    'primaryKey' => ['foobar', 'bazbax'],
                ],
                'expected_errors' => implode(', ', [
                    '[fields[0]] Integer value found, but an object is required',
                    '[fields[0]] Failed to match at least one schema',
                    '[fields[1]] Integer value found, but an object is required',
                    '[fields[1]] Failed to match at least one schema',
                    '[fields[2]] Integer value found, but an object is required',
                    '[fields[2]] Failed to match at least one schema',
                ]),
            ],
        ];
        foreach ($descriptors as $data) {
            $this->assertValidationErrors($data['expected_errors'], $data['descriptor']);
        }
    }

    public function testValidateRow()
    {
        $schema = new Schema((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'integer'],
                (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
            ],
        ]);
        $this->assertEquals(
            'id: value must be numeric ("foobar"), email: value is not a valid email ("bad.email")',
            SchemaValidationError::getErrorMessages(
                $schema->validateRow(['id' => 'foobar', 'email' => 'bad.email'])
            )
        );
    }

    public function testValidInitialize()
    {
        new Schema($this->minDescriptorJson);
        new Schema($this->maxDescriptorJson);
        new Schema($this->schemaValidFullFilename);
        new Schema($this->schemaValidSimpleFilename);
    }

    //def test_init_invalid():
    //with pytest.raises(exceptions.SchemaValidationError) as exception:
    //Schema('data/schema_invalid_multiple_errors.json')
    public function testInvalidInitialize()
    {
        try {
            new Schema($this->schemaInvalidMultipleErrorsFilename);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals(
                'Schema failed validation: [fields[0].type] Does not have a value in the enumeration ["string"], [fields[0].type] Does not have a value in the enumeration ["number"], [fields[0].type] Does not have a value in the enumeration ["integer"], [fields[0].type] Does not have a value in the enumeration ["date"], [fields[0].type] Does not have a value in the enumeration ["time"], [fields[0].type] Does not have a value in the enumeration ["datetime"], [fields[0].type] Does not have a value in the enumeration ["year"], [fields[0].type] Does not have a value in the enumeration ["yearmonth"], [fields[0].type] Does not have a value in the enumeration ["boolean"], [fields[0].type] Does not have a value in the enumeration ["object"], [fields[0].type] Does not have a value in the enumeration ["geopoint"], [fields[0].type] Does not have a value in the enumeration ["geojson"], [fields[0].type] Does not have a value in the enumeration ["array"], [fields[0].type] Does not have a value in the enumeration ["duration"], [fields[0].type] Does not have a value in the enumeration ["any"], [fields[0]] Failed to match at least one schema, [foreignKeys[0].fields] Array value found, but a string is required, [foreignKeys[0].reference.resource] The property resource is required, [foreignKeys[0].reference.fields] String value found, but an array is required, [foreignKeys[0]] Failed to match exactly one schema',
                $e->getMessage()
            );
        }
    }

    public function testDescriptor()
    {
        $schema = new Schema($this->simpleDescriptorJson);
        $this->assertEquals($this->simpleDescriptor, $schema->descriptor());
    }

    public function testDescriptorDefaults()
    {
        $schema = new Schema((object) [
            'fields' => [
                (object) ['name' => 'id'],
                (object) ['name' => 'height', 'type' => 'integer'],
            ],
        ]);
        $this->assertEquals((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'string', 'format' => 'default'],
                (object) ['name' => 'height', 'type' => 'integer', 'format' => 'default'],
            ],
            'missingValues' => [''],
        ], $schema->fullDescriptor());
    }

    public function testCastRow()
    {
        $this->assertCastRow(
            ['id' => 1, 'email' => 'test@example.com'],
            (object) [
                'fields' => [
                    (object) ['name' => 'id', 'type' => 'integer'],
                    (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
                ],
            ],
            ['id' => '1', 'email' => 'test@example.com']
        );
        $this->assertCastRow(
            ['id' => 'string', 'height' => 10.0, 'age' => 1, 'name' => 'string', 'occupation' => 'string'],
            $this->maxDescriptorJson,
            ['id' => 'string', 'height' => '10.0', 'age' => '1', 'name' => 'string', 'occupation' => 'string']
        );
    }

    public function testCastRowNullValues()
    {
        $this->assertCastRow(
            ['id' => 'string', 'height' => null, 'age' => null, 'name' => 'string', 'occupation' => null],
            $this->maxDescriptorJson,
            ['id' => 'string', 'height' => '', 'age' => '', 'name' => 'string', 'occupation' => 'null']
        );
    }

    public function testCastRowTooShort()
    {
        // missing values in row are completed with null value from schema (if not required)
        $this->assertCastRow(
            ['id' => 'string', 'height' => 10.0, 'age' => 1, 'name' => 'string', 'occupation' => null],
            $this->maxDescriptorJson,
            ['id' => 'string', 'height' => '10.0', 'age' => '1', 'name' => 'string']
        );
    }

    public function testCastRowTooLong()
    {
        // additiona values in row are ignored
        $this->assertCastRow(
            ['id' => 'string', 'height' => 10.0, 'age' => 1, 'name' => 'string', 'occupation' => null],
            $this->maxDescriptorJson,
            ['id' => 'string', 'height' => '10.0', 'age' => '1', 'name' => 'string', 'additional' => 'string']
        );
    }

    public function testCastRowWrongType()
    {
        $this->assertCastRowException(
            'height: value must be numeric ("notdecimal")',
            $this->maxDescriptorJson,
            ['id' => 'string', 'height' => 'notdecimal', 'age' => '1', 'name' => 'string', 'additional' => 'string']
        );
    }

    public function testCastRowWrongTypeMultipleErrors()
    {
        $this->assertCastRowException(
            'height: value must be numeric ("notdecimal"), age: value must be an integer ("10.6")',
            $this->maxDescriptorJson,
            ['id' => 'string', 'height' => 'notdecimal', 'age' => '10.6', 'name' => 'string', 'additional' => 'string']
        );
    }

    public function testFields()
    {
        $schema = new Schema($this->minDescriptorJson);
        $this->assertEquals(['id', 'height'], array_keys($schema->fields()));
    }

    public function testGetField()
    {
        $schema = new Schema($this->minDescriptorJson);
        $this->assertEquals('id', $schema->field('id')->name());
        $this->assertEquals('height', $schema->field('height')->name());
        try {
            $schema->field('undefined')->name();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('unknown field name: undefined', $e->getMessage());
        }
    }

    public function testHasField()
    {
        $schema = new Schema($this->minDescriptorJson);
        $fields = $schema->fields();
        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('height', $fields);
        $this->assertArrayNotHasKey('undefined', $fields);
    }

    public function testPrimaryKey()
    {
        $this->assertEquals([], (new Schema($this->minDescriptorJson))->primaryKey());
        $this->assertEquals(['id'], (new Schema($this->maxDescriptorJson))->primaryKey());
    }

    public function testPrimaryKeyAsString()
    {
        $descriptor = json_decode($this->maxDescriptorJson);
        $descriptor->primaryKey = 'id';
        $this->assertEquals(['id'], (new Schema($descriptor))->primaryKey());
    }

    public function testForeignKeys()
    {
        $this->assertEquals([], (new Schema($this->minDescriptorJson))->foreignKeys());
        $this->assertEquals([
            (object) [
                'fields' => ['name'],
                'reference' => (object) [
                    'resource' => 'data.csv',
                    'fields' => ['id'],
                ],
            ],
        ], (new Schema($this->maxDescriptorJson))->foreignKeys());
    }

    public function testEditable()
    {
        $schema = new Schema();
        // set fields
        $schema->fields([
            'id' => (object) ['type' => 'integer'],
        ]);
        // add field
        $schema->field('age', (object) ['type' => 'integer']);
        // edit field
        $schema->field('age', FieldsFactory::field((object) ['name' => 'age', 'type' => 'number']));
        // remove field
        $schema->removeField('age');
        // after every change - schema is validated and will raise Exception in case of validation errors
        try {
            $schema->field('age', FieldsFactory::field((object) []));
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('Could not find a valid field for descriptor: {}', $e->getMessage());
        }
        // additional fields available for editing
        try {
            $schema->primaryKey(['aardvark']);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('Schema failed validation: primary key must refer to a field name (aardvark)', $e->getMessage());
        }
        $schema->primaryKey(['id']);
        $this->assertEquals((object) [
            'fields' => [(object) ['name' => 'id', 'type' => 'integer']],
            'primaryKey' => ['id'],
        ], $schema->descriptor());
        $foreignKeys = [
            (object) [
                'fields' => ['nonexistantfield'],
                'reference' => (object) [
                    'resource' => 'non-existant-external-resource.csv',
                    'fields' => ['doesnt_matter'],
                ],
            ],
        ];
        try {
            $schema->foreignKeys($foreignKeys);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals(
                'Schema failed validation: foreign key fields must refer to a field name (nonexistantfield)',
                $e->getMessage()
            );
        }
        // fields supports a string value, in that case it's considered an array of 1 field
        $foreignKeys[0]->fields = ['id'];
        // this is equivalent to:
        // $foreignKeys[0]->fields = ['id'];
        $schema->foreignKeys($foreignKeys);
        $schema->field('age', FieldsFactory::field((object) ['name' => 'age', 'type' => 'integer']));
        $foreignKeys[0]->fields = ['age'];
        try {
            $schema->missingValues('invalid value');
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('Schema failed validation: [missingValues] String value found, but an array is required', $e->getMessage());
        }
        $schema->missingValues(['', 'null']);
        $this->assertEquals((object) [
            'fields' => [
                (object) ['name' => 'id', 'type' => 'integer', 'format' => 'default'],
                (object) ['name' => 'age', 'type' => 'integer', 'format' => 'default'],
            ],
            'primaryKey' => ['id'],
            'foreignKeys' => [
                (object) [
                    'fields' => ['age'],
                    'reference' => (object) [
                        'resource' => 'non-existant-external-resource.csv',
                        'fields' => ['doesnt_matter'],
                    ],
                ],
            ],
            'missingValues' => ['', 'null'],
        ], $schema->fullDescriptor());
    }

    public function testSchemaToEditableSchema()
    {
        $schema = new Schema('{"fields": [{"name": "id", "type": "integer"}]}');
        $schema->field('title', '{"type": "string"}');
        $this->assertEquals((object) ['fields' => [
            (object) ['name' => 'id', 'type' => 'integer'],
            (object) ['name' => 'title', 'type' => 'string'],
        ]], $schema->descriptor());
    }

    public function testSave()
    {
        $schema = new Schema($this->minDescriptorJson);
        $filename = $this->getTempFile();
        $schema->save($filename);
        $this->assertEquals($schema->fullDescriptor(), json_decode(file_get_contents($filename)));
    }

    public function testSpecsUriFormat()
    {
        $validator = new \JsonSchema\Validator();
        // we will validate this against a simple schema of an array of uri strings
        $descriptor = ['data/data.csv'];
        $validator->validate(
            $descriptor,
            // this is a simple schema with only an array of uri strings
            (object) ['$ref' => 'file://'.realpath(dirname(__FILE__)).'/fixtures/uri-string-schema.json']
        );
        // validation fails
        $this->assertFalse($validator->isValid());
        $this->assertEquals([[
            'property' => '[0]',
            'pointer' => '/0',
            // it considers file names to be invalid uris
            'message' => 'Invalid URL format',
            'constraint' => 'format',
            'context' => 1,
            'format' => 'uri',
        ]], $validator->getErrors());
    }

    public function testSchemaInfer()
    {
        $schema = Schema::infer('tests/fixtures/data.csv');
        $this->assertEquals((object) [
            'fields' => [
                (object) ['name' => 'first_name', 'type' => 'string'],
                (object) ['name' => 'last_name', 'type' => 'string'],
                (object) ['name' => 'order', 'type' => 'integer'],
            ],
        ], $schema->descriptor());
    }

    public function testSchemaInferCsvDialect()
    {
        $schema = Schema::infer('tests/fixtures/data.lolsv', [
            'delimiter' => 'o',
            'quoteChar' => 'L',
            'header' => true,
            'caseSensitiveHeader' => false,
        ]);
        $this->assertEquals((object) [
            'fields' => [
                (object) ['name' => 'first_name', 'type' => 'string'],
                (object) ['name' => 'last_name', 'type' => 'string'],
                (object) ['name' => 'order', 'type' => 'integer'],
            ],
        ], $schema->descriptor());
    }

    public function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    protected $tempFiles = [];

    protected function assertValidationErrors($expectedValidationErrors, $descriptor)
    {
        $this->assertEquals(
            $expectedValidationErrors,
            SchemaValidationError::getErrorMessages(
                Schema::validate($descriptor)
            )
        );
    }

    protected function getFileGetContentsErrorMessage($in)
    {
        try {
            file_get_contents($in);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        throw new \Exception();
    }

    protected function assertCastRow($expectedRow, $descriptor, $inputRow)
    {
        $schema = new Schema($descriptor);
        $this->assertEquals($expectedRow, $schema->castRow($inputRow));

        return $schema;
    }

    protected function assertCastRowException($expectedError, $descriptor, $inputRow)
    {
        try {
            $schema = new Schema($descriptor);
            $schema->castRow($inputRow);
            $this->fail();
        } catch (FieldValidationException $e) {
            $this->assertEquals($expectedError, $e->getMessage());
        }
    }

    protected function getTempFile()
    {
        $file = tempnam(sys_get_temp_dir(), 'tableschema-php-tests');
        $this->tempFiles[] = $file;

        return $file;
    }
}
