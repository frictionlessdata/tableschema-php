<?php

namespace frictionlessdata\tableschema\tests;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use frictionlessdata\tableschema\Fields\FieldsFactory;

class FieldTypesTest extends TestCase
{
    const ERROR = '~~ERROR~~';

    /*

    any

    ('default', 1, 1),
    ('default', '1', '1'),
    ('default', '3.14', '3.14'),
    ('default', True, True),
    ('default', '', ''),

    array

    ('default', [], []),
    ('default', '[]', []),
    ('default', ['key', 'value'], ['key', 'value']),
    ('default', '["key", "value"]', ['key', 'value']),
    ('default', {'key': 'value'}, ERROR),
    ('default', '{"key": "value"}', ERROR),
    ('default', 'string', ERROR),
    ('default', 1, ERROR),
    ('default', '3.14', ERROR),
    ('default', '', ERROR),

    boolean
    ('default', True, True),
    ('default', 'yes', True),
    ('default', 'y', True),
    ('default', 'true', True),
    ('default', 't', True),
    ('default', '1', True),
    ('default', 'YES', True),
    ('default', 'Yes', True),
    ('default', False, False),
    ('default', 'no', False),
    ('default', 'n', False),
    ('default', 'false', False),
    ('default', 'f', False),
    ('default', '0', False),
    ('default', 'NO', False),
    ('default', 'No', False),
    ('default', 0, ERROR),
    ('default', 1, ERROR),
    ('default', '3.14', ERROR),
    ('default', '', ERROR),

    date
    ('default', date(2019, 1, 1), date(2019, 1, 1)),
    ('default', '2019-01-01', date(2019, 1, 1)),
    ('default', '10th Jan 1969', ERROR),
    ('default', 'invalid', ERROR),
    ('default', True, ERROR),
    ('default', '', ERROR),
    ('any', date(2019, 1, 1), date(2019, 1, 1)),
    ('any', '2019-01-01', date(2019, 1, 1)),
    ('any', '10th Jan 1969', date(1969, 1, 10)),
    ('any', '10th Jan nineteen sixty nine', ERROR),
    ('any', 'invalid', ERROR),
    ('any', True, ERROR),
    ('any', '', ERROR),
    ('%d/%m/%y', date(2019, 1, 1), date(2019, 1, 1)),
    ('%d/%m/%y', '21/11/06', date(2006, 11, 21)),
    ('%y/%m/%d','21/11/06 16:30', ERROR),
    ('%d/%m/%y','invalid', ERROR),
    ('%d/%m/%y',True, ERROR),
    ('%d/%m/%y', '', ERROR),
    ('invalid','21/11/06 16:30', ERROR),
    # Deprecated
    ('fmt:%d/%m/%y', date(2019, 1, 1), date(2019, 1, 1)),
    ('fmt:%d/%m/%y', '21/11/06', date(2006, 11, 21)),
    ('fmt:%y/%m/%d','21/11/06 16:30', ERROR),
    ('fmt:%d/%m/%y','invalid', ERROR),
    ('fmt:%d/%m/%y',True, ERROR),
    ('fmt:%d/%m/%y', '', ERROR),

    datetime
    ('default', datetime(2014, 1, 1, 6), datetime(2014, 1, 1, 6)),
    ('default', '2014-01-01T06:00:00Z', datetime(2014, 1, 1, 6)),
    ('default', 'Mon 1st Jan 2014 9 am', ERROR),
    ('default', 'invalid', ERROR),
    ('default', True, ERROR),
    ('default', '', ERROR),
    ('any', datetime(2014, 1, 1, 6), datetime(2014, 1, 1, 6)),
    ('any', '10th Jan 1969 9 am', datetime(1969, 1, 10, 9)),
    ('any', 'invalid', ERROR),
    ('any', True, ERROR),
    ('any', '', ERROR),
    ('%d/%m/%y %H:%M', datetime(2006, 11, 21, 16, 30), datetime(2006, 11, 21, 16, 30)),
    ('%d/%m/%y %H:%M', '21/11/06 16:30', datetime(2006, 11, 21, 16, 30)),
    ('%H:%M %d/%m/%y', '21/11/06 16:30', ERROR),
    ('%d/%m/%y %H:%M', 'invalid', ERROR),
    ('%d/%m/%y %H:%M', True, ERROR),
    ('%d/%m/%y %H:%M', '', ERROR),
    ('invalid', '21/11/06 16:30', ERROR),
    # Deprecated
    ('fmt:%d/%m/%y %H:%M', datetime(2006, 11, 21, 16, 30), datetime(2006, 11, 21, 16, 30)),
    ('fmt:%d/%m/%y %H:%M', '21/11/06 16:30', datetime(2006, 11, 21, 16, 30)),
    ('fmt:%H:%M %d/%m/%y', '21/11/06 16:30', ERROR),
    ('fmt:%d/%m/%y %H:%M', 'invalid', ERROR),
    ('fmt:%d/%m/%y %H:%M', True, ERROR),
    ('fmt:%d/%m/%y %H:%M', '', ERROR),

    duration
    ('default', isodate.Duration(years=1), isodate.Duration(years=1)),
    ('default', 'P1Y10M3DT5H11M7S',
         isodate.Duration(years=1, months=10, days=3, hours=5, minutes=11, seconds=7)),
    ('default', 'P1Y', isodate.Duration(years=1)),
    ('default', 'P1M', isodate.Duration(months=1)),
    ('default', 'P1M1Y', ERROR),
    ('default', 'P-1Y', ERROR),
    ('default', 'year', ERROR),
    ('default', True, ERROR),
    ('default', False, ERROR),
    ('default', 1, ERROR),
    ('default', '', ERROR),
    ('default', [], ERROR),
    ('default', {}, ERROR),

    geojson
    ('default',
        {'properties': {'Ã': 'Ã'}, 'type': 'Feature', 'geometry': None},
        {'properties': {'Ã': 'Ã'}, 'type': 'Feature', 'geometry': None}),
    ('default',
        '{"geometry": null, "type": "Feature", "properties": {"\\u00c3": "\\u00c3"}}',
        {'properties': {'Ã': 'Ã'}, 'type': 'Feature', 'geometry': None}),
    ('default', {'coordinates': [0, 0, 0], 'type': 'Point'}, ERROR),
    ('default', 'string', ERROR),
    ('default', 1, ERROR),
    ('default', '3.14', ERROR),
    ('default', '', ERROR),
    ('default', {}, ERROR),
    ('default', '{}', ERROR),
    ('topojson',
        {'type': 'LineString', 'arcs': [42]},
        {'type': 'LineString', 'arcs': [42]}),
    ('topojson',
        '{"type": "LineString", "arcs": [42]}',
        {'type': 'LineString', 'arcs': [42]}),
    ('topojson', 'string', ERROR),
    ('topojson', 1, ERROR),
    ('topojson', '3.14', ERROR),
    ('topojson', '', ERROR),

    geopoint
    ('default', (180, 90), (180, 90)),
    ('default', [180, 90], (180, 90)),
    ('default', '180,90', (180, 90)),
    ('default', '180, -90', (180, -90)),
    ('default', {'lon': 180, 'lat': 90}, ERROR),
    ('default', '181,90', ERROR),
    ('default', '0,91', ERROR),
    ('default', 'string', ERROR),
    ('default', 1, ERROR),
    ('default', '3.14', ERROR),
    ('default', '', ERROR),
    ('array', (180, 90), (180, 90)),
    ('array', [180, 90], (180, 90)),
    ('array', '[180, -90]', (180, -90)),
    ('array', {'lon': 180, 'lat': 90}, ERROR),
    ('array', [181, 90], ERROR),
    ('array', [0, 91], ERROR),
    ('array', '180,90', ERROR),
    ('array', 'string', ERROR),
    ('array', 1, ERROR),
    ('array', '3.14', ERROR),
    ('array', '', ERROR),
    ('object', {'lon': 180, 'lat': 90}, (180, 90)),
    ('object', '{"lon": 180, "lat": 90}', (180, 90)),
    ('object', '[180, -90]', ERROR),
    ('object', {'lon': 181, 'lat': 90}, ERROR),
    ('object', {'lon': 180, 'lat': -91}, ERROR),
    ('object', [180, -90], ERROR),
    ('object', '180,90', ERROR),
    ('object', 'string', ERROR),
    ('object', 1, ERROR),
    ('object', '3.14', ERROR),
    ('object', '', ERROR),

    integer
    ('default', 1, 1),
    ('default', '1', 1),
    ('default', '3.14', ERROR),
    ('default', '', ERROR),

    number
    ('default', Decimal(1), {}, Decimal(1)),
    ('default', 1, {}, Decimal(1)),
    ('default', 1.0, {}, Decimal(1)),
    ('default', '1', {}, Decimal(1)),
    ('default', '10.00', {}, Decimal(10)),
    ('default', '10.50', {}, Decimal(10.5)),
    ('default', '100%', {}, Decimal(1)),
    ('default', '1000‰', {}, Decimal(10)),
    ('default', '-1000', {}, Decimal(-1000)),
    ('default', '1,000', {'groupChar': ','}, Decimal(1000)),
    ('default', '10,000.00', {'groupChar': ','}, Decimal(10000)),
    ('default', '10,000,000.50', {'groupChar': ','}, Decimal(10000000.5)),
    ('default', '10#000.00', {'groupChar': '#'}, Decimal(10000)),
    ('default', '10#000#000.50', {'groupChar': '#'}, Decimal(10000000.5)),
    ('default', '10.50', {'groupChar': '#'}, Decimal(10.5)),
    ('default', '1#000', {'groupChar': '#'}, Decimal(1000)),
    ('default', '10#000@00', {'groupChar': '#', 'decimalChar': '@'}, Decimal(10000)),
    ('default', '10#000#000@50', {'groupChar': '#', 'decimalChar': '@'}, Decimal(10000000.5)),
    ('default', '10@50', {'groupChar': '#', 'decimalChar': '@'}, Decimal(10.5)),
    ('default', '1#000', {'groupChar': '#', 'decimalChar': '@'}, Decimal(1000)),
    ('default', '10,000.00', {'groupChar': ',', 'currency': True}, Decimal(10000)),
    ('default', '10,000,000.00', {'groupChar': ',', 'currency': True}, Decimal(10000000)),
    ('default', '$10000.00', {'currency': True}, Decimal(10000)),
    ('default', '  10,000.00 €', {'groupChar': ',', 'currency': True}, Decimal(10000)),
    ('default', '10 000,00', {'groupChar': ' ', 'decimalChar': ','}, Decimal(10000)),
    ('default', '10 000 000,00', {'groupChar': ' ', 'decimalChar': ','}, Decimal(10000000)),
    ('default', '10000,00 ₪', {'groupChar': ' ', 'decimalChar': ',', 'currency': True}, Decimal(10000)),
    ('default', '  10 000,00 £', {'groupChar': ' ', 'decimalChar': ',', 'currency': True}, Decimal(10000)),
    ('default', '10,000a.00', {}, ERROR),
    ('default', '10+000.00', {}, ERROR),
    ('default', '$10:000.00', {}, ERROR),
    ('default', 'string', {}, ERROR),
    ('default', '', {}, ERROR),

    object
    ('default', {}, {}),
    ('default', '{}', {}),
    ('default', {'key': 'value'}, {'key': 'value'}),
    ('default', '{"key": "value"}', {'key': 'value'}),
    ('default', '["key", "value"]', ERROR),
    ('default', 'string', ERROR),
    ('default', 1, ERROR),
    ('default', '3.14', ERROR),
    ('default', '', ERROR),

     */
    public function testString()
    {
        $this->assertFieldTestData('string', [
            // format , input value , expected cast value, (optional) expected infer type
            ['default', 'string', 'string'],
            ['default', '', ''],
            ['default', 0, '0'],
            ['uri', 'http://google.com', 'http://google.com'],
            ['uri', 'string', self::ERROR],
            ['uri', '', self::ERROR],
            ['uri', 0, self::ERROR],
            ['email', 'name@gmail.com', 'name@gmail.com'],
            ['email', 'http://google.com', self::ERROR],
            ['email', 'string', self::ERROR],
            ['email', '', self::ERROR],
            ['email', 0, self::ERROR],
            ['binary', 'dGVzdA==', 'dGVzdA=='],
            ['binary', '', ''],
        ]);
    }

