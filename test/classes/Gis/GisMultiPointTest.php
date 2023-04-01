<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function file_exists;

/**
 * @covers \PhpMyAdmin\Gis\GisMultiPoint
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisMultiPointTest extends GisGeomTestCase
{
    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string|null, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $gisData1 = [
            0 => [
                'MULTIPOINT' => [
                    'no_of_points' => 2,
                    0 => ['x' => 5.02, 'y' => 8.45],
                    1 => ['x' => 1.56, 'y' => 4.36],
                ],
            ],
        ];

        $gisData2 = $gisData1;
        $gisData2[0]['MULTIPOINT']['no_of_points'] = -1;

        return [
            [$gisData1, 0, null, 'MULTIPOINT(5.02 8.45,1.56 4.36)'],
            [$gisData2, 0, null, 'MULTIPOINT(5.02 8.45)'],
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
        $object = GisMultiPoint::singleton();
        $this->assertEquals($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * test getShape method
     */
    public function testGetShape(): void
    {
        $gisData = ['numpoints' => 2, 'points' => [0 => ['x' => 5.02, 'y' => 8.45], 1 => ['x' => 6.14, 'y' => 0.15]]];

        $object = GisMultiPoint::singleton();
        $this->assertEquals('MULTIPOINT(5.02 8.45,6.14 0.15)', $object->getShape($gisData));
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
        $object = GisMultiPoint::singleton();
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
                "'MULTIPOINT(5.02 8.45,6.14 0.15)',124",
                [
                    'srid' => 124,
                    0 => [
                        'MULTIPOINT' => [
                            'no_of_points' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
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
        $object = GisMultiPoint::singleton();
        $this->assertEquals($minMax, $object->scaleRow($spatial));
    }

    /**
     * data provider for testScaleRow
     *
     * @return array<array{string, ScaleData}>
     */
    public static function providerForTestScaleRow(): array
    {
        return [['MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)', new ScaleData(69, 12, 78, 23)]];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $object = GisMultiPoint::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $object->prepareRowAsPng(
            'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
            'image',
            [176, 46, 224],
            ['x' => -18, 'y' => 14, 'scale' => 1.71, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/multipoint-expected.png';
        $fileActual = $this->testDir . '/multipoint-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string                   $spatial   GIS MULTIPOINT object
     * @param string                   $label     label for the GIS MULTIPOINT object
     * @param int[]                    $color     color for the GIS MULTIPOINT object
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
        $object = GisMultiPoint::singleton();
        $return = $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpectedArch = $this->testDir . '/multipoint-expected-' . $this->getArch() . '.pdf';
        $fileExpectedGeneric = $this->testDir . '/multipoint-expected.pdf';
        $fileExpected = file_exists($fileExpectedArch) ? $fileExpectedArch : $fileExpectedGeneric;
        $fileActual = $this->testDir . '/multipoint-actual.pdf';
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'pdf',
                [176, 46, 224],
                ['x' => 7, 'y' => 3, 'scale' => 3.16, 'height' => 297],

                parent::createEmptyPdf('MULTIPOINT'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string                   $spatial   GIS MULTIPOINT object
     * @param string                   $label     label for the GIS MULTIPOINT object
     * @param int[]                    $color     color for the GIS MULTIPOINT object
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
        $object = GisMultiPoint::singleton();
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                [176, 46, 224],
                ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
                '<circle cx="72" cy="138" r="3" name="svg" class="multipoint '
                . 'vector" fill="white" stroke="#b02ee0" stroke-width="2" id="'
                . 'svg1234567890"/><circle cx="114" cy="242" r="3" name="svg" class="mult'
                . 'ipoint vector" fill="white" stroke="#b02ee0" stroke-width="2" id'
                . '="svg1234567890"/><circle cx="26" cy="198" r="3" name="svg" class='
                . '"multipoint vector" fill="white" stroke="#b02ee0" stroke-width='
                . '"2" id="svg1234567890"/><circle cx="4" cy="182" r="3" name="svg" '
                . 'class="multipoint vector" fill="white" stroke="#b02ee0" stroke-'
                . 'width="2" id="svg1234567890"/><circle cx="46" cy="132" r="3" name='
                . '"svg" class="multipoint vector" fill="white" stroke="#b02ee0" '
                . 'stroke-width="2" id="svg1234567890"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial GIS MULTIPOINT object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS MULTIPOINT object
     * @param int[]  $color   color for the GIS MULTIPOINT object
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
        $object = GisMultiPoint::singleton();
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                [176, 46, 224],
                'var feature = new ol.Feature(new ol.geom.MultiPoint([[12,35],[48,75],[69,23],[25,4'
                . '5],[14,53],[35,78]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feature.setStyle(n'
                . 'ew ol.style.Style({image: new ol.style.Circle({fill: new ol.style.Fill({"color":'
                . '"white"}),stroke: new ol.style.Stroke({"color":[176,46,224],"width":2}),radius: '
                . '3}),text: new ol.style.Text({"text":"Ol","offsetY":-9})}));vectorSource.addFeatur'
                . 'e(feature);',
            ],
        ];
    }
}
