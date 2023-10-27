<?php

declare(strict_types=1);

namespace frictionlessdata\tableschema\Utility;

use DateTimeImmutable;

final class StrptimeFormatTransformer
{
    public static function transform(string $strptimeFormat): string
    {
        return strtr(
            $strptimeFormat,
            [
                // Day
                '%d' => 'd', // 09
                '%e' => 'j', // 9

                // Month
                '%m' => 'm', // 02
                '%b' => 'M', // Feb
                '%B' => 'F', // February

                // Year
                '%Y' => 'Y', // 2023
                '%y' => 'y', // 23

                // Hour
                '%H' => 'H', // 00 to 23
                '%k' => 'G', // 0 to 23
                '%I' => 'h', // 00 to 12
                '%l' => 'h', // 0 to 12
                '%p' => 'A', // AM / PM
                '%P' => 'a', // am / pm

                // Minute
                '%M' => 'i',

                // Second
                '%S' => 's',

                // Date
                '%D' => 'm/d/y',
                '%F' => 'Y-m-d',

                // Time
                '%r' => 'h:i:s A',
                '%R' => 'H:i',
                '%T' => 'H:i:s',
                '%s' => 'U',
            ]
        );
    }
}
