<?php

declare(strict_types=1);

namespace Utility;

use frictionlessdata\tableschema\Utility\StrptimeFormatTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \frictionlessdata\tableschema\Utility\StrptimeFormatTransformer
 */
class StrptimeFormatTransformerTestCase extends TestCase
{
    /**
     * @dataProvider provideStrptimeFormatTestData
     */
    public function testStrptimeFormatConversion($dateTimeArr, string $strptimeFormat, string $time): void
    {
        $parsedDateTime = date_parse_from_format(
            StrptimeFormatTransformer::transform($strptimeFormat),
            $time
        );
        self::assertSame(
            $dateTimeArr,
            array_intersect_key(
                $parsedDateTime,
                array_flip(['year', 'month', 'day', 'hour', 'minute', 'second'])
            )
        );

        self::assertEquivalentStrptime(
            @strptime($time, $strptimeFormat),
            $parsedDateTime
        );
    }

    public static function provideStrptimeFormatTestData(): iterable
    {
        // Day
        yield [
            ['year' => false, 'month' => false, 'day' => 3, 'hour' => false, 'minute' => false, 'second' => false],
            '%d',
            '03',
        ];
        yield [
            ['year' => false, 'month' => false, 'day' => 3, 'hour' => false, 'minute' => false, 'second' => false],
            '%e',
            '3',
        ];

        // Month
        yield [
            ['year' => false, 'month' => 2, 'day' => false, 'hour' => false, 'minute' => false, 'second' => false],
            '%m',
            '02',
        ];
        yield [
            ['year' => false, 'month' => 2, 'day' => false, 'hour' => false, 'minute' => false, 'second' => false],
            '%b',
            'Feb',
        ];
        yield [
            ['year' => false, 'month' => 2, 'day' => false, 'hour' => false, 'minute' => false, 'second' => false],
            '%B',
            'February',
        ];

        // Year
        yield [
            ['year' => 2023, 'month' => false, 'day' => false, 'hour' => false, 'minute' => false, 'second' => false],
            '%Y',
            '2023',
        ];
        yield [
            ['year' => 2023, 'month' => false, 'day' => false, 'hour' => false, 'minute' => false, 'second' => false],
            '%y',
            '23',
        ];

        // Hour
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 9, 'minute' => 0, 'second' => 0],
            '%H',
            '09',
        ];
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 9, 'minute' => 0, 'second' => 0],
            '%k',
            '9',
        ];
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 23, 'minute' => 0, 'second' => 0],
            '%I %p',
            '11 PM',
        ];
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 13, 'minute' => 0, 'second' => 0],
            '%l %p',
            '1 PM',
        ];
        // Not understood by strptime
//        yield [
//            ['year' => false, 'month' => false, 'day' => false, 'hour' => 13, 'minute' => 0, 'second' => 0],
//            '%l %P',
//            '1 pm',
//        ];

        // Minute
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 0, 'minute' => 9, 'second' => 0],
            '%M',
            '09',
        ];

        // Second
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 0, 'minute' => 0, 'second' => 9],
            '%S',
            '09',
        ];

        // Date
        yield [
            ['year' => 2009, 'month' => 2, 'day' => 5, 'hour' => false, 'minute' => false, 'second' => false],
            '%D',
            '02/05/09',
        ];
        yield [
            ['year' => 2009, 'month' => 2, 'day' => 5, 'hour' => false, 'minute' => false, 'second' => false],
            '%F',
            '2009-02-05',
        ];

        // Time
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 21, 'minute' => 34, 'second' => 17],
            '%r',
            '09:34:17 PM',
        ];
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 13, 'minute' => 23, 'second' => 0],
            '%R',
            '13:23',
        ];
        yield [
            ['year' => false, 'month' => false, 'day' => false, 'hour' => 13, 'minute' => 23, 'second' => 34],
            '%T',
            '13:23:34',
        ];
        yield [
            ['year' => 2023, 'month' => 10, 'day' => 27, 'hour' => 4, 'minute' => 32, 'second' => 44],
            '%s',
            '1698381164',
        ];
    }

    private static function assertEquivalentStrptime(array $strptime, array $actual): void
    {
        if (0 === $strptime['tm_year']) {
            self::assertFalse($actual['year']);
        } else {
            self::assertSame($strptime['tm_year'] + 1900, $actual['year']);
        }

        if (0 === $strptime['tm_mon']) {
            self::assertFalse($actual['month']);
        } else {
            self::assertSame($strptime['tm_mon'] + 1, $actual['month']);
        }

        if (0 === $strptime['tm_mday']) {
            self::assertFalse($actual['day']);
        } else {
            self::assertSame($strptime['tm_mday'], $actual['day']);
        }
    }
}
