<?php

declare(strict_types=1);

namespace Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\Fields\BaseField;
use PHPUnit\Framework\TestCase;

/**
 * @covers \frictionlessdata\tableschema\Fields\BaseField
 */
class BaseFieldTest extends TestCase
{
    public function testPreserveOriginalValueInValidateError(): void
    {
        $descriptor = (object) [
            'name' => 'date_col',
            'constraints' => (object) ['minimum' => '2025-07-01'],
        ];

        $sut = new class($descriptor) extends BaseField {
            protected function validateCastValue($val)
            {
                // If the logic is wrong, this object will be in the error
                // instead of the original date string.
                return new \DateTimeImmutable($val);
            }
        };

        $validatedValue = '2025-06-30';
        $errors = $sut->validateValue($validatedValue);

        self::assertCount(1, $errors);
        $error = reset($errors);
        self::assertSame(
            $validatedValue,
            $error->extraDetails['value']
        );
    }

    public function testPreserveOriginalValueInCastError(): void
    {
        $descriptor = (object) [
            'name' => 'date_col',
            'constraints' => (object) ['minimum' => '2025-07-01'],
        ];

        $sut = new class($descriptor) extends BaseField {
            protected function validateCastValue($val)
            {
                // If the logic is wrong, this object will be in the error
                // instead of the original date string.
                return new \DateTimeImmutable($val);
            }
        };

        $validatedValue = '2025-06-30';
        $exception = null;

        try {
            $sut->castValue($validatedValue);
        } catch (FieldValidationException $exception) {
        }

        self::assertNotNull($exception, 'An exception is expected for this test.');
        self::assertSame(
            'date_col: value is below minimum ("2025-06-30")',
            $exception->getMessage()
        );

        $errors = $exception->validationErrors;

        self::assertCount(1, $errors);
        $error = reset($errors);
        self::assertSame(
            $validatedValue,
            $error->extraDetails['value']
        );
    }
}
