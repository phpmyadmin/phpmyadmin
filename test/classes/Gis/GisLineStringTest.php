<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisLineString;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisLineString
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisLineStringTest extends GisGeomTestCase
{
    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string|null, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp1 = [
            0 => [
                'LINESTRING' => [
                    'no_of_points' => 2,
                    0 => ['x' => 5.02, 'y' => 8.45],
                    1 => ['x' => 6.14, 'y' => 0.15],
                ],
            ],
        ];

        $temp2 = $temp1;
        $temp2[0]['LINESTRING']['no_of_points'] = 3;
        $temp2[0]['LINESTRING'][2] = ['x' => 1.56];

        $temp3 = $temp2;
        $temp3[0]['LINESTRING']['no_of_points'] = -1;

        $temp4 = $temp3;
        $temp4[0]['LINESTRING']['no_of_points'] = 3;
        unset($temp4[0]['LINESTRING'][2]['x']);

        return [
            [$temp1, 0, null, 'LINESTRING(5.02 8.45,6.14 0.15)'],
            // if a coordinate is missing, default is empty string
            [$temp2, 0, null, 'LINESTRING(5.02 8.45,6.14 0.15,1.56 )'],
            // if no_of_points is not valid, it is considered as 2
            [$temp3, 0, null, 'LINESTRING(5.02 8.45,6.14 0.15)'],
            // missing coordinates are replaced with provided values (3rd parameter)
            [$temp4, 0, '0', 'LINESTRING(5.02 8.45,6.14 0.15,0 0)'],
        ];
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
        $object = GisLineString::singleton();
        $this->assertEquals($output, $object->generateWkt($gisData, $index, $empty));
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
        $object = GisLineString::singleton();
        $this->assertEquals($params, $object->generateParams($wkt));
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array<array{string, array<mixed>}>
     */
    public static function providerForTestGenerateParams(): array
    {
        return [
            [
                "'LINESTRING(5.02 8.45,6.14 0.15)',124",
                [
                    'srid' => 124,
                    0 => [
                        'LINESTRING' => [
                            'no_of_points' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
                        ],
                    ],
                ],
            ],
            [
                '',
                [
                    'srid' => 0,
                    0 => [
                        'LINESTRING' => [
                            'no_of_points' => 1,
                            0 => [
                                'x' => 0,
                                'y' => 0,
                            ],
                        ],
                    ],
                ],
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
        $object = GisLineString::singleton();
        $this->assertEquals($minMax, $object->scaleRow($spatial));
    }

    /**
     * data provider for testScaleRow
     *
     * @return array<array{string, ScaleData}>
     */
    public static function providerForTestScaleRow(): array
    {
        return [['LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)', new ScaleData(69, 12, 78, 23)]];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $object = GisLineString::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $object->prepareRowAsPng(
            'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
            'image',
            [176, 46, 224],
            ['x' => -18, 'y' => 14, 'scale' => 1.71, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/linestring-expected.png';
        $fileActual = $this->testDir . '/linestring-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string                   $spatial   GIS LINESTRING object
     * @param string                   $label     label for the GIS LINESTRING object
     * @param int[]                    $color     color for the GIS LINESTRING object
     * @param array<string, int|float> $scaleData array containing data related to scaling
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
        $object = GisLineString::singleton();
        $return = $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpected = $this->testDir . '/linestring-expected.pdf';
        $fileActual = $this->testDir . '/linestring-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array<array{string, string, int[], array<string, int|float>, TCPDF}>
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                'pdf',
                [176, 46, 224],
                ['x' => 7, 'y' => 3, 'scale' => 3.15, 'height' => 297],

                parent::createEmptyPdf('LINESTRING'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string                   $spatial   GIS LINESTRING object
     * @param string                   $label     label for the GIS LINESTRING object
     * @param int[]                    $color     color for the GIS LINESTRING object
     * @param array<string, int|float> $scaleData array containing data related to scaling
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
        $object = GisLineString::singleton();
        $svg = $object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array<array{string, string, int[], array<string, int|float>, string}>
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                [176, 46, 224],
                ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
                '<polyline points="0,218 72,138 114,242 26,198 4,182 46,132 " '
                . 'name="svg" id="svg1234567890" class="linestring vector" fill="none" '
                . 'stroke="#b02ee0" stroke-width="2"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial GIS LINESTRING object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS LINESTRING object
     * @param int[]  $color   color for the GIS LINESTRING object
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
        $object = GisLineString::singleton();
        $ol = $object->prepareRowAsOl($spatial, $srid, $label, $color);
        $this->assertEquals($output, $ol);
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array<array{string, int, string, int[], string}>
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                [176, 46, 224],
                'var feature = new ol.Feature(new ol.geom.LineString([[12,35],[48,75],[69,23],[25,4'
                . '5],[14,53],[35,78]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feature.setStyle(n'
                . 'ew ol.style.Style({stroke: new ol.style.Stroke({"color":[176,46,224],"width":2})'
                . ', text: new ol.style.Text({"text":"Ol"})}));vectorSource.addFeature(feature);',
            ],
        ];
    }
}
