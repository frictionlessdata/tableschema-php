<?php

declare(strict_types=1);

namespace frictionlessdata\tableschema\tests\Fields;

use frictionlessdata\tableschema\Fields\TimeField;
use PHPUnit\Framework\TestCase;

/**
 * @covers \frictionlessdata\tableschema\Fields\TimeField
 */
class TimeFieldTest extends TestCase
{
    /**
     * @dataProvider provideDateFormatTestData
     */
    public function testParseDateFormat(array $expectedTimeParts, string $format, $dateValue): void
    {
        $descriptor = (object) ['format' => $format];
        $sut = new TimeField($descriptor);

        self::assertSame(
            $expectedTimeParts,
            $sut->castValue($dateValue)
        );
    }

    public static function provideDateFormatTestData(): iterable
    {
        yield [[13, 12, 34], '%H:%M:%S', '13:12:34'];
    }
}
