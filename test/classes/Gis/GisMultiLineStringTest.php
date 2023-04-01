<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisMultiLineString
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisMultiLineStringTest extends GisGeomTestCase
{
    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string|null, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [
            0 => [
                'MULTILINESTRING' => [
                    'no_of_lines' => 2,
                    0 => ['no_of_points' => 2, 0 => ['x' => 5.02, 'y' => 8.45], 1 => ['x' => 6.14, 'y' => 0.15]],
                    1 => ['no_of_points' => 2, 0 => ['x' => 1.23, 'y' => 4.25], 1 => ['x' => 9.15, 'y' => 0.47]],
                ],
            ],
        ];

        $temp1 = $temp;
        unset($temp1[0]['MULTILINESTRING'][1][1]['y']);

        $temp2 = $temp;
        $temp2[0]['MULTILINESTRING']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTILINESTRING'][1]['no_of_points'] = 1;

        return [
            [$temp, 0, null, 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'],
            // if a coordinate is missing, default is empty string
            [$temp1, 0, null, 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 ))'],
            // missing coordinates are replaced with provided values (3rd parameter)
            [$temp1, 0, '0', 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0))'],
            // at least one line should be there
            [$temp2, 0, null, 'MULTILINESTRING((5.02 8.45,6.14 0.15))'],
            // a line should have at least two points
            [$temp3, 0, '0', 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'],
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
        $object = GisMultiLineString::singleton();
        $this->assertEquals($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * test getShape method
     */
    public function testGetShape(): void
    {
        $rowData = [
            'numparts' => 2,
            'parts' => [
                0 => ['points' => [0 => ['x' => 5.02, 'y' => 8.45], 1 => ['x' => 6.14, 'y' => 0.15]]],
                1 => ['points' => [0 => ['x' => 1.23, 'y' => 4.25], 1 => ['x' => 9.15, 'y' => 0.47]]],
            ],
        ];

        $object = GisMultiLineString::singleton();
        $this->assertEquals(
            'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
            $object->getShape($rowData),
        );
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
        $object = GisMultiLineString::singleton();
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
                "'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',124",
                [
                    'srid' => 124,
                    0 => [
                        'MULTILINESTRING' => [
                            'no_of_lines' => 2,
                            0 => ['no_of_points' => 2, 0 => ['x' => 5.02,'y' => 8.45], 1 => ['x' => 6.14,'y' => 0.15]],
                            1 => ['no_of_points' => 2, 0 => ['x' => 1.23,'y' => 4.25], 1 => ['x' => 9.15,'y' => 0.47]],
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
        $object = GisMultiLineString::singleton();
        $this->assertEquals($minMax, $object->scaleRow($spatial));
    }

    /**
     * data provider for testScaleRow
     *
     * @return array<array{string, ScaleData}>
     */
    public static function providerForTestScaleRow(): array
    {
        return [['MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))', new ScaleData(178, 17, 75, 10)]];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $object = GisMultiLineString::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $object->prepareRowAsPng(
            'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
            'image',
            [176, 46, 224],
            ['x' => 3, 'y' => -16, 'scale' => 1.06, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/multilinestring-expected.png';
        $fileActual = $this->testDir . '/multilinestring-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string                   $spatial   GIS MULTILINESTRING object
     * @param string                   $label     label for the GIS MULTILINESTRING object
     * @param int[]                    $color     color for the GIS MULTILINESTRING object
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
        $object = GisMultiLineString::singleton();
        $return = $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpected = $this->testDir . '/multilinestring-expected.pdf';
        $fileActual = $this->testDir . '/multilinestring-actual.pdf';
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'pdf',
                [176, 46, 224],
                ['x' => 4, 'y' => -90, 'scale' => 1.12, 'height' => 297],

                parent::createEmptyPdf('MULTILINESTRING'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string                   $spatial   GIS MULTILINESTRING object
     * @param string                   $label     label for the GIS MULTILINESTRING object
     * @param int[]                    $color     color for the GIS MULTILINESTRING object
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
        $object = GisMultiLineString::singleton();
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'svg',
                [176, 46, 224],
                ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
                '<polyline points="48,260 70,242 100,138 " name="svg" '
                . 'class="linestring vector" fill="none" stroke="#b02ee0" '
                . 'stroke-width="2" id="svg1234567890"/><polyline points="48,268 10,'
                . '242 332,182 " name="svg" class="linestring vector" fill="none" '
                . 'stroke="#b02ee0" stroke-width="2" id="svg1234567890"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial GIS MULTILINESTRING object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS MULTILINESTRING object
     * @param int[]  $color   color for the GIS MULTILINESTRING object
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
        $object = GisMultiLineString::singleton();
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                4326,
                'Ol',
                [176, 46, 224],
                'var feature = new ol.Feature(new ol.geom.MultiLineString([[[36,14],[47,23],[62,75]'
                . '],[[36,10],[17,23],[178,53]]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feature.'
                . 'setStyle(new ol.style.Style({stroke: new ol.style.Stroke({"color":[176,46,224],"'
                . 'width":2}), text: new ol.style.Text({"text":"Ol"})}));vectorSource.addFeature(fea'
                . 'ture);',
            ],
        ];
    }
}
