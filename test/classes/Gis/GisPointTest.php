<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Gis\GisPoint;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function file_exists;

/**
 * @covers \PhpMyAdmin\Gis\GisPoint
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisPointTest extends GisGeomTestCase
{
    protected GisGeometry $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = GisPoint::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public static function providerForTestGenerateWkt(): array
    {
        return [
            [
                [
                    0 => [
                        'POINT' => [
                            'x' => 5.02,
                            'y' => 8.45,
                        ],
                    ],
                ],
                0,
                null,
                'POINT(5.02 8.45)',
            ],
            [
                [
                    0 => [
                        'POINT' => [
                            'x' => 5.02,
                            'y' => 8.45,
                        ],
                    ],
                ],
                1,
                null,
                'POINT( )',
            ],
            [
                [0 => ['POINT' => ['x' => 5.02]]],
                0,
                null,
                'POINT(5.02 )',
            ],
            [
                [0 => ['POINT' => ['y' => 8.45]]],
                0,
                null,
                'POINT( 8.45)',
            ],
            [
                [0 => ['POINT' => []]],
                0,
                null,
                'POINT( )',
            ],
        ];
    }

    /**
     * test getShape method
     *
     * @param array  $row_data array of GIS data
     * @param string $shape    expected shape in WKT
     *
     * @dataProvider providerForTestGetShape
     */
    public function testGetShape(array $row_data, string $shape): void
    {
        $this->assertEquals($shape, $this->object->getShape($row_data));
    }

    /**
     * data provider for testGetShape
     *
     * @return array data for testGetShape
     */
    public static function providerForTestGetShape(): array
    {
        return [
            [
                [
                    'x' => 5.02,
                    'y' => 8.45,
                ],
                'POINT(5.02 8.45)',
            ],
        ];
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public static function providerForTestGenerateParams(): array
    {
        return [
            [
                "'POINT(5.02 8.45)',124",
                [
                    'srid' => 124,
                    0 => [
                        'POINT' => [
                            'x' => 5.02,
                            'y' => 8.45,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * data provider for testScaleRow
     *
     * @return array data for testScaleRow
     */
    public static function providerForTestScaleRow(): array
    {
        return [
            [
                'POINT(12 35)',
                new ScaleData(12, 12, 35, 35),
            ],
        ];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            'POINT(12 35)',
            'image',
            [176, 46, 224],
            ['x' => -88, 'y' => -27, 'scale' => 1, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/point-expected.png';
        $fileActual = $this->testDir . '/point-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS POINT object
     * @param string $label      label for the GIS POINT object
     * @param int[]  $color      color for the GIS POINT object
     * @param array  $scale_data array containing data related to scaling
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        TCPDF $pdf,
    ): void {
        $return = $this->object->prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf);

        $fileExpectedArch = $this->testDir . '/point-expected-' . $this->getArch() . '.pdf';
        $fileExpectedGeneric = $this->testDir . '/point-expected.pdf';
        $fileExpected = file_exists($fileExpectedArch) ? $fileExpectedArch : $fileExpectedGeneric;
        $fileActual = $this->testDir . '/point-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'POINT(12 35)',
                'pdf',
                [176, 46, 224],
                ['x' => -93, 'y' => -114, 'scale' => 1, 'height' => 297],

                parent::createEmptyPdf('POINT'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial   GIS POINT object
     * @param string $label     label for the GIS POINT object
     * @param int[]  $color     color for the GIS POINT object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
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
        $svg = $this->object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * data provider for prepareRowAsSvg() test case
     *
     * @return array test data for prepareRowAsSvg() test case
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POINT(12 35)',
                'svg',
                [176, 46, 224],
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS POINT object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS POINT object
     * @param int[]  $color      color for the GIS POINT object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        array $scale_data,
        string $output,
    ): void {
        $ol = $this->object->prepareRowAsOl($spatial, $srid, $label, $color, $scale_data);
        $this->assertEquals($output, $ol);
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'POINT(12 35)',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var fill = new ol.style.Fill({"color":"white"});'
                . 'var stroke = new ol.style.Stroke({"color":[176'
                . ',46,224],"width":2});var style = new ol.style.'
                . 'Style({image: new ol.style.Circle({fill: fill,'
                . 'stroke: stroke,radius: 3}),fill: fill,stroke: '
                . 'stroke,text: new ol.style.Text({"text":"Ol","o'
                . 'ffsetY":-9})});var minLoc = [0, 0];var maxLoc '
                . '= [1, 1];var ext = ol.extent.boundingExtent([m'
                . 'inLoc, maxLoc]);ext = ol.proj.transformExtent('
                . 'ext, ol.proj.get("EPSG:4326"), ol.proj.get(\'E'
                . 'PSG:3857\'));map.getView().fit(ext, map.getSiz'
                . 'e());var point = new ol.Feature({geometry: (ne'
                . 'w ol.geom.Point([12,35]).transform(ol.proj.get'
                . '("EPSG:4326"), ol.proj.get(\'EPSG:3857\')))});'
                . 'point.setStyle(style);vectorLayer.addFeature(p'
                . 'oint);',
            ],
        ];
    }
}
