<?php

declare(strict_types=1);

namespace frictionlessdata\tableschema\tests;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\Exceptions\SchemaLoadException;
use frictionlessdata\tableschema\Exceptions\SchemaValidationFailedException;
use frictionlessdata\tableschema\Fields\FieldsFactory;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\SchemaValidationError;
use PHPUnit\Framework\TestCase;
use stdClass;

class SchemaTest extends TestCase
{
    private const SIMPLE_DESCRIPTOR_JSON = <<<'JSON'
{
    "fields": [
        {"name": "id"},
        {"name": "height", "type": "integer"}
    ]
}
JSON;
    private const MIN_DESCRIPTOR_JSON = <<<'JSON'
{"fields": [{"name": "id"}, {"name": "height", "type": "integer"}]}
JSON;
    private const MAX_DESCRIPTOR_JSON = <<<'JSON'
{
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
}
JSON;
    private const FULL_DESCRIPTOR_JSON = <<<'JSON'
{
    "fields": [
        {
            "name": "id",
            "type": "string",
            "constraints": {
                "required": true
            }
        },
        {
            "name": "height",
            "type": "number"
        },
        {
            "name": "age",
            "type": "integer"
        },
        {
            "name": "name",
            "type": "string"
        },
        {
            "name": "occupation",
            "type": "string"
        }
    ],
    "primaryKey": [
        "id"
    ],
    "foreignKeys": [
        {
            "fields": [
                "name"
            ],
            "reference": {
                "resource": "related-resource-idntifier-or-url",
                "fields": [
                    "id"
                ]
            }
        }
    ]
}
JSON;
    private const SIMPLE_DESCRIPTOR_FILE_PATH = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema_valid_simple.json';
    private const FULL_DESCRIPTOR_FILE_PATH = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema_valid_full.json';
    private const INVALID_DESCRIPTOR_FILE_PATH = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema_invalid_multiple_errors.json';

    public function testInitializeFromRemoteResource(): void
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

    /**
     * @dataProvider provideInvalidSchema
     *
     * @param string|array|stdClass $invalidSchema
     */
    public function testConstructFromInvalidResource(
        string $expectedExceptionClass,
        string $expectedExceptionMessage,
        $invalidSchema
    ): void {
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);

