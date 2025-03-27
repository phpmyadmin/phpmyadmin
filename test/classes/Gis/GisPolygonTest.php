<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function preg_match;

/**
 * @covers \PhpMyAdmin\Gis\GisPolygon
 */
class GisPolygonTest extends GisGeomTestCase
{
    /** @var    GisPolygon */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = GisPolygon::singleton();
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
     * Provide some common data to data providers
     *
     * @return array common data for data providers
     */
    private static function getData(): array
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
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [
            0 => self::getData(),
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
    public static function providerForTestGenerateParams(): array
    {
        $temp = self::getData();

        $temp1 = $temp;
        $temp1['gis_type'] = 'POLYGON';

        return [
            [
                '\'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))\',124',
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
        self::assertSame($this->object->area($ring), $area);
    }

    /**
     * data provider for testArea
     *
     * @return array data for testArea
     */
    public static function providerForTestArea(): array
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
        self::assertSame($this->object->isPointInsidePolygon($point, $polygon), $isInside);
    }

    /**
     * data provider for testIsPointInsidePolygon
     *
     * @return array data for testIsPointInsidePolygon
     */
    public static function providerForTestIsPointInsidePolygon(): array
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
        $point = $this->object->getPointOnSurface($ring);
        self::assertIsArray($point);
        self::assertTrue($this->object->isPointInsidePolygon($point, $ring));
    }

    /**
     * data provider for testGetPointOnSurface
     *
     * @return array data for testGetPointOnSurface
     */
    public static function providerForTestGetPointOnSurface(): array
    {
        $temp = self::getData();
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
    public static function providerForTestScaleRow(): array
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
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
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
     * @requires extension gd
     */
    public function testPrepareRowAsPng(): void
    {
        $image = ImageWrapper::create(120, 150);
        self::assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            'POLYGON((123 0,23 30,17 63,123 0))',
            'image',
            '#B02EE0',
            ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
            $image
        );
        self::assertSame(120, $return->width());
        self::assertSame(150, $return->height());
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
        $return = $this->object->prepareRowAsPdf($spatial, $label, $fill_color, $scale_data, $pdf);
        self::assertInstanceOf(TCPDF::class, $return);
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
        $string = $this->object->prepareRowAsSvg($spatial, $label, $fillColor, $scaleData);
        self::assertSame(1, preg_match($output, $string));
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public static function providerForPrepareRowAsSvg(): array
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
                '/^(<path d=" M 222, 288 L 22, 228 L 10, 162 Z " data-label="svg" '
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
        self::assertSame($output, $this->object->prepareRowAsOl(
            $spatial,
            $srid,
            $label,
            $fill_color,
            $scale_data
        ));
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
        self::assertTrue($this->object->isOuterRing($ring));
    }

    /**
     * data provider for testIsOuterRing() test case
     *
     * @return array test data for testIsOuterRing() test case
     */
    public static function providerForIsOuterRing(): array
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
