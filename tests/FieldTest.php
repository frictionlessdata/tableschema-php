<?php

declare(strict_types=1);

namespace frictionlessdata\tableschema\tests;

use frictionlessdata\tableschema\DataSources\NativeDataSource;
use frictionlessdata\tableschema\Exceptions\DataSourceException;
use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\Fields\FieldsFactory;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\SchemaValidationError;
use frictionlessdata\tableschema\Table;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public $DESCRIPTOR_WITHOUT_TYPE;
    public $DESCRIPTOR_MIN;
    public $DESCRIPTOR_MAX;

    public function setUp(): void
    {
        $this->DESCRIPTOR_WITHOUT_TYPE = [
            'name' => 'id',
        ];
        $this->DESCRIPTOR_MIN = [
            'name' => 'id',
            'type' => 'string',
        ];
        $this->DESCRIPTOR_MAX = [
            'name' => 'id',
            'type' => 'integer',
            'format' => 'default',
            'constraints' => ['required' => true],
        ];
    }

    public function testNoValidFieldType(): void
    {
        $this->expectException(FieldValidationException::class);
        $this->expectExceptionMessage('Could not find a valid field for descriptor: {"name":"id"}');

        $fieldDescriptorWithoutType = ['name' => 'id'];
        FieldsFactory::field($fieldDescriptorWithoutType);
    }

    public function testDescriptor(): void
    {
        $fieldDescriptor = [
            'name' => 'id',
            'type' => 'string',
        ];

        $this->assertEquals(
            (object) $fieldDescriptor,
            FieldsFactory::field($fieldDescriptor)->descriptor()
        );
    }

    public function testName(): void
    {
        $fieldDescriptor = [
            'name' => 'id',
            'type' => 'string',
        ];

        $this->assertSame('id', FieldsFactory::field($fieldDescriptor)->name());
    }

    /**
     * @dataProvider provideFieldWithType
     */
    public function testType(string $expectedType, array $fieldDescriptor): void
    {
        $this->assertSame(
            $expectedType,
            FieldsFactory::field($fieldDescriptor)->type()
        );
    }

    public static function provideFieldWithType(): array
    {
        return [
            [
                'string',
                ['name' => 'id', 'type' => 'string'],
            ],
            [
                'integer',
                ['name' => 'id', 'type' => 'integer'],
            ],
        ];
    }

    /**
     * @dataProvider provideFieldDescriptorFormat
     */
    public function testFormat(string $expectedFormat, array $fieldDescriptor): void
    {
        $this->assertSame(
            $expectedFormat,
            FieldsFactory::field($fieldDescriptor)->format()
        );
    }

    public static function provideFieldDescriptorFormat(): array
    {
        return [
            [
                'default',
                ['name' => 'id', 'type' => 'string'],
            ],
            [
                'default',
                ['name' => 'id', 'type' => 'string', 'format' => 'default'],
            ],
        ];
    }

    /**
     * @dataProvider provideFieldConstraintsTestData
     */
    public function testConstraints(\stdClass $expectedConstraint, array $fieldDescriptor): void
    {
        $this->assertEquals(
            $expectedConstraint,
            FieldsFactory::field($fieldDescriptor)->constraints()
        );
    }

    public static function provideFieldConstraintsTestData(): array
    {
        return [
            [
                (object) [],
                ['name' => 'id', 'type' => 'string'],
            ],
            [
                (object) ['required' => true],
                [
                    'name' => 'id',
                    'type' => 'string',
                    'constraints' => ['required' => true],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideFieldRequiredTestData
     */
    public function testRequired(bool $expectedRequired, array $fieldDescriptor): void
    {
        $this->assertSame(
            $expectedRequired,
            FieldsFactory::field($fieldDescriptor)->required()
        );
    }

    public static function provideFieldRequiredTestData(): array
    {
        return [
            [
                false,
                ['name' => 'id', 'type' => 'string'],
            ],
            [
                true,
                [
                    'name' => 'id',
                    'type' => 'string',
                    'constraints' => ['required' => true],
                ],
            ],
        ];
    }

    public function testCastValue(): void
    {
        $fieldDescriptor = [
            'name' => 'id',
            'type' => 'integer',
        ];

        $this->assertEquals(1, FieldsFactory::field($fieldDescriptor)->castValue('1'));
    }

    public function testAdditionalMethods(): void
    {
        $field = FieldsFactory::field(['name' => 'name', 'type' => 'string']);
        $this->assertNull($field->title());
        $this->assertNull($field->description());
        $this->assertNull($field->rdfType());
        $field = FieldsFactory::field([
            'name' => 'name', 'type' => 'string',
            'title' => 'Title',
            'description' => 'Description',
            'rdfType' => 'https://schema.org/Thing',
        ]);
        $this->assertSame('Title', $field->title());
        $this->assertSame('Description', $field->description());
        $this->assertSame('https://schema.org/Thing', $field->rdfType());
    }

    public function testCastValueConstraintError(): void
    {
        $this->expectException(FieldValidationException::class);
        $this->expectExceptionMessage('id: field is required (null)');

        $fieldDescriptor = [
            'name' => 'id',
            'type' => 'integer',
            'constraints' => ['required' => true],
        ];

        FieldsFactory::field($fieldDescriptor)->castValue(null);
    }

    /**
     * @dataProvider provideDisableConstraintTestData
     *
     * @param mixed $expectedCastValue
     * @param mixed $valueToCast
     */
    public function testDisableConstraints($expectedCastValue, $valueToCast, array $fieldDescriptor): void
    {
        $this->assertSame(
            $expectedCastValue,
            FieldsFactory::field($fieldDescriptor)->disableConstraints()->castValue($valueToCast)
        );
    }

    public static function provideDisableConstraintTestData(): array
    {
        return [
            [
                '',
                '',
                ['name' => 'id', 'type' => 'string'],
            ],
            [
                null,
                null,
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'constraints' => ['required' => true],
                ],
            ],
        ];
    }

    public function testCastValueNullMissingValues(): void
    {
        // missing values are only validated at schema castRow function
        $schema = new Schema((object) [
            'fields' => [
                ['name' => 'name', 'type' => 'number'],
            ],
            'missingValues' => ['null'],
        ]);
        $this->assertSame(['name' => null], $schema->castRow(['name' => 'null']));
    }

    public function testDoNotCastValueNullMissingValues(): void
    {
        // missing values are only validated at schema castRow function
        $schema = new Schema((object) [
            'fields' => [
                ['name' => 'name', 'type' => 'string'],
            ],
            'missingValues' => [],
        ]);
        $this->assertSame(['name' => ''], $schema->castRow(['name' => '']));
    }

    /**
     * @dataProvider provideValidateValueTestData
     *
     * @param mixed $value
     */
    public function testValidateValue(string $expectedError, array $fieldDescriptor, $value): void
    {
        $this->assertFieldValidateValue($expectedError, $fieldDescriptor, $value);
    }

    public static function provideValidateValueTestData(): array
    {
        return [
            [
                '',
                ['name' => 'id', 'type' => 'integer'],
                '1',
            ],
            [
                'id: value must be numeric ("string")',
                ['name' => 'id', 'type' => 'integer'],
                'string',
            ],
            [
                'id: field is required (null)',
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'constraints' => ['required' => true],
                ],
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideValidateValueDisableConstraintsTestData
     */
    public function testValidateValueDisableConstraints(array $fieldDescriptor, $value): void
    {
        $this->assertSame(
            [],
            FieldsFactory::field($fieldDescriptor)->disableConstraints()->validateValue($value)
        );
    }

    public static function provideValidateValueDisableConstraintsTestData(): array
    {
        return [
            [
                ['name' => 'id', 'type' => 'string'],
                '',
            ],
            [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'constraints' => ['required' => true],
                ],
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideMissingDataFieldType
     */
    public function testMissingValues(string $fieldType): void
    {
        $this->assertMissingValues(['type' => $fieldType], ['', 'NA', 'N/A']);
    }

    public static function provideMissingDataFieldType(): array
    {
        return [
            ['string'],
            ['number'],
        ];
    }

    public function testValidateValueRequired(): void
    {
        $schema = new Schema((object) [
            'fields' => [
                (object) [
                    'name' => 'name',
                    'type' => 'string',
                    'constraints' => ['required' => true],
                ],
            ],
            'missingValues' => ['', 'NA', 'N/A'],
        ]);
        $this->assertSchemaValidateValue('', $schema, 'test');
        $this->assertSchemaValidateValue('', $schema, 'null');
        $this->assertSchemaValidateValue('', $schema, 'none');
        $this->assertSchemaValidateValue('', $schema, 'nil');
        $this->assertSchemaValidateValue('', $schema, 'nan');
        $this->assertSchemaValidateValue('name: field is required (null)', $schema, 'NA');
        $this->assertSchemaValidateValue('name: field is required (null)', $schema, 'N/A');
        $this->assertSchemaValidateValue('', $schema, '-');
        $this->assertSchemaValidateValue('name: field is required (null)', $schema, '');
        $this->assertSchemaValidateValue('name: field is required (null)', $schema, null);
    }

    public function testValidateValueUnique(): void
    {
        // unique values are only validated at the Table object
        $dataSource = new NativeDataSource([
            ['name' => 1],
            ['name' => 2],
            ['name' => 2],
            ['name' => 4],
        ]);
        $schema = new Schema((object) [
            'fields' => [
                (object) [
                    'name' => 'name',
                    'type' => 'integer',
                    'constraints' => (object) ['unique' => true],
                ],
            ],
        ]);
        $table = new Table($dataSource, $schema);
        $actualRows = [];
        try {
            foreach ($table as $row) {
                $actualRows[] = $row;
            }
            $this->fail();
        } catch (DataSourceException $e) {
            $this->assertEquals([
                ['name' => 1],
                ['name' => 2],
            ], $actualRows);
            $this->assertEquals('row 3: field must be unique', $e->getMessage());
        }
    }

    /**
     * @dataProvider provideValidDataForConstraint
     */
    public function testValidValueForConstraint(string $type, array $constraintDefinition, $validValue): void
    {
        $descriptor = [
            'name' => 'name',
            'type' => $type,
            'constraints' => (object) $constraintDefinition,
        ];

        $this->assertFieldValidateValue('', $descriptor, $validValue);
    }

    public static function provideValidDataForConstraint(): array
    {
        return [
            ['string', ['pattern' => '3.*'], '3'],
            ['string', ['pattern' => '3.*'], '321'],
            ['integer', ['enum' => ['1']], '1'],
            ['integer', ['enum' => ['2']], 2],
            ['integer', ['enum' => [3]], '3'],
            ['integer', ['minimum' => 1], 1],
            ['integer', ['minimum' => 1], '1'],
            ['integer', ['minimum' => 1], 2],
            ['integer', ['minimum' => 1], '2'],
            ['integer', ['maximum' => 1], 1],
            ['integer', ['maximum' => 1], '1'],
            ['integer', ['maximum' => 1], 0],
            ['integer', ['maximum' => 1], '0'],
            ['string', ['minLength' => 2], 'ab'],
            ['string', ['minLength' => 2], 'aaaa'],
            ['string', ['minLength' => 2], null],
            ['string', ['maxLength' => 2], 'ab'],
            ['string', ['maxLength' => 2], 'a'],
            ['string', ['maxLength' => 2], null],
            ['string', ['maxLength' => 2], ''],
        ];
    }

    /**
     * @dataProvider provideInvalidDataForConstraint
     */
    public function testInvalidValueForConstraint(
        string $expectedError,
        string $type,
        array $constraintDefinition,
        $invalidValue
    ): void {
        $descriptor = [
            'name' => 'name',
            'type' => $type,
            'constraints' => (object) $constraintDefinition,
        ];

        $this->assertFieldValidateValue($expectedError, $descriptor, $invalidValue);
    }

    public static function provideInvalidDataForConstraint(): array
    {
        return [
            ['name: value does not match pattern ("123")', 'string', ['pattern' => '3.*'], '123'],
            ['name: value not in enum ("4")', 'integer', ['enum' => ['1', '2', 3]], '4'],
            ['name: value not in enum (4)', 'integer', ['enum' => ['1', '2', 3]], 4],
            ['name: value is below minimum (0)', 'integer', ['minimum' => 1], 0],
            ['name: value is below minimum ("0")', 'integer', ['minimum' => 1], '0'],
            ['name: value is above maximum (2)', 'integer', ['maximum' => 1], 2],
            ['name: value is above maximum ("2")', 'integer', ['maximum' => 1], '2'],
            ['name: value is below minimum length ("a")', 'string', ['minLength' => 2], 'a'],
            ['name: value is above maximum length ("aaa")', 'string', ['maxLength' => 2], 'aaa'],
        ];
    }

    protected function assertFieldValidateValue(string $expectedErrors, array $descriptor, $value): void
    {
        $this->assertSame(
            $expectedErrors,
            SchemaValidationError::getErrorMessages(FieldsFactory::field($descriptor)->validateValue($value))
        );
    }

    /**
     * @param $expectedErrors
     * @param $schema Schema
     * @param $value
     */
    protected function assertSchemaValidateValue($expectedErrors, $schema, $value): void
    {
        $this->assertEquals(
            $expectedErrors,
            SchemaValidationError::getErrorMessages($schema->validateRow(['name' => $value]))
        );
    }

    protected function assertMissingValues($partialDescriptor, $missingValues): void
    {
        $descriptor = (object) ['name' => 'name'];
        foreach ($partialDescriptor as $k => $v) {
            $descriptor->{$k} = $v;
        }
        $schema = new Schema((object) [
            'fields' => [$descriptor],
            'missingValues' => $missingValues,
        ]);
        foreach ($missingValues as $val) {
            $this->assertSame(['name' => null], $schema->castRow(['name' => $val]));
        }
    }
}
