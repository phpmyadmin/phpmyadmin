<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometryCollection;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisGeometryCollection
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisGeometryCollectionTest extends GisGeomTestCase
{
    /**
     * Data provider for testScaleRow() test case
     *
     * @return array<array{string, ScaleData}>
     */
    public static function providerForTestScaleRow(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                new ScaleData(45, 10, 45, 10),
            ],
        ];
    }

    /**
     * test scaleRow method
     *
     * @param string    $spatial spatial data of a row
     * @param ScaleData $minMax  expected results
     *
     * @dataProvider providerForTestScaleRow
     */
    public function testScaleRow(string $spatial, ScaleData $minMax): void
    {
        $object = GisGeometryCollection::singleton();
        $this->assertEquals($minMax, $object->scaleRow($spatial));
    }

    /**
     * Test for generateWkt
     *
     * @param array<mixed> $gisData
     * @param int          $index   index in $gis_data
     * @param string|null  $empty   empty parameter
     * @param string       $output  expected output
     *
     * @dataProvider providerForTestGenerateWkt
     */
    public function testGenerateWkt(array $gisData, int $index, string|null $empty, string $output): void
    {
        $object = GisGeometryCollection::singleton();
        $this->assertEquals($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * Data provider for testGenerateWkt() test case
     *
     * @return array<array{array<mixed>, int, string|null, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp1 = [
            0 => [
                'gis_type' => 'LINESTRING',
                'LINESTRING' => ['no_of_points' => 2, 0 => ['x' => 5.02,'y' => 8.45], 1 => ['x' => 6.14,'y' => 0.15]],
            ],
        ];

        return [[$temp1, 0, null, 'GEOMETRYCOLLECTION(LINESTRING(5.02 8.45,6.14 0.15))']];
    }

    /**
     * test generateParams method
     *
     * @param string       $wkt    point in WKT form
     * @param array<mixed> $params expected output array
     *
     * @dataProvider providerForTestGenerateParams
     */
    public function testGenerateParams(string $wkt, array $params): void
    {
        $object = GisGeometryCollection::singleton();
        $this->assertEquals($params, $object->generateParams($wkt));
    }

    /**
     * Data provider for testGenerateParams() test case
     *
     * @return array<array{string, array<mixed>}>
     */
    public static function providerForTestGenerateParams(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION('
                . 'LINESTRING(5.02 8.45,6.14 0.15)'
                . ',MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))'
                . ',MULTIPOINT(5.02 8.45,6.14 0.15)'
                . ',MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))'
                . ',POINT(5.02 8.45)'
                . ',POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'
                . ')',
                [
                    'srid' => 0,
                    'GEOMETRYCOLLECTION' => ['geom_count' => 6],
                    0 => [
                        'gis_type' => 'LINESTRING',
                        'LINESTRING' => [
                            'no_of_points' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
                        ],
                    ],
                    1 => [
                        'gis_type' => 'MULTILINESTRING',
                        'MULTILINESTRING' => [
                            'no_of_lines' => 2,
                            0 => [
                                'no_of_points' => 3,
                                0 => ['x' => 36.0, 'y' => 14.0],
                                1 => ['x' => 47.0, 'y' => 23.0],
                                2 => ['x' => 62.0, 'y' => 75.0],
                            ],
                            1 => [
                                'no_of_points' => 3,
                                0 => ['x' => 36.0, 'y' => 10.0],
                                1 => ['x' => 17.0, 'y' => 23.0],
                                2 => ['x' => 178.0, 'y' => 53.0],
                            ],
                        ],
                    ],
                    2 => [
                        'gis_type' => 'MULTIPOINT',
                        'MULTIPOINT' => [
                            'no_of_points' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
                        ],
                    ],
                    3 => [
                        'gis_type' => 'MULTIPOLYGON',
                        'MULTIPOLYGON' => [
                            'no_of_polygons' => 2,
                            0 => [
                                'no_of_lines' => 2,
                                0 => [
                                    'no_of_points' => 5,
                                    0 => ['x' => 35.0, 'y' => 10.0],
                                    1 => ['x' => 10.0, 'y' => 20.0],
                                    2 => ['x' => 15.0, 'y' => 40.0],
                                    3 => ['x' => 45.0, 'y' => 45.0],
                                    4 => ['x' => 35.0, 'y' => 10.0],
                                ],
                                1 => [
                                    'no_of_points' => 4,
                                    0 => ['x' => 20.0, 'y' => 30.0],
                                    1 => ['x' => 35.0, 'y' => 32.0],
                                    2 => ['x' => 30.0, 'y' => 20.0],
                                    3 => ['x' => 20.0, 'y' => 30.0],
                                ],
                            ],
                            1 => [
                                'no_of_lines' => 1,
                                0 => [
                                    'no_of_points' => 4,
                                    0 => ['x' => 123.0, 'y' => 0.0],
                                    1 => ['x' => 23.0, 'y' => 30.0],
                                    2 => ['x' => 17.0, 'y' => 63.0],
                                    3 => ['x' => 123.0, 'y' => 0.0],
                                ],
                            ],
                        ],
                    ],
                    4 => ['gis_type' => 'POINT', 'POINT' => ['x' => 5.02, 'y' => 8.45]],
                    5 => [
                        'gis_type' => 'POLYGON',
                        'POLYGON' => [
                            'no_of_lines' => 2,
                            0 => [
                                'no_of_points' => 5,
                                0 => ['x' => 35.0, 'y' => 10.0],
                                1 => ['x' => 10.0, 'y' => 20.0],
                                2 => ['x' => 15.0, 'y' => 40.0],
                                3 => ['x' => 45.0, 'y' => 45.0],
                                4 => ['x' => 35.0, 'y' => 10.0],
                            ],
                            1 => [
                                'no_of_points' => 4,
                                0 => ['x' => 20.0, 'y' => 30.0],
                                1 => ['x' => 35.0, 'y' => 32.0],
                                2 => ['x' => 30.0, 'y' => 20.0],
                                3 => ['x' => 20.0, 'y' => 30.0],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $object = GisGeometryCollection::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $object->prepareRowAsPng(
            'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)),'
            . 'LINESTRING(5 30,4 4))',
            'image',
            [176, 46, 224],
            ['x' => -19, 'y' => -3, 'scale' => 2.29, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/geometrycollection-expected.png';
        $fileActual = $this->testDir . '/geometrycollection-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * Test for prepareRowAsPdf
     *
     * @param string                   $spatial   string to parse
     * @param string                   $label     field label
     * @param int[]                    $color     line color
     * @param array<string, int|float> $scaleData scaling parameters
     * @param TCPDF                    $pdf       expected output
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        TCPDF $pdf,
    ): void {
        $object = GisGeometryCollection::singleton();
        $return = $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpected = $this->testDir . '/geometrycollection-expected.pdf';
        $fileActual = $this->testDir . '/geometrycollection-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * Data provider for testPrepareRowAsPdf() test case
     *
     * @return array<array{string, string, int[], array<string, int|float>, TCPDF}>
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)),'
                . 'LINESTRING(5 30,4 4))',
                'pdf',
                [176, 46, 224],
                ['x' => 1, 'y' => -9, 'scale' => 4.39, 'height' => 297],

                parent::createEmptyPdf('GEOMETRYCOLLECTION'),
            ],
        ];
    }

    /**
     * Test for prepareRowAsSvg
     *
     * @param string                   $spatial   string to parse
     * @param string                   $label     field label
     * @param int[]                    $color     line color
     * @param array<string, int|float> $scaleData scaling parameters
     * @param string                   $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        string $output,
    ): void {
        $object = GisGeometryCollection::singleton();
        $svg = $object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * Data provider for testPrepareRowAsSvg() test case
     *
     * @return array<array{string, string, int[], array<string, int|float>, string}>
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                'svg',
                [176, 46, 224],
                ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
                '<path d=" M 46, 268 L -4, 248 L 6, 208 L 66, 198 Z  M 16,'
                . ' 228 L 46, 224 L 36, 248 Z " name="svg" id="svg1234567890'
                . '" class="polygon vector" stroke="black" stroke-width="0.5"'
                . ' fill="#b02ee0" fill-rule="evenodd" fill-opacity="0.8"/>',
            ],
        ];
    }

    /**
     * Test for prepareRowAsOl
     *
     * @param string $spatial string to parse
     * @param int    $srid    SRID
     * @param string $label   field label
     * @param int[]  $color   line color
     * @param string $output  expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        string $output,
    ): void {
        $object = GisGeometryCollection::singleton();
        $this->assertEquals(
            $output,
            $object->prepareRowAsOl(
                $spatial,
                $srid,
                $label,
                $color,
            ),
        );
    }

    /**
     * Data provider for testPrepareRowAsOl() test case
     *
     * @return array<array{string, int, string, int[], string}>
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                4326,
                'Ol',
                [176, 46, 224],
                'var feature = new ol.Feature(new ol.geom.Polygon([[[35,10],'
                . '[10,20],[15,40],[45,45],[35,10]],[[20,30],[35,32],[30,20]'
                . ',[20,30]]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feat'
                . 'ure.setStyle(new ol.style.Style({fill: new ol.style.Fill('
                . '{"color":[176,46,224,0.8]}),stroke: new ol.style.Stroke({'
                . '"color":[0,0,0],"width":0.5}),text: new ol.style.Text({"t'
                . 'ext":"Ol"})}));vectorSource.addFeature(feature);',
            ],
        ];
    }
}
