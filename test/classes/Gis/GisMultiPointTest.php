<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function preg_match;

/**
 * @covers \PhpMyAdmin\Gis\GisMultiPoint
 */
class GisMultiPointTest extends GisGeomTestCase
{
    /**
     * @var    GisMultiPoint
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
        $this->object = GisMultiPoint::singleton();
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
        $gis_data1 = [
            0 => [
                'MULTIPOINT' => [
                    'no_of_points' => 2,
                    0 => [
                        'x' => 5.02,
                        'y' => 8.45,
                    ],
                    1 => [
                        'x' => 1.56,
                        'y' => 4.36,
                    ],
                ],
            ],
        ];

        $gis_data2 = $gis_data1;
        $gis_data2[0]['MULTIPOINT']['no_of_points'] = -1;

        return [
            [
                $gis_data1,
                0,
                null,
                'MULTIPOINT(5.02 8.45,1.56 4.36)',
            ],
            [
                $gis_data2,
                0,
                null,
                'MULTIPOINT(5.02 8.45)',
            ],
        ];
    }

    /**
     * test getShape method
     */
    public function testGetShape(): void
    {
        $gis_data = [
            'numpoints' => 2,
            'points' => [
                0 => [
                    'x' => 5.02,
                    'y' => 8.45,
                ],
                1 => [
                    'x' => 6.14,
                    'y' => 0.15,
                ],
            ],
        ];

        $this->assertEquals(
            $this->object->getShape($gis_data),
            'MULTIPOINT(5.02 8.45,6.14 0.15)'
        );
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public function providerForTestGenerateParams(): array
    {
        $temp1 = [
            'MULTIPOINT' => [
                'no_of_points' => 2,
                0 => [
                    'x' => '5.02',
                    'y' => '8.45',
                ],
                1 => [
                    'x' => '6.14',
                    'y' => '0.15',
                ],
            ],
        ];
        $temp2 = $temp1;
        $temp2['gis_type'] = 'MULTIPOINT';

        return [
            [
                "'MULTIPOINT(5.02 8.45,6.14 0.15)',124",
                null,
                [
                    'srid' => '124',
                    0 => $temp1,
                ],
            ],
            [
                'MULTIPOINT(5.02 8.45,6.14 0.15)',
                2,
                [2 => $temp2],
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                [
                    'minX' => 12,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 78,
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
        $this->assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
            'image',
            '#B02EE0',
            ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
            $image
        );
        $this->assertEquals(120, $return->width());
        $this->assertEquals(150, $return->height());
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       label for the GIS MULTIPOINT object
     * @param string $point_color color for the GIS MULTIPOINT object
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
        $return = $this->object->prepareRowAsPdf($spatial, $label, $point_color, $scale_data, $pdf);
        $this->assertInstanceOf(TCPDF::class, $return);
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
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
     * @param string $spatial    GIS MULTIPOINT object
     * @param string $label      label for the GIS MULTIPOINT object
     * @param string $pointColor color for the GIS MULTIPOINT object
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
        $string = $this->object->prepareRowAsSvg($spatial, $label, $pointColor, $scaleData);
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '/^(<circle cx="72" cy="138" r="3" name="svg" class="multipoint '
                . 'vector" fill="white" stroke="#B02EE0" stroke-width="2" id="svg)'
                . '(\d+)("\/><circle cx="114" cy="242" r="3" name="svg" class="mult'
                . 'ipoint vector" fill="white" stroke="#B02EE0" stroke-width="2" id'
                . '="svg)(\d+)("\/><circle cx="26" cy="198" r="3" name="svg" class='
                . '"multipoint vector" fill="white" stroke="#B02EE0" stroke-width='
                . '"2" id="svg)(\d+)("\/><circle cx="4" cy="182" r="3" name="svg" '
                . 'class="multipoint vector" fill="white" stroke="#B02EE0" stroke-'
                . 'width="2" id="svg)(\d+)("\/><circle cx="46" cy="132" r="3" name='
                . '"svg" class="multipoint vector" fill="white" stroke="#B02EE0" '
                . 'stroke-width="2" id="svg)(\d+)("\/>)$/',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param int    $srid        spatial reference ID
     * @param string $label       label for the GIS MULTIPOINT object
     * @param array  $point_color color for the GIS MULTIPOINT object
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var feature = new ol.Feature(new ol.geom.MultiPoint([[12,35],[48,75],[69,23],[25,4'
                . '5],[14,53],[35,78]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feature.setStyle(n'
                . 'ew ol.style.Style({image: new ol.style.Circle({fill: new ol.style.Fill({"color":'
                . '"white"}),stroke: new ol.style.Stroke({"color":[176,46,224],"width":2}),radius: '
                . '3}),text: new ol.style.Text({"text":"Ol","offsetY":-9})}));vectorLayer.addFeatur'
                . 'e(feature);',
            ],
        ];
    }
}
