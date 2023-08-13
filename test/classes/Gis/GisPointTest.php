<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisPoint;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisPoint
 */
class GisPointTest extends GisGeomTestCase
{
    /** @var    GisPoint */
    protected $object;

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
     * Data provider for testPrepareRowAsPng
     *
     * @return string[][]
     */
    public function providerForTestPrepareRowAsPng(): array
    {
        return [
            ['POINT(12 35)'],
        ];
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForTestPrepareRowAsPdf(): array
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
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForTestPrepareRowAsSvg(): array
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
                '/^$/',
            ],
        ];
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForTestPrepareRowAsOl(): array
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
