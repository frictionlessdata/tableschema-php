<?php

namespace frictionlessdata\tableschema\tests;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use PHPUnit\Framework\TestCase;
use frictionlessdata\tableschema\Fields\FieldsFactory;

class FieldTypesTest extends TestCase
{
    const ERROR = '~~ERROR~~';

    public function testAny()
    {
        $this->assertFieldTestData('any', [
            ['default', 1, 1],
            ['default', '1', '1'],
            ['default', '3.14', '3.14'],
            ['default', true, true],
            ['default', '', ''],
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]],
                null, null, ], // any field has no empty value, so required without enum is meaningless
            [(object) [
                'format' => 'default',
                'constraints' => (object) ['required' => true, 'enum' => ['test', 1, false]],
            ], null, self::ERROR],
            [(object) [
                'format' => 'default',
                'constraints' => (object) ['required' => true, 'enum' => ['test', 1, false]],
            ], 'FOO', self::ERROR],
            [(object) [
                'format' => 'default',
                'constraints' => (object) ['required' => true, 'enum' => ['test', 1, false]],
            ], false, false],
            [(object) [
                'format' => 'default',
                'constraints' => (object) ['required' => true, 'enum' => ['test', 1, null, false]],
            ], null, null],
        ]);
    }

    public function testArray()
    {
        $this->assertFieldTestData('array', [
            ['default', [], []],
            ['default', '[]', []],
            ['default', ['key', 'value'], ['key', 'value']],
            ['default', '["key", "value"]', ['key', 'value']],
            ['default', (object) ['key' => 'value'], self::ERROR],
            ['default', '{"key": "value"}', self::ERROR],
            ['default', 'string', self::ERROR],
            ['default', 1, self::ERROR],
            ['default', '3.14', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]],
                null, self::ERROR, ],
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]],
                [], [], ],
            // enum
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => [[1, 2], ['foo', 'bar']]],
            ], [], self::ERROR],
            [(object) [
                'format' => 'default', 'constraints' => (object) [
                    'required' => true,
                    'enum' => [[1, 2], ['foo', 'bar']], ],
            ], '[1,2]', [1, 2]],
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => [[1, 2], ['foo', 'bar']]],
            ], [1, 2], [1, 2]],
            // minLength / maxLength
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1]], [], self::ERROR],
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1]], [1], [1]],
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1]], [1, 2], [1, 2]],
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1]], 'invalid', self::ERROR],
            [(object) ['format' => 'default', 'constraints' => (object) ['maxLength' => 1]], [], []],
            [(object) ['format' => 'default', 'constraints' => (object) ['maxLength' => 1]], [1], [1]],
            [(object) ['format' => 'default', 'constraints' => (object) ['maxLength' => 1]], [1, 2], self::ERROR],
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1, 'maxLength' => 1]],
                [1, 2], self::ERROR, ],
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1, 'maxLength' => 1]],
                [1], [1], ],
            [(object) ['format' => 'default', 'constraints' => (object) ['minLength' => 1, 'maxLength' => 1]],
                [], self::ERROR, ],
        ]);
    }

    public function testBoolean()
    {
        $this->assertFieldTestData('boolean', [
            ['default', true, true],
            [(object) ['format' => 'default', 'trueValues' => ['yes']], 'yes', true],
            ['default', 'y', self::ERROR],
            ['default', 'true', true],
            ['default', 't', self::ERROR],
            ['default', '1', true],
            ['default', 'YES', self::ERROR],
            ['default', 'Yes', self::ERROR],
            ['default', false, false],
            ['default', 'no', self::ERROR],
            ['default', 'n', self::ERROR],
            ['default', 'false', false],
            [(object) ['format' => 'default', 'falseValues' => ['f']], 'f', false],
            ['default', '0', false],
            ['default', 'NO', self::ERROR],
            ['default', 'No', self::ERROR],
            ['default', 0, self::ERROR],
            ['default', 1, self::ERROR],
            ['default', '3.14', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => [false]],
            ], true, self::ERROR],
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => [false]],
            ], false, false],
        ]);
    }

    public function testDate()
    {
        $this->assertFieldTestData('date', [
            ['default', '2019-01-01', Carbon::create(2019, 1, 1, 0, 0, 0)],
            ['default', '10th Jan 1969', self::ERROR],
            ['default', 'invalid', self::ERROR],
            ['default', true, self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            ['any', '2019-01-01', Carbon::create(2019, 1, 1, 0, 0, 0)],
            ['any', '10th Jan 1969', Carbon::create(1969, 1, 10, 0, 0, 0)],
            ['any', '10th Jan nineteen sixty nine', self::ERROR],
            ['any', 'invalid', self::ERROR],
            ['any', true, self::ERROR],
            ['%d/%m/%y', '21/11/06', Carbon::create(2006, 11, 21, 0, 0, 0)],
            ['%y/%m/%d', '21/11/06 16:30', self::ERROR],
            ['%d/%m/%y', 'invalid', self::ERROR],
            ['%d/%m/%y', true, self::ERROR],
            ['%d/%m/%y', '', self::ERROR],  // missingValues is handled at the schema level,
            ['invalid', '21/11/06 16:30', self::ERROR],
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['2019-01-01']],
            ], '2019-01-01', Carbon::create(2019, 1, 1, 0, 0, 0)],
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['2019-01-01']],
            ], '2019-01-02', self::ERROR],
        ]);
    }

    public function testDatetime()
    {
        $this->assertFieldTestData('datetime', [
            ['default', '2014-01-01T06:00:00Z', Carbon::create(2014, 1, 1, 6, 0, 0, 'UTC')],
            ['default', 'Mon 1st Jan 2014 9 am', self::ERROR],
            ['default', 'invalid', self::ERROR],
            ['default', true, self::ERROR],
            ['default', 'xxx-yy-zzTfo-ob:arZ', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            ['any', Carbon::create(2014, 1, 1, 6), Carbon::create(2014, 1, 1, 6, 0, 0, 'UTC')],
            ['any', '10th Jan 1969 9 am', Carbon::create(1969, 1, 10, 9, 0, 0, 'UTC')],
            ['any', 'invalid', self::ERROR],
            ['any', true, self::ERROR],
            ['%d/%m/%y %H:%M', '21/11/06 16:30', Carbon::create(2006, 11, 21, 16, 30, 0)],
            ['%H:%M %d/%m/%y', '21/11/06 16:30', self::ERROR],
            ['%d/%m/%y %H:%M', 'invalid', self::ERROR],
            ['%d/%m/%y %H:%M', true, self::ERROR],
            ['%d/%m/%y %H:%M', '', self::ERROR],  // missingValues is handled at the schema level,
            ['invalid', '21/11/06 16:30', self::ERROR],
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['2014-01-01T06:00:00Z']],
            ], '2014-01-01T06:00:00Z', Carbon::create(2014, 1, 1, 6, 0, 0, 'UTC')],
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['2014-01-01T06:00:00Z']],
            ], '2014-01-01T06:01:00Z', self::ERROR],
        ]);
    }

    public function testDuration()
    {
        $this->assertFieldTestData('duration', [
            ['default', 'P1Y10M3DT5H11M7S', new CarbonInterval(1, 10, 0, 3, 5, 11, 7)],
            ['default', 'P1Y', new CarbonInterval(1)],
            ['default', 'P1M', new CarbonInterval(0, 1)],
            ['default', 'P1M1Y', self::ERROR],
            ['default', 'P-1Y', self::ERROR],
            ['default', 'year', self::ERROR],
            ['default', true, self::ERROR],
            ['default', false, self::ERROR],
            ['default', 1, self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            ['default', [], self::ERROR],
            ['default', (object) [], self::ERROR],
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['P1Y10M3DT5H11M7S']],
            ], 'P1Y10M3DT5H11M7S', new CarbonInterval(1, 10, 0, 3, 5, 11, 7)],
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['P1Y10M3DT5H11M7S']],
            ], 'P1Y10M3DT5H11M8S', self::ERROR],
        ]);
    }

    public function testGeojson()
    {
        $this->assertFieldTestData('geojson', [
            [
                'default',
                (object) ['properties' => (object) ['Ã' => 'Ã'], 'type' => 'Feature', 'geometry' => null],
                (object) ['properties' => (object) ['Ã' => 'Ã'], 'type' => 'Feature', 'geometry' => null],
            ],
            [
                'default',
                '{"geometry": null, "type": "Feature", "properties": {"\\u00c3": "\\u00c3"}}',
                (object) ['properties' => (object) ['Ã' => 'Ã'], 'type' => 'Feature', 'geometry' => null],
            ],
            [
                'default',
                (object) ['coordinates' => [0, 0, 0], 'type' => 'Point'],
                (object) ['coordinates' => [0, 0, 0], 'type' => 'Point'],
            ],
            ['default', 'string', self::ERROR],
            ['default', 1, self::ERROR],
            ['default', '3.14', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            ['default', (object) [], self::ERROR],
            ['default', '{}', self::ERROR],
            [
                'topojson',
                (object) ['type' => 'LineString', 'arcs' => [42]],
                (object) ['type' => 'LineString', 'arcs' => [42]],
            ],
            [
                'topojson',
                '{"type": "LineString", "arcs": [42]}',
                (object) ['type' => 'LineString', 'arcs' => [42]],
            ],
            ['topojson', 'string', self::ERROR],
            ['topojson', 1, self::ERROR],
            ['topojson', '3.14', self::ERROR],
            ['topojson', '', self::ERROR],
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['{"geometry": null, "type": "Feature", "properties": {"\\u00c3": "\\u00c3"}}']],
            ], '{"geometry": null, "type": "Feature", "properties": {"\\u00c3": "\\u00c3"}}', (object) ['properties' => (object) ['Ã' => 'Ã'], 'type' => 'Feature', 'geometry' => null]],
            [(object) [
                'format' => 'default', 'constraints' => (object) ['enum' => ['{"geometry": null, "type": "Feature", "properties": {"\\u00c3": "\\u00c3"}}']],
            ], '{"geometry": null, "type": "Feature", "properties": {"\\u00c3": "\\u00c4"}}', self::ERROR],
        ]);
    }

    public function testGeopoint()
    {
        $this->assertFieldTestData('geopoint', [
            ['default',  [180, 90],  self::ERROR],
            ['default', [180, 90],  self::ERROR],
            ['default', '180,90',  [180, 90]],
            ['default', '180, -90',  [180, -90]],
            ['default', (object) ['lon' => 180, 'lat' => 90], self::ERROR],
            ['default', '181,90', self::ERROR],
            ['default', '0,91', self::ERROR],
            ['default', 'string', self::ERROR],
            ['default', 1, self::ERROR],
            ['default', '3.14', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            ['array',  [180, 90],  [180, 90]],
            ['array', [180, 90],  [180, 90]],
            ['array', '[180, -90]',  [180, -90]],
            ['array', (object) ['lon' => 180, 'lat' => 90], self::ERROR],
            ['array', [181, 90], self::ERROR],
            ['array', [0, 91], self::ERROR],
            ['array', '180,90', self::ERROR],
            ['array', 'string', self::ERROR],
            ['array', 1, self::ERROR],
            ['array', '3.14', self::ERROR],
            ['array', '', self::ERROR],  // missingValues is handled at the schema level,
            ['object', (object) ['lon' => 180, 'lat' => 90],  [180, 90]],
            ['object', '{"lon":180, "lat":90}',  [180, 90]],
            ['object', '{"lat":90, "lon":180, "foo": "bar"}',  [180, 90]],
            ['object', '[180, -90]', self::ERROR],
            ['object', (object) ['lon' => 181, 'lat' => 90], self::ERROR],
            ['object', (object) ['lon' => 180, 'lat' => -91], self::ERROR],
            ['object', [180, -90], self::ERROR],
            ['object', '180,90', self::ERROR],
            ['object', 'string', self::ERROR],
            ['object', 1, self::ERROR],
            ['object', '3.14', self::ERROR],
            ['object', '', self::ERROR],  // missingValues is handled at the schema level,
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'array', 'constraints' => (object) ['enum' => ['[180, -90]']]], '[180, -90]', [180, -90]],
            [(object) ['format' => 'array', 'constraints' => (object) ['enum' => ['[180, -90]']]], '[171, -90]', self::ERROR],
        ]);
    }

    public function testInteger()
    {
        $this->assertFieldTestData('integer', [
            ['default', 1, 1],
            ['default', '1', 1],
            ['default', '3.14', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [0, '1']]], '0', 0],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [0, '1']]], '2', self::ERROR],
        ]);
    }

    public function testNumber()
    {
        $this->assertFieldTestData('number', [
            [(object) ['format' => 'default'], 1, 1.0],
            [(object) ['format' => 'default'], 1, 1.0],
            [(object) ['format' => 'default'], 1.0, 1.0],
            [(object) ['format' => 'default'], '1', 1.0],
            [(object) ['format' => 'default'], '10.00', 10.0],
            [(object) ['format' => 'default'], '10.50', 10.5],
            [(object) ['format' => 'default'], '100%', 1.0],
            [(object) ['format' => 'default'], '1000‰', self::ERROR],  // spec only supports percent sign
            [(object) ['format' => 'default'], '-1000', -1000.0],
            [(object) ['format' => 'default', 'groupChar' => ','], '1,000', 1000.0],
            [(object) ['format' => 'default', 'groupChar' => ','], '10,000.00', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => ','], '10,000,000.50', 10000000.5],
            [(object) ['format' => 'default', 'groupChar' => '#'], '10#000.00', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => '#'], '10#000#000.50', 10000000.5],
            [(object) ['format' => 'default', 'groupChar' => '#'], '10.50', 10.5],
            [(object) ['format' => 'default', 'groupChar' => '#'], '1#000', 1000.0],
            [(object) ['format' => 'default', 'groupChar' => '#', 'decimalChar' => '@'], '10#000@00', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => '#', 'decimalChar' => '@'], '10#000#000@50', 10000000.5],
            [(object) ['format' => 'default', 'groupChar' => '#', 'decimalChar' => '@'], '10@50', 10.5],
            [(object) ['format' => 'default', 'groupChar' => '#', 'decimalChar' => '@'], '1#000', 1000.0],
            [(object) ['format' => 'default', 'groupChar' => ',', 'currency' => true], '10,000.00', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => ',', 'currency' => true], '10,000,000.00', 10000000.0],
            [(object) ['format' => 'default', 'currency' => true], '$10000.00', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => ',', 'currency' => true], '  10,000.00 €', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => ' ', 'decimalChar' => ','], '10 000,00', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => ' ', 'decimalChar' => ','], '10 000 000,00', 10000000.0],
            [(object) ['format' => 'default', 'groupChar' => ' ', 'decimalChar' => ',', 'currency' => true], '10000,00 ₪', 10000.0],
            [(object) ['format' => 'default', 'groupChar' => ' ', 'decimalChar' => ',', 'currency' => true], '  10 000,00 £', 10000.0],
            [(object) ['format' => 'default'], '10,000a.00', self::ERROR],
            [(object) ['format' => 'default'], '10+000.00', self::ERROR],
            [(object) ['format' => 'default'], '$10:000.00', self::ERROR],
            [(object) ['format' => 'default'], 'string', self::ERROR],
            [(object) ['format' => 'default'], '', self::ERROR],  // missingValues is handled at the schema level,
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [0.5, '1.6']]], '1.6', 1.6],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [0.5, '1.6']]], '0.55', self::ERROR],
        ]);
    }

    public function testObject()
    {
        $this->assertFieldTestData('object', [
            ['default', (object) [], (object) []],
            ['default', '{}', (object) []],
            ['default', (object) ['key' => 'value'], (object) ['key' => 'value']],
            ['default', '{"key": "value"}', (object) ['key' => 'value']],
            ['default', '["key", "value"]', self::ERROR],
            ['default', 'string', self::ERROR],
            ['default', 1, self::ERROR],
            ['default', '3.14', self::ERROR],
            ['default', '', self::ERROR],  // missingValues is handled at the schema level,
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => ['{"foo":"bar"}']]], '{"foo":"bar"}', (object) ['foo' => 'bar']],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => ['{"foo":"bar"}']]], '{"foox":"bar"}', self::ERROR],
        ]);
    }

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
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], '', ''],
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => ['foobar']]], 'foobar', 'foobar'],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => ['foobar']]], 'foobarx', self::ERROR],
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
            ['default', '', self::ERROR],
            ['any', '06:00:00', [6, 0, 0]],
            ['any', '3:00 am', [3, 0, 0]],
            ['any', 'some night', self::ERROR],
            ['any', 'invalid', self::ERROR],
            ['any', true, self::ERROR],
            ['%H:%M', '06:00', [6, 0, 0]],
            ['%H:%M', '3:00 am', self::ERROR],
            ['%H:%M', 'some night', self::ERROR],
            ['%H:%M', 'invalid', self::ERROR],
            ['%H:%M', true, self::ERROR],
            ['%H:%M', '', self::ERROR],
            ['invalid', '', self::ERROR],
            ['default', '06:35:21', [6, 35, 21]],
            ['any', '06:35:21', [6, 35, 21]],
            ['any', '06:35', [6, 35, 0]],
            ['any', '6', self::ERROR],
            ['any', '3 am', [3, 0, 0]],
            ['%H:%M:%S', '06:35:21', [6, 35, 21]],
            ['%H:%M', '06:35:21', self::ERROR],
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => ['06:00:00']]], '06:00:00', [6, 0, 0]],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => ['06:00:00']]], '06:01:00', self::ERROR],
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
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [2000]]], '2000', 2000],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [2000]]], '2001', self::ERROR],
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
            ['default', '', self::ERROR],
            // required
            [(object) ['format' => 'default', 'constraints' => (object) ['required' => true]], null, self::ERROR],
            // enum
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [[2000, 10]]]], '2000-10', [2000, 10]],
            [(object) ['format' => 'default', 'constraints' => (object) ['enum' => [[2000, 10]]]], '2000-11', self::ERROR],
        ]);
    }

    protected function assertFieldTestData($fieldType, $testData)
    {
        foreach ($testData as $testLine) {
            if (!isset($testLine[3])) {
                $testLine[3] = null;
            }
            list($format, $inputValue, $expectedCastValue, $expectedInferType) = $testLine;
            if (is_object($format)) {
                $descriptor = $format;
                $descriptor->type = $fieldType;
            } else {
                $descriptor = (object) ['type' => $fieldType, 'format' => $format];
            }
            $assertMessage = 'descriptor='.json_encode($descriptor).", input='".json_encode($inputValue)."', expected='".json_encode($expectedCastValue)."'";
            if (!isset($descriptor->name)) {
                $descriptor->name = 'unknown';
            }
            $field = FieldsFactory::field($descriptor);
            if ($expectedCastValue === self::ERROR) {
                $this->assertTrue(count($field->validateValue($inputValue)) > 0, $assertMessage);
            } elseif (is_object($expectedCastValue)) {
                $this->assertEquals($expectedCastValue, $field->castValue($inputValue), $assertMessage);
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