    public function testTime()
    {
        $this->assertFieldTestData('time', [
            // format , input value , expected cast value, (optional) expected infer type
            ['default', '06:00:00', [6, 0, 0]],
            ['default', '3 am', self::ERROR],
            ['default', '3.00', self::ERROR],
            ['default', 'invalid', self::ERROR],
            ['default', true, self::ERROR],
            ['default', '', null],
            ['any', '06:00:00', [6, 0, 0]],
            ['any', '3:00 am', [3, 0, 0]],
            ['any', 'some night', self::ERROR],
            ['any', 'invalid', self::ERROR],
            ['any', true, self::ERROR],
            ['any', '', null],
            ['%H:%M', '06:00', [6, 0, 0]],
            ['%H:%M', '3:00 am', self::ERROR],
            ['%H:%M', 'some night', self::ERROR],
            ['%H:%M', 'invalid', self::ERROR],
            ['%H:%M', true, self::ERROR],
            ['%H:%M', '', null],
            ['invalid', '', null],
            ['default', '06:35:21', [6, 35, 21]],
            ['any', '06:35:21', [6, 35, 21]],
            ['any', '06:35', [6, 35, 0]],
            ['any', '6', self::ERROR],
            ['any', '3 am', [3, 0, 0]],
            ['%H:%M:%S', '06:35:21', [6, 35, 21]],
            ['%H:%M', '06:35:21', self::ERROR],
        ]);
    }

