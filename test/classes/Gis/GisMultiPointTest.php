<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiPoint;
use TCPDF;
use function function_exists;
use function imagecreatetruecolor;
use function preg_match;

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
     * test case for prepareRowAsPng() method
     *
     * @param string   $spatial     GIS MULTIPOINT object
     * @param string   $label       label for the GIS MULTIPOINT object
     * @param string   $point_color color for the GIS MULTIPOINT object
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
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
        $string = $this->object->prepareRowAsSvg(
            $spatial,
            $label,
            $pointColor,
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
                'var fill = new ol.style.Fill({"color":"white"});var stroke = new ol.style.Stroke({'
                . '"color":[176,46,224],"width":2});var style = new ol.style.Style({image: new ol.s'
                . 'tyle.Circle({fill: fill,stroke: stroke,radius: 3}),fill: fill,stroke: stroke,tex'
                . 't: new ol.style.Text({"text":"Ol","offsetY":-9})});var minLoc = [0, 0];var maxLo'
                . 'c = [1, 1];var ext = ol.extent.boundingExtent([minLoc, maxLoc]);ext = ol.proj.tr'
                . 'ansformExtent(ext, ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'));map.get'
                . 'View().fit(ext, map.getSize());var multiPoint = new ol.geom.MultiPoint(new Array'
                . '((new ol.geom.Point([12,35]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'E'
                . 'PSG:3857\'))).getCoordinates(), (new ol.geom.Point([48,75]).transform(ol.proj.ge'
                . 't("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Poin'
                . 't([69,23]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getC'
                . 'oordinates(), (new ol.geom.Point([25,45]).transform(ol.proj.get("EPSG:4326"), ol'
                . '.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([14,53]).transfo'
                . 'rm(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new'
                . ' ol.geom.Point([35,78]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3'
                . '857\'))).getCoordinates()));var feature = new ol.Feature({geometry: multiPoint})'
                . ';feature.setStyle(style);vectorLayer.addFeature(feature);',
            ],
        ];
    }
}