        new Schema($invalidSchema);
    }

    public function provideInvalidSchema(): array
    {
        return [
            [
                SchemaLoadException::class,
                'error loading descriptor from source "--invalid--": file_get_contents(--invalid--): Failed to open stream: No such file or directory',
                '--invalid--',
            ],
            [
                SchemaValidationFailedException::class,
                'Schema failed validation: [fields] There must be a minimum of 1 items in the array',
                (object) ['fields' => []],
            ],
        ];
    }

    /**
     * @dataProvider provideValidDescriptorSources
     *
     * @param string|array|stdClass $originalDescriptor
     */
    public function testDifferentValidDescriptorSources(stdClass $expectedDescriptor, $originalDescriptor): void
    {
        $schema = new Schema($originalDescriptor);
        $this->assertEquals($expectedDescriptor, $schema->descriptor());
    }

    public function provideValidDescriptorSources(): \Generator
    {
        yield 'Simple object descriptor' => [
            json_decode(self::SIMPLE_DESCRIPTOR_JSON, false),
            json_decode(self::SIMPLE_DESCRIPTOR_JSON, false),
        ];
        yield 'Full object descriptor' => [
            json_decode(self::FULL_DESCRIPTOR_JSON, false),
            json_decode(self::FULL_DESCRIPTOR_JSON, false),
        ];
        yield 'Simple JSON descriptor' => [
            json_decode(self::SIMPLE_DESCRIPTOR_JSON, false),
            self::SIMPLE_DESCRIPTOR_JSON,
        ];
        yield 'Full JSON descriptor' => [
            json_decode(self::FULL_DESCRIPTOR_JSON, false),
            self::FULL_DESCRIPTOR_JSON,
        ];
        yield 'Simple array descriptor' => [
            json_decode(self::SIMPLE_DESCRIPTOR_JSON, false),
            json_decode(self::SIMPLE_DESCRIPTOR_JSON, true),
        ];
        yield 'Full array descriptor' => [
            json_decode(self::FULL_DESCRIPTOR_JSON, false),
            json_decode(self::FULL_DESCRIPTOR_JSON, true),
        ];
        $simpleDescriptorFilePath = $this->getTempFile();
        file_put_contents($simpleDescriptorFilePath, self::SIMPLE_DESCRIPTOR_JSON);
        yield 'Simple JSON descriptor from file' => [
            json_decode(self::SIMPLE_DESCRIPTOR_JSON, false),
            $simpleDescriptorFilePath,
        ];
        $fullDescriptorFilePath = $this->getTempFile();
        file_put_contents($fullDescriptorFilePath, self::FULL_DESCRIPTOR_JSON);
        yield 'Full JSON descriptor from file' => [
            json_decode(self::FULL_DESCRIPTOR_JSON, false),
            $fullDescriptorFilePath,
        ];
    }

    /**
     * @dataProvider provideInvalidDescriptors
     *
     * @param string|array|stdClass $invalidDescriptor
     */
    public function testInvalidDescriptor(string $expectedErrors, $invalidDescriptor): void
    {
        $this->assertValidationErrors($expectedErrors, $invalidDescriptor);
    }

    public function provideInvalidDescriptors(): array
    {
        return [
            [
                '[] Array value found, but an object is required',
                [],
            ],
            [
                'error loading descriptor from source "foobar": file_get_contents(foobar): Failed to open stream: No such file or directory',
                'foobar',
            ],
            [
                'error decoding descriptor "{\"fields\": [\"name\": \"id\", \"type\": \"integer\"]}": invalid json',
                '{"fields": ["name": "id", "type": "integer"]}',
            ],
            [
                implode(', ', [
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
                (object) [
                    'fields' => [
                        (object) ['name' => 'id', 'title' => 'Identifier', 'type' => 'magical_unicorn'],
                        (object) ['name' => 'title', 'title' => 'Title', 'type' => 'string'],
                    ],
                    'primaryKey' => 'identifier',
                    'foreignKeys' => 'foobar',
                ],
            ],
            [
                implode(', ', [
                    '[fields[0]] Integer value found, but an object is required',
                    '[fields[0]] Failed to match at least one schema',
                    '[fields[1]] Integer value found, but an object is required',
                    '[fields[1]] Failed to match at least one schema',
                    '[fields[2]] Integer value found, but an object is required',
                    '[fields[2]] Failed to match at least one schema',
                ]),
                (object) [
                    'fields' => [1, 2, 3],
                    'primaryKey' => ['foobar', 'bazbax'],
                ],
            ],
        ];
    }

    public function testValidateRow(): void
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

    /**
     * @dataProvider provideValidDescriptors
     *
     * @param string|array|stdClass $validDescriptor
     */
    public function testValidInitialize($validDescriptor): void
    {
        try {
            new Schema($validDescriptor);
        } catch (\Throwable $e) {
            self::fail('Unexpected exception: ', get_class($e));
        }

        // Indicate test ran and passed.
        self::assertTrue(true);
    }

    public function provideValidDescriptors(): array
    {
        return [
            [self::MIN_DESCRIPTOR_JSON],
            [self::MAX_DESCRIPTOR_JSON],
            [self::FULL_DESCRIPTOR_FILE_PATH],
            [self::SIMPLE_DESCRIPTOR_FILE_PATH],
        ];
    }

    //def test_init_invalid():
    //with pytest.raises(exceptions.SchemaValidationError) as exception:
    //Schema('data/schema_invalid_multiple_errors.json')
    public function testInvalidInitialize(): void
    {
        $this->expectException(SchemaValidationFailedException::class);
        $this->expectExceptionMessage(
            'Schema failed validation: [fields[0].type] Does not have a value in the enumeration ["string"], [fields[0].type] Does not have a value in the enumeration ["number"], [fields[0].type] Does not have a value in the enumeration ["integer"], [fields[0].type] Does not have a value in the enumeration ["date"], [fields[0].type] Does not have a value in the enumeration ["time"], [fields[0].type] Does not have a value in the enumeration ["datetime"], [fields[0].type] Does not have a value in the enumeration ["year"], [fields[0].type] Does not have a value in the enumeration ["yearmonth"], [fields[0].type] Does not have a value in the enumeration ["boolean"], [fields[0].type] Does not have a value in the enumeration ["object"], [fields[0].type] Does not have a value in the enumeration ["geopoint"], [fields[0].type] Does not have a value in the enumeration ["geojson"], [fields[0].type] Does not have a value in the enumeration ["array"], [fields[0].type] Does not have a value in the enumeration ["duration"], [fields[0].type] Does not have a value in the enumeration ["any"], [fields[0]] Failed to match at least one schema, [foreignKeys[0].fields] Array value found, but a string is required, [foreignKeys[0].reference.resource] The property resource is required, [foreignKeys[0].reference.fields] String value found, but an array is required, [foreignKeys[0]] Failed to match exactly one schema'
        );
        new Schema(self::INVALID_DESCRIPTOR_FILE_PATH);
    }

    public function testDescriptorDefaults(): void
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

    /**
     * @dataProvider provideRowCastingTestData
     *
     * @param string|array|stdClass $descriptor
     */
    public function testCastRowNew(array $expectedRow, $descriptor, array $inputRow): void
    {
        $this->assertCastRow($expectedRow, $descriptor, $inputRow);
    }

    public function provideRowCastingTestData(): \Generator
    {
        yield 'Cast integer field' => [
            ['id' => 1, 'email' => 'test@example.com'],
            (object) [
                'fields' => [
                    (object) ['name' => 'id', 'type' => 'integer'],
                    (object) ['name' => 'email', 'type' => 'string', 'format' => 'email'],
                ],
            ],
            ['id' => '1', 'email' => 'test@example.com'],
        ];
        yield 'Cast integer and numeric field' => [
            ['id' => 'string', 'height' => 10.0, 'age' => 1, 'name' => 'string', 'occupation' => 'string'],
            self::MAX_DESCRIPTOR_JSON,
            ['id' => 'string', 'height' => '10.0', 'age' => '1', 'name' => 'string', 'occupation' => 'string'],
        ];
        yield 'Cast null values' => [
            ['id' => 'string', 'height' => null, 'age' => null, 'name' => 'string', 'occupation' => null],
            self::MAX_DESCRIPTOR_JSON,
            ['id' => 'string', 'height' => '', 'age' => '', 'name' => 'string', 'occupation' => 'null'],
        ];
        // missing values in row are completed with null value from schema (if not required)
        yield 'Add missing optional values' => [
            ['id' => 'string', 'height' => 10.0, 'age' => 1, 'name' => 'string', 'occupation' => null],
            self::MAX_DESCRIPTOR_JSON,
            ['id' => 'string', 'height' => '10.0', 'age' => '1', 'name' => 'string'],
        ];
        // Additional values in row are ignored
        yield 'Discards additional values' => [
            ['id' => 'string', 'height' => 10.0, 'age' => 1, 'name' => 'string', 'occupation' => null],
            self::MAX_DESCRIPTOR_JSON,
            ['id' => 'string', 'height' => '10.0', 'age' => '1', 'name' => 'string', 'additional' => 'string'],
        ];
    }

    /**
     * @dataProvider provideCastExceptionTestData
     *
     * @param string|array|object $descriptor
     */
    public function testCastException(string $expectedExceptionMessage, $descriptor, array $invalidRow): void
    {
        $this->expectException(FieldValidationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $schema = new Schema($descriptor);
        $schema->castRow($invalidRow);
    }

    public function provideCastExceptionTestData(): array
    {
        return [
            'Wrong type in row' => [
                'height: value must be numeric ("notdecimal")',
                self::MAX_DESCRIPTOR_JSON,
                ['id' => 'string', 'height' => 'notdecimal', 'age' => '1', 'name' => 'string', 'additional' => 'string'],
            ],
            'Multiple wrong types in row' => [
                'height: value must be numeric ("notdecimal"), age: value must be an integer ("10.6")',
                self::MAX_DESCRIPTOR_JSON,
                ['id' => 'string', 'height' => 'notdecimal', 'age' => '10.6', 'name' => 'string', 'additional' => 'string'],
            ],
        ];
    }

    public function testFields(): void
    {
        $schema = new Schema(self::MIN_DESCRIPTOR_JSON);

        $this->assertEquals(['id', 'height'], array_keys($schema->fields()));
        $this->assertEquals('id', $schema->field('id')->name());
        $this->assertEquals('height', $schema->field('height')->name());
    }

    public function testExceptionOnGetUndefinedField(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('unknown field name: undefined');

        $schema = new Schema(self::MIN_DESCRIPTOR_JSON);
        $schema->field('undefined')->name();
    }

    /**
     * @dataProvider providePrimaryKeyTestData
     *
     * @param string|array|stdClass $descriptor
     */
    public function testPrimaryKey(array $expectedKeys, $descriptor): void
    {
        $this->assertEquals($expectedKeys, (new Schema($descriptor))->primaryKey());
    }

    public function providePrimaryKeyTestData(): \Generator
    {
        yield 'PK not defined' => [[], self::MIN_DESCRIPTOR_JSON];
        yield 'PK defined as array' => [['id'], self::MAX_DESCRIPTOR_JSON];
        $descriptor = json_decode(self::MAX_DESCRIPTOR_JSON, false);
        $descriptor->primaryKey = 'id';
        yield 'PK defined as string' => [['id'], $descriptor];
    }

    public function testForeignKeys(): void
    {
        $this->assertEquals([], (new Schema(self::MIN_DESCRIPTOR_JSON))->foreignKeys());
        $this->assertEquals([
            (object) [
                'fields' => ['name'],
                'reference' => (object) [
                    'resource' => 'data.csv',
                    'fields' => ['id'],
                ],
            ],
        ], (new Schema(self::MAX_DESCRIPTOR_JSON))->foreignKeys());
    }

    public function testEditable(): void
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

    public function testSchemaToEditableSchema(): void
    {
        $schema = new Schema('{"fields": [{"name": "id", "type": "integer"}]}');
        $schema->field('title', '{"type": "string"}');
        $this->assertEquals((object) ['fields' => [
            (object) ['name' => 'id', 'type' => 'integer'],
            (object) ['name' => 'title', 'type' => 'string'],
        ]], $schema->descriptor());
    }

    public function testSave(): void
    {
        $schema = new Schema(self::MIN_DESCRIPTOR_JSON);
        $filename = $this->getTempFile();
        $schema->save($filename);
        $this->assertEquals($schema->fullDescriptor(), json_decode(file_get_contents($filename), false));
    }

    public function testSpecsUriFormat(): void
    {
        $validator = new \JsonSchema\Validator();
        // we will validate this against a simple schema of an array of uri strings
        $descriptor = ['data/data.csv'];
        $validator->validate(
            $descriptor,
            // this is a simple schema with only an array of uri strings
            (object) ['$ref' => 'file://'.realpath(__DIR__).'/fixtures/uri-string-schema.json']
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

    public function testSchemaInfer(): void
    {
        $schema = Schema::infer(__DIR__.'/fixtures/data.csv');
        $this->assertEquals((object) [
            'fields' => [
                (object) ['name' => 'first_name', 'type' => 'string'],
                (object) ['name' => 'last_name', 'type' => 'string'],
                (object) ['name' => 'order', 'type' => 'integer'],
            ],
        ], $schema->descriptor());
    }

    public function testSchemaInferCsvDialect(): void
    {
        $schema = Schema::infer(__DIR__.'/fixtures/data.lolsv', [
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

    protected function assertValidationErrors($expectedValidationErrors, $descriptor): void
    {
        $this->assertEquals(
            $expectedValidationErrors,
            SchemaValidationError::getErrorMessages(
                Schema::validate($descriptor)
            )
        );
    }

    protected function assertCastRow($expectedRow, $descriptor, $inputRow): Schema
    {
        $schema = new Schema($descriptor);
        $this->assertEquals($expectedRow, $schema->castRow($inputRow));

        return $schema;
    }

    protected function assertCastRowException($expectedError, $descriptor, $inputRow): void
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
