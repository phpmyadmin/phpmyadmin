<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisPolygon;
use TCPDF;
use function function_exists;
use function imagecreatetruecolor;
use function preg_match;

class GisPolygonTest extends GisGeomTestCase
{
    /**
     * @var    GisPolygon
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
        $this->object = GisPolygon::singleton();
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
     * Provide some common data to data providers
     *
     * @return array common data for data providers
     */
    private function getData(): array
    {
        return [
            'POLYGON' => [
                'no_of_lines' => 2,
                0 => [
                    'no_of_points' => 5,
                    0 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 20,
                    ],
                    2 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                    3 => [
                        'x' => 45,
                        'y' => 45,
                    ],
                    4 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                ],
                1 => [
                    'no_of_points' => 4,
                    0 => [
                        'x' => 20,
                        'y' => 30,
                    ],
                    1 => [
                        'x' => 35,
                        'y' => 32,
                    ],
                    2 => [
                        'x' => 30,
                        'y' => 20,
                    ],
                    3 => [
                        'x' => 20,
                        'y' => 30,
                    ],
                ],
            ],
        ];
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public function providerForTestGenerateWkt(): array
    {
        $temp = [
            0 => $this->getData(),
        ];

        $temp1 = $temp;
        unset($temp1[0]['POLYGON'][1][3]['y']);

        $temp2 = $temp;
        $temp2[0]['POLYGON']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['POLYGON'][1]['no_of_points'] = 3;

        return [
            [
                $temp,
                0,
                null,
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',
            ],
            // values at undefined index
            [
                $temp,
                1,
                null,
                'POLYGON(( , , , ))',
            ],
            // if a coordinate is missing, default is empty string
            [
                $temp1,
                0,
                null,
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 ))',
            ],
            // missing coordinates are replaced with provided values (3rd parameter)
            [
                $temp1,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 0))',
            ],
            // should have at least one ring
            [
                $temp2,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10))',
            ],
            // a ring should have at least four points
            [
                $temp3,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',
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
        $temp = $this->getData();

        $temp1 = $temp;
        $temp1['gis_type'] = 'POLYGON';

        return [
            [
                "'POLYGON((35 10,10 20,15 40,45 45,35 10),"
                    . "(20 30,35 32,30 20,20 30))',124",
                null,
                [
                    'srid' => '124',
                    0 => $temp,
                ],
            ],
            [
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',
                2,
                [2 => $temp1],
            ],
        ];
    }

    /**
     * test for Area
     *
     * @param array $ring array of points forming the ring
     * @param float $area area of the ring
     *
     * @dataProvider providerForTestArea
     */
    public function testArea(array $ring, float $area): void
    {
        $this->assertEquals($this->object->area($ring), $area);
    }

    /**
     * data provider for testArea
     *
     * @return array data for testArea
     */
    public function providerForTestArea(): array
    {
        return [
            [
                [
                    0 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 10,
                    ],
                    2 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                ],
                -375.00,
            ],
            // first point of the ring repeated as the last point
            [
                [
                    0 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 10,
                    ],
                    2 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                    3 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                ],
                -375.00,
            ],
            // anticlockwise gives positive area
            [
                [
                    0 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 10,
                    ],
                    2 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                ],
                375.00,
            ],
        ];
    }

    /**
     * test for isPointInsidePolygon
     *
     * @param array $point    x, y coordinates of the point
     * @param array $polygon  array of points forming the ring
     * @param bool  $isInside output
     *
     * @dataProvider providerForTestIsPointInsidePolygon
     */
    public function testIsPointInsidePolygon(array $point, array $polygon, bool $isInside): void
    {
        $this->assertEquals(
            $this->object->isPointInsidePolygon($point, $polygon),
            $isInside
        );
    }

    /**
     * data provider for testIsPointInsidePolygon
     *
     * @return array data for testIsPointInsidePolygon
     */
    public function providerForTestIsPointInsidePolygon(): array
    {
        $ring = [
            0 => [
                'x' => 35,
                'y' => 10,
            ],
            1 => [
                'x' => 10,
                'y' => 10,
            ],
            2 => [
                'x' => 15,
                'y' => 40,
            ],
            3 => [
                'x' => 35,
                'y' => 10,
            ],
        ];

        return [
            // point inside the ring
            [
                [
                    'x' => 20,
                    'y' => 15,
                ],
                $ring,
                true,
            ],
            // point on an edge of the ring
            [
                [
                    'x' => 20,
                    'y' => 10,
                ],
                $ring,
                false,
            ],
            // point on a vertex of the ring
            [
                [
                    'x' => 10,
                    'y' => 10,
                ],
                $ring,
                false,
            ],
            // point outside the ring
            [
                [
                    'x' => 5,
                    'y' => 10,
                ],
                $ring,
                false,
            ],
        ];
    }

    /**
     * test for getPointOnSurface
     *
     * @param array $ring array of points forming the ring
     *
     * @dataProvider providerForTestGetPointOnSurface
     */
    public function testGetPointOnSurface(array $ring): void
    {
        $this->assertEquals(
            $this->object->isPointInsidePolygon(
                $this->object->getPointOnSurface($ring),
                $ring
            ),
            true
        );
    }

    /**
     * data provider for testGetPointOnSurface
     *
     * @return array data for testGetPointOnSurface
     */
    public function providerForTestGetPointOnSurface(): array
    {
        $temp = $this->getData();
        unset($temp['POLYGON'][0]['no_of_points']);
        unset($temp['POLYGON'][1]['no_of_points']);

        return [
            [
                $temp['POLYGON'][0],
            ],
            [
                $temp['POLYGON'][1],
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
                'POLYGON((123 0,23 30,17 63,123 0))',
                [
                    'minX' => 17,
                    'maxX' => 123,
                    'minY' => 0,
                    'maxY' => 63,
                ],
            ],
            [
                'POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
                [
                    'minX' => 10,
                    'maxX' => 45,
                    'minY' => 10,
                    'maxY' => 45,
                ],
            ],
        ];
    }

    /**
     * test case for prepareRowAsPng()
     *
     * @param string   $spatial    GIS POLYGON object
     * @param string   $label      label for the GIS POLYGON object
     * @param string   $fill_color color for the GIS POLYGON object
     * @param array    $scale_data array containing data related to scaling
     * @param resource $image      image object
     *
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        string $spatial,
        string $label,
        string $fill_color,
        array $scale_data,
        $image
    ): void {
        $return = $this->object->prepareRowAsPng(
            $spatial,
            $label,
            $fill_color,
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
                'POLYGON((123 0,23 30,17 63,123 0))',
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
     * @param string $spatial    GIS POLYGON object
     * @param string $label      label for the GIS POLYGON object
     * @param string $fill_color color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        string $fill_color,
        array $scale_data,
        TCPDF $pdf
    ): void {
        $return = $this->object->prepareRowAsPdf(
            $spatial,
            $label,
            $fill_color,
            $scale_data,
            $pdf
        );
        $this->assertInstanceOf('TCPDF', $return);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0))',
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
     * @param string $spatial   GIS POLYGON object
     * @param string $label     label for the GIS POLYGON object
     * @param string $fillColor color for the GIS POLYGON object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        string $fillColor,
        array $scaleData,
        string $output
    ): void {
        $string = $this->object->prepareRowAsSvg(
            $spatial,
            $label,
            $fillColor,
            $scaleData
        );
        $this->assertEquals(1, preg_match($output, $string));
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0))',
                'svg',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '/^(<path d=" M 222, 288 L 22, 228 L 10, 162 Z " name="svg" '
                . 'id="svg)(\d+)(" class="polygon vector" stroke="black" '
                . 'stroke-width="0.5" fill="#B02EE0" fill-rule="evenodd" '
                . 'fill-opacity="0.8"\/>)$/',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS POLYGON object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS POLYGON object
     * @param array  $fill_color color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $fill_color,
        array $scale_data,
        string $output
    ): void {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial,
                $srid,
                $label,
                $fill_color,
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
                'POLYGON((123 0,23 30,17 63,123 0))',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var style = new ol.style.Style({fill: new ol.style.Fill({"color":[176,46,224,0.8]'
                . '}),stroke: new ol.style.Stroke({"color":[0,0,0],"width":0.5}),text: new ol.styl'
                . 'e.Text({"text":"Ol"})});var minLoc = [0, 0];var maxLoc = [1, 1];var ext = ol.ex'
                . 'tent.boundingExtent([minLoc, maxLoc]);ext = ol.proj.transformExtent(ext, ol.pro'
                . 'j.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'));map.getView().fit(ext, map.getS'
                . 'ize());var arr = [];var lineArr = [];var line = new ol.geom.LinearRing(new Arra'
                . 'y((new ol.geom.Point([123,0]).transform(ol.proj.get("EPSG:4326"), ol.proj.get('
                . '\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([23,30]).transform(ol.pro'
                . 'j.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom'
                . '.Point([17,63]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))'
                . ').getCoordinates(), (new ol.geom.Point([123,0]).transform(ol.proj.get("EPSG:432'
                . '6"), ol.proj.get(\'EPSG:3857\'))).getCoordinates()));var coord = line.getCoordi'
                . 'nates();for (var i = 0; i < coord.length; i++) lineArr.push(coord[i]);arr.push(lineArr);'
                . 'var polygon = new ol.geom.Polygon(arr);var feature = new ol.Feature({geometry: polygon});f'
                . 'eature.setStyle(style);vectorLayer.addFeature(feature);',
            ],
        ];
    }

    /**
     * test case for isOuterRing() method
     *
     * @param array $ring coordinates of the points in a ring
     *
     * @dataProvider providerForIsOuterRing
     */
    public function testIsOuterRing(array $ring): void
    {
        $this->assertTrue($this->object->isOuterRing($ring));
    }

    /**
     * data provider for testIsOuterRing() test case
     *
     * @return array test data for testIsOuterRing() test case
     */
    public function providerForIsOuterRing(): array
    {
        return [
            [
                [
                    [
                        'x' => 0,
                        'y' => 0,
                    ],
                    [
                        'x' => 0,
                        'y' => 1,
                    ],
                    [
                        'x' => 1,
                        'y' => 1,
                    ],
                    [
                        'x' => 1,
                        'y' => 0,
                    ],
                ],
            ],
        ];
    }
}
