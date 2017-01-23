<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA\libraries\gis\GISMultipoint
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\gis\GISMultipoint;

require_once 'GISGeomTest.php';
require_once TCPDF_INC;

/**
 * Tests for PMA\libraries\gis\GISMultipoint class
 *
 * @package PhpMyAdmin-test
 */
class GISMultipointTest extends GISGeomTest
{
    /**
     * @var    GISMultipoint
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = GISMultipoint::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return data for testGenerateWkt
     */
    public function providerForTestGenerateWkt()
    {
        $gis_data1 = array(
            0 => array(
                'MULTIPOINT' => array(
                    'no_of_points' => 2,
                    0 => array(
                        'x' => 5.02,
                        'y' => 8.45
                    ),
                    1 => array(
                        'x' => 1.56,
                        'y' => 4.36
                    )
                )
            )
        );

        $gis_data2 = $gis_data1;
        $gis_data2[0]['MULTIPOINT']['no_of_points'] = -1;

        return array(
            array(
                $gis_data1,
                0,
                null,
                'MULTIPOINT(5.02 8.45,1.56 4.36)'
            ),
            array(
                $gis_data2,
                0,
                null,
                'MULTIPOINT(5.02 8.45)'
            )
        );
    }

    /**
     * test getShape method
     *
     * @return void
     */
    public function testGetShape()
    {
        $gis_data = array(
            'numpoints' => 2,
            'points' => array(
                0 => array('x' => 5.02, 'y' => 8.45),
                1 => array('x' => 6.14, 'y' => 0.15)
            )
        );

        $this->assertEquals(
            $this->object->getShape($gis_data),
            'MULTIPOINT(5.02 8.45,6.14 0.15)'
        );
    }

    /**
     * data provider for testGenerateParams
     *
     * @return data for testGenerateParams
     */
    public function providerForTestGenerateParams()
    {
        $temp1 = array(
            'MULTIPOINT' => array(
                'no_of_points' => 2,
                0 => array('x' => '5.02', 'y' => '8.45'),
                1 => array('x' => '6.14', 'y' => '0.15')
            )
        );
        $temp2 = $temp1;
        $temp2['gis_type'] = 'MULTIPOINT';

        return array(
            array(
                "'MULTIPOINT(5.02 8.45,6.14 0.15)',124",
                null,
                array(
                    'srid' => '124',
                    0 => $temp1
                )
            ),
            array(
                'MULTIPOINT(5.02 8.45,6.14 0.15)',
                2,
                array(
                    2 => $temp2
                )
            )
        );
    }

    /**
     * data provider for testScaleRow
     *
     * @return data for testScaleRow
     */
    public function providerForTestScaleRow()
    {
        return array(
            array(
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                array(
                    'minX' => 12,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 78
                )
            )
        );
    }


    /**
     * test case for prepareRowAsPng() method
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       label for the GIS MULTIPOINT object
     * @param string $point_color color for the GIS MULTIPOINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param object $image       image object
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        $spatial, $label, $point_color, $scale_data, $image
    ) {
        $return = $this->object->prepareRowAsPng(
            $spatial, $label, $point_color, $scale_data, $image
        );
        $this->assertImage($return);
    }

    /**
     * data provider for testPrepareRowAsPng() test case
     *
     * @return array test data for testPrepareRowAsPng() test case
     */
    public function providerForPrepareRowAsPng()
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension missing!');
        }
        return array(
            array(
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'image',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                imagecreatetruecolor('120', '150'),
            )
        );
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       label for the GIS MULTIPOINT object
     * @param string $point_color color for the GIS MULTIPOINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param object $pdf         TCPDF instance
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        $spatial, $label, $point_color, $scale_data, $pdf
    ) {
        $return = $this->object->prepareRowAsPdf(
            $spatial, $label, $point_color, $scale_data, $pdf
        );
        $this->assertInstanceOf('TCPDF', $return);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf()
    {
        return array(
            array(
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'pdf',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                new TCPDF(),
            )
        );
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       label for the GIS MULTIPOINT object
     * @param string $point_color color for the GIS MULTIPOINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param string $output      expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        $spatial, $label, $point_color, $scale_data, $output
    ) {
        $string = $this->object->prepareRowAsSvg(
            $spatial, $label, $point_color, $scale_data
        );
        $this->assertEquals(1, preg_match($output, $string));
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg()
    {
        return array(
            array(
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
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
                . 'stroke-width="2" id="svg)(\d+)("\/>)$/'
            )
        );
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param int    $srid        spatial reference ID
     * @param string $label       label for the GIS MULTIPOINT object
     * @param string $point_color color for the GIS MULTIPOINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param string $output      expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        $spatial, $srid, $label, $point_color, $scale_data, $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial, $srid, $label, $point_color, $scale_data
            )
        );
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForPrepareRowAsOl()
    {
        return array(
            array(
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                '#B02EE0',
                array(
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ),
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.Lon'
                . 'Lat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject())); bound.extend(new OpenLayers.LonLat'
                . '(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.'
                . 'getProjectionObject()));vectorLayer.addFeatures(new OpenLayers.'
                . 'Feature.Vector(new OpenLayers.Geometry.MultiPoint(new Array(('
                . 'new OpenLayers.Geometry.Point(12,35)).transform(new OpenLayers.'
                . 'Projection("EPSG:4326"), map.getProjectionObject()), (new Open'
                . 'Layers.Geometry.Point(48,75)).transform(new OpenLayers.Projec'
                . 'tion("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.'
                . 'Geometry.Point(69,23)).transform(new OpenLayers.Projection("'
                . 'EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry'
                . '.Point(25,45)).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject()), (new OpenLayers.Geometry.Point(14,53)'
                . ').transform(new OpenLayers.Projection("EPSG:4326"), map.getProjec'
                . 'tionObject()), (new OpenLayers.Geometry.Point(35,78)).transform'
                . '(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject('
                . ')))), null, {"pointRadius":3,"fillColor":"#ffffff","strokeColor"'
                . ':"#B02EE0","strokeWidth":2,"label":"Ol","labelYOffset":-8,'
                . '"fontSize":10}));'
            )
        );
    }
}
