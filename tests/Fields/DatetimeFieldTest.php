<?php

declare(strict_types=1);

namespace frictionlessdata\tableschema\tests\Fields;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use frictionlessdata\tableschema\Fields\DatetimeField;
use PHPUnit\Framework\TestCase;

/**
 * @covers \frictionlessdata\tableschema\Fields\DatetimeField
 */
class DatetimeFieldTest extends TestCase
{
    /**
     * @dataProvider provideDateFormatTestData
     */
    public function testParseDateFormat(DateTimeInterface $expectedDateTime, string $format, $dateValue): void
    {
        $descriptor = (object) ['format' => $format];
        $sut = new DatetimeField($descriptor);

        $castValue = $sut->castValue($dateValue);
        self::assertInstanceOf(Carbon::class, $castValue);

        self::assertTrue(
            $castValue->eq($expectedDateTime)
        );
    }

    public static function provideDateFormatTestData(): iterable
    {
        yield [new DateTime('2023-02-28 13:12:34'), '%Y-%m-%d %H:%M:%S', '2023-02-28 13:12:34'];
    }
}
