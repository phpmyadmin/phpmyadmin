<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisPoint;
use TCPDF;
use function function_exists;
use function imagecreatetruecolor;

class GisPointTest extends GisGeomTestCase
{
    /**
     * @var    GisPoint
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = GisPoint::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
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
    public function providerForTestGenerateWkt(): array
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
        $this->assertEquals($this->object->getShape($row_data), $shape);
    }

    /**
     * data provider for testGetShape
     *
     * @return array data for testGetShape
     */
    public function providerForTestGetShape(): array
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
    public function providerForTestGenerateParams(): array
    {
        return [
            [
                "'POINT(5.02 8.45)',124",
                null,
                [
                    'srid' => '124',
                    0 => [
                        'POINT' => [
                            'x' => '5.02',
                            'y' => '8.45',
                        ],
                    ],
                ],
            ],
            [
                'POINT(5.02 8.45)',
                2,
                [
                    2 => [
                        'gis_type' => 'POINT',
                        'POINT' => [
                            'x' => '5.02',
                            'y' => '8.45',
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
    public function providerForTestScaleRow(): array
    {
        return [
            [
                'POINT(12 35)',
                [
                    'minX' => 12,
                    'maxX' => 12,
                    'minY' => 35,
                    'maxY' => 35,
                ],
            ],
        ];
    }

    /**
     * test case for prepareRowAsPng() method
     *
     * @param string   $spatial     GIS POINT object
     * @param string   $label       label for the GIS POINT object
     * @param string   $point_color color for the GIS POINT object
     * @param array    $scale_data  array containing data related to scaling
     * @param resource $image       image object
     *
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        string $spatial,
        string $label,
        string $point_color,
        array $scale_data,
        $image
    ): void {
        $return = $this->object->prepareRowAsPng(
            $spatial,
            $label,
            $point_color,
            $scale_data,
            $image
        );
        $this->assertImage($return);
    }

    /**
     * data provider for testPrepareRowAsPng() test case
     *
     * @return array test data for testPrepareRowAsPng() test case
     */
    public function providerForPrepareRowAsPng(): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension missing!');
        }

        return [
            [
                'POINT(12 35)',
                'image',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                imagecreatetruecolor(120, 150),
            ],
        ];
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       label for the GIS POINT object
     * @param string $point_color color for the GIS POINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param TCPDF  $pdf         TCPDF instance
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        string $point_color,
        array $scale_data,
        TCPDF $pdf
    ): void {
        $return = $this->object->prepareRowAsPdf(
            $spatial,
            $label,
            $point_color,
            $scale_data,
            $pdf
        );
        $this->assertInstanceOf('TCPDF', $return);
    }

    /**
     * data provider for prepareRowAsPdf() test case
     *
     * @return array test data for prepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'POINT(12 35)',
                'pdf',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                new TCPDF(),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial    GIS POINT object
     * @param string $label      label for the GIS POINT object
     * @param string $pointColor color for the GIS POINT object
     * @param array  $scaleData  array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        string $pointColor,
        array $scaleData,
        string $output
    ): void {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsSvg(
                $spatial,
                $label,
                $pointColor,
                $scaleData
            )
        );
    }

    /**
     * data provider for prepareRowAsSvg() test case
     *
     * @return array test data for prepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POINT(12 35)',
                'svg',
                '#B02EE0',
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
     * @param string $spatial     GIS POINT object
     * @param int    $srid        spatial reference ID
     * @param string $label       label for the GIS POINT object
     * @param array  $point_color color for the GIS POINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param string $output      expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $point_color,
        array $scale_data,
        string $output
    ): void {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial,
                $srid,
                $label,
                $point_color,
                $scale_data
            )
        );
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForPrepareRowAsOl(): array
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
