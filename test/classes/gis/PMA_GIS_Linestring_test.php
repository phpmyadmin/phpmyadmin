<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Linestring
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_linestring.php';
require_once 'libraries/tcpdf/tcpdf.php';

/**
 * Tests for PMA_GIS_Linestring class
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_LinestringTest extends PMA_GIS_GeomTest
{
    /**
     * @var    PMA_GIS_Linestring
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
        $this->object = PMA_GIS_Linestring::singleton();
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
        $temp1 = array(
            0 => array(
                'LINESTRING' => array(
                    'no_of_points' => 2,
                    0 => array('x' => 5.02, 'y' => 8.45),
                    1 => array('x' => 6.14, 'y' => 0.15)
                )
            )
        );

        $temp2 = $temp1;
        $temp2[0]['LINESTRING']['no_of_points'] = 3;
        $temp2[0]['LINESTRING'][2] = array('x' => 1.56);

        $temp3 = $temp2;
        $temp3[0]['LINESTRING']['no_of_points'] = -1;

        $temp4 = $temp3;
        $temp4[0]['LINESTRING']['no_of_points'] = 3;
        unset($temp4[0]['LINESTRING'][2]['x']);

        return array(
            array(
                $temp1,
                0,
                null,
                'LINESTRING(5.02 8.45,6.14 0.15)'
            ),
            // if a coordinate is missing, default is empty string
            array(
                $temp2,
                0,
                null,
                'LINESTRING(5.02 8.45,6.14 0.15,1.56 )'
            ),
            // if no_of_points is not valid, it is considered as 2
            array(
                $temp3,
                0,
                null,
                'LINESTRING(5.02 8.45,6.14 0.15)'
            ),
            // missing coordinates are replaced with provided values (3rd parameter)
            array(
                $temp4,
                0,
                '0',
                'LINESTRING(5.02 8.45,6.14 0.15,0 0)'
            )
        );
    }

    /**
     * data provider for testGenerateParams
     *
     * @return data for testGenerateParams
     */
    public function providerForTestGenerateParams()
    {
        $temp = array(
            'LINESTRING' => array(
                'no_of_points' => 2,
                0 => array('x' => '5.02', 'y' => '8.45'),
                1 => array('x' => '6.14', 'y' => '0.15')
            )
        );
        $temp1 = $temp;
        $temp1['gis_type'] = 'LINESTRING';

        return array(
            array(
                "'LINESTRING(5.02 8.45,6.14 0.15)',124",
                null,
                array(
                    'srid' => '124',
                    0 => $temp
                )
            ),
            array(
                'LINESTRING(5.02 8.45,6.14 0.15)',
                2,
                array(
                    2 => $temp1
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
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
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param object $image      image object
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsPng
     * @return void
     */
    public function testPrepareRowAsPng($spatial, $label, $line_color,
        $scale_data, $image, $output
    ) {
        $return = $this->object->prepareRowAsPng(
            $spatial, $label, $line_color, $scale_data, $image
        );
        /* TODO: this never fails */
        $this->assertTrue(true);
    }

    /**
     * data provider for testPrepareRowAsPng() test case
     *
     * @return array test data for testPrepareRowAsPng() test case
     */
    public function providerForPrepareRowAsPng()
    {
        return array(
            array(
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                'image',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                imagecreatetruecolor('120', '150'),
                ''
            )
        );
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param object $pdf        TCPDF instance
     *
     * @dataProvider providerForPrepareRowAsPdf
     * @return void
     */
    public function testPrepareRowAsPdf($spatial, $label, $line_color,
        $scale_data, $pdf
    ) {
        $return = $this->object->prepareRowAsPdf(
            $spatial, $label, $line_color, $scale_data, $pdf
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
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
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     * @return void
     */
    public function testPrepareRowAsSvg($spatial, $label, $line_color,
        $scale_data, $output
    ) {
        $string = $this->object->prepareRowAsSvg(
            $spatial, $label, $line_color, $scale_data
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                '/^(<polyline points="0,218 72,138 114,242 26,198 4,182 46,132 " name="svg" id="svg)(\d+)(" class="linestring vector" fill="none" stroke="#B02EE0" stroke-width="2"\/>)$/'
            )
        );
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS LINESTRING object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     * @return void
     */
    public function testPrepareRowAsOl($spatial, $srid, $label,
        $line_color, $scale_data, $output
    ) {
        $this->assertEquals(
            $this->object->prepareRowAsOl(
                $spatial, $srid, $label, $line_color, $scale_data
            ),
            $output
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                '#B02EE0',
                array(
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ),
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));vectorLayer.addFeatures(new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(new Array((new OpenLayers.Geometry.Point(12,35)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(48,75)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(69,23)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(25,45)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(14,53)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(35,78)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()))), null, {"strokeColor":"#B02EE0","strokeWidth":2,"label":"Ol","fontSize":10}));'
            )
        );
    }
}
?>
