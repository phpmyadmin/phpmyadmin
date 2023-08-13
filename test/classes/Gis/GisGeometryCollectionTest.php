<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometryCollection;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisGeometryCollection
 */
class GisGeometryCollectionTest extends GisGeomTestCase
{
    /** @var    GisGeometryCollection */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = GisGeometryCollection::singleton();
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
     * Data provider for testScaleRow() test case
     *
     * @return array test data for testScaleRow() test case
     */
    public function providerForTestScaleRow(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                [
                    'maxX' => 45.0,
                    'minX' => 10.0,
                    'maxY' => 45.0,
                    'minY' => 10.0,
                ],
            ],
        ];
    }

    /**
     * Data provider for testGenerateWkt() test case
     *
     * @return array test data for testGenerateWkt() test case
     */
    public function providerForTestGenerateWkt(): array
    {
        $temp1 = [
            0 => [
                'gis_type' => 'LINESTRING',
                'LINESTRING' => [
                    'no_of_points' => 2,
                    0 => [
                        'x' => 5.02,
                        'y' => 8.45,
                    ],
                    1 => [
                        'x' => 6.14,
                        'y' => 0.15,
                    ],
                ],
            ],
        ];

        return [
            [
                $temp1,
                0,
                null,
                'GEOMETRYCOLLECTION(LINESTRING(5.02 8.45,6.14 0.15))',
            ],
        ];
    }

    /**
     * Data provider for testGenerateParams() test case
     *
     * @return array test data for testGenerateParams() test case
     */
    public function providerForTestGenerateParams(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(LINESTRING(5.02 8.45,6.14 0.15))',
                null,
                [
                    'srid' => 0,
                    'GEOMETRYCOLLECTION' => ['geom_count' => 1],
                    '0' => [
                        'gis_type' => 'LINESTRING',
                        'LINESTRING' => [
                            'no_of_points' => 2,
                            '0' => [
                                'x' => 5.02,
                                'y' => 8.45,
                            ],
                            '1' => [
                                'x' => 6.14,
                                'y' => 0.15,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testPrepareRowAsPng
     *
     * @return string[]
     */
    public function providerForTestPrepareRowAsPng(): array
    {
        return [
            ['GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))'],
        ];
    }

    /**
     * Data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForTestPrepareRowAsPdf(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
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
     * Data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForTestPrepareRowAsSvg(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                'svg',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '/^(<path d=" M 46, 268 L -4, 248 L 6, 208 L 66, 198 Z  M 16,'
                    . ' 228 L 46, 224 L 36, 248 Z " name="svg" id="svg)(\d+)'
                    . '(" class="polygon vector" stroke="black" stroke-width="0.5"'
                    . ' fill="#B02EE0" fill-rule="evenodd" fill-opacity="0.8"\/>)$/',
            ],
        ];
    }

    /**
     * Data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForTestPrepareRowAsOl(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var style = new ol.style.Style({fill: new ol.style.Fill({"c'
                . 'olor":[176,46,224,0.8]}),stroke: new ol.style.Stroke({"co'
                . 'lor":[0,0,0],"width":0.5}),text: new ol.style.Text({"text'
                . '":"Ol"})});var minLoc = [0, 0];var maxLoc = [1, 1];var ex'
                . 't = ol.extent.boundingExtent([minLoc, maxLoc]);ext = ol.p'
                . 'roj.transformExtent(ext, ol.proj.get("EPSG:4326"), ol.pro'
                . 'j.get(\'EPSG:3857\'));map.getView().fit(ext, map.getSize('
                . '));var arr = [];var lineArr = [];var line = new ol.geom.L'
                . 'inearRing(new Array((new ol.geom.Point([35,10]).transform'
                . '(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).g'
                . 'etCoordinates(), (new ol.geom.Point([10,20]).transform(ol'
                . '.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getC'
                . 'oordinates(), (new ol.geom.Point([15,40]).transform(ol.pr'
                . 'oj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoor'
                . 'dinates(), (new ol.geom.Point([45,45]).transform(ol.proj.'
                . 'get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordin'
                . 'ates(), (new ol.geom.Point([35,10]).transform(ol.proj.get'
                . '("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinate'
                . 's()));var coord = line.getCoordinates();for (var i = 0; i < coord.length; '
                . 'i++) lineArr.push(coord[i]);arr.push(lineArr);var lineArr = '
                . '[];var line = new ol.geom.LinearRing(new Array((new ol.ge'
                . 'om.Point([20,30]).transform(ol.proj.get("EPSG:4326"), ol.'
                . 'proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.'
                . 'Point([35,32]).transform(ol.proj.get("EPSG:4326"), ol.pro'
                . 'j.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Poi'
                . 'nt([30,20]).transform(ol.proj.get("EPSG:4326"), ol.proj.g'
                . 'et(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point('
                . '[20,30]).transform(ol.proj.get("EPSG:4326"), ol.proj.get('
                . '\'EPSG:3857\'))).getCoordinates()));var coord = line.getC'
                . 'oordinates();for (var i = 0; i < coord.length; i++) lineArr.push(coord[i]);ar'
                . 'r.push(lineArr);var polygon = new ol.geom.Polygon(arr);va'
                . 'r feature = new ol.Feature({geometry: polygon});feature.s'
                . 'etStyle(style);vectorLayer.addFeature(feature);',
            ],
        ];
    }
}