    public function testYear()
    {
        $this->assertFieldTestData('year', [
            // format , input value , expected cast value, (optional) expected infer type
            ['default', 2000, 2000],
            ['default', '2000', 2000],
            ['default', 20000, 20000],
            ['default', '3.14', self::ERROR],
            ['default', '', null],
        ]);
    }

    public function testYearMonth()
    {
        $this->assertFieldTestData('yearmonth', [
            // format , input value , expected cast value, (optional) expected infer type
            ['default', [2000, 10], [2000, 10]],
            ['default', [2000, 10], [2000, 10], 'string'],
            ['default', '2000-10', [2000, 10], 'string'],
            ['default', [2000, 10, 20], self::ERROR],
            ['default', '2000-13-20', self::ERROR],
            ['default', '2000-13', self::ERROR],
            ['default', '2000-0', self::ERROR],
            ['default', '13', self::ERROR],
            ['default', -10, self::ERROR],
            ['default', 20, self::ERROR],
            ['default', '3.14', self::ERROR],
            ['default', '', null],
        ]);
    }

    protected function assertFieldTestData($fieldType, $testData)
    {
        foreach ($testData as $testLine) {
            if (!isset($testLine[3])) {
                $testLine[3] = null;
            }
            list($format, $inputValue, $expectedCastValue, $expectedInferType) = $testLine;
            $assertMessage = "format='{$format}', input='".json_encode($inputValue)."', expected='".json_encode($expectedCastValue)."'";
            $field = FieldsFactory::field((object) ['type' => $fieldType, 'format' => $format]);
            if ($expectedCastValue == self::ERROR) {
                $this->assertTrue(count($field->validateValue($inputValue)) > 0, $assertMessage);
            } else {
                $this->assertSame($expectedCastValue, $field->castValue($inputValue), $assertMessage);
            }
            $inferredType = FieldsFactory::infer($inputValue)->type();
            if ($expectedInferType) {
                $this->assertSame($expectedInferType, $inferredType, $assertMessage);
            }
        }
    }
}
