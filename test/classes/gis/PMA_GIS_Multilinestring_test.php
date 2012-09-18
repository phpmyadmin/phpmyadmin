<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Multilinestring
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_multilinestring.php';
require_once 'libraries/tcpdf/tcpdf.php';

/**
 * Tests for PMA_GIS_Multilinestring class
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_MultilinestringTest extends PMA_GIS_GeomTest
{
    /**
     * @var    PMA_GIS_Multilinestring
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
        $this->object = PMA_GIS_Multilinestring::singleton();
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
        $temp = array(
            0 => array(
                'MULTILINESTRING' => array(
                    'no_of_lines' => 2,
                    0 => array(
                        'no_of_points' => 2,
                        0 => array('x' => 5.02, 'y' => 8.45),
                        1 => array('x' => 6.14, 'y' => 0.15)
                    ),
                    1 => array(
                        'no_of_points' => 2,
                        0 => array('x' => 1.23, 'y' => 4.25),
                        1 => array('x' => 9.15, 'y' => 0.47)
                    )
                )
            )
        );

        $temp1 = $temp;
        unset($temp1[0]['MULTILINESTRING'][1][1]['y']);

        $temp2 = $temp;
        $temp2[0]['MULTILINESTRING']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTILINESTRING'][1]['no_of_points'] = 1;

        return array(
            array(
                $temp,
                0,
                null,
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'
            ),
            // values at undefined index
            array(
                $temp,
                1,
                null,
                'MULTILINESTRING(( , ))'
            ),
            // if a coordinate is missing, default is empty string
            array(
                $temp1,
                0,
                null,
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 ))'
            ),
            // missing coordinates are replaced with provided values (3rd parameter)
            array(
                $temp1,
                0,
                '0',
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0))'
            ),
            // atleast one line should be there
            array(
                $temp2,
                0,
                null,
                'MULTILINESTRING((5.02 8.45,6.14 0.15))'
            ),
            // a line should have atleast two points
            array(
                $temp3,
                0,
                '0',
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'
            ),
        );
    }

    /**
     * test getShape method
     *
     * @return void
     */
    public function testGetShape()
    {
        $row_data = array(
            'numparts' => 2,
            'parts'    => array(
                0 => array(
                    'points' => array(
                        0 => array('x' => 5.02, 'y' => 8.45),
                        1 => array('x' => 6.14, 'y' => 0.15),
                    ),
                ),
                1 => array(
                    'points' => array(
                        0 => array('x' => 1.23, 'y' => 4.25),
                        1 => array('x' => 9.15, 'y' => 0.47),
                    ),
                ),
            ),
        );

        $this->assertEquals(
            $this->object->getShape($row_data),
            'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'
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
            'MULTILINESTRING' => array(
                'no_of_lines' => 2,
                0 => array(
                    'no_of_points' => 2,
                    0 => array('x' => 5.02, 'y' => 8.45),
                    1 => array('x' => 6.14, 'y' => 0.15),
                ),
                1 => array(
                    'no_of_points' => 2,
                    0 => array('x' => 1.23, 'y' => 4.25),
                    1 => array('x' => 9.15, 'y' => 0.47),
                )
            )
        );

        $temp1 = $temp;
        $temp1['gis_type'] = 'MULTILINESTRING';

        return array(
            array(
                "'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',124",
                null,
                array(
                    'srid' => '124',
                    0 => $temp
                )
            ),
            array(
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                array(
                    'minX' => 17,
                    'maxX' => 178,
                    'minY' => 10,
                    'maxY' => 75
                )
            )
        );
    }


    /**
     * test case for prepareRowAsPng() method
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      label for the GIS MULTILINESTRING object
     * @param string $line_color color for the GIS MULTILINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param object $image      image object
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        $spatial, $label, $line_color, $scale_data, $image, $output
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
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
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      label for the GIS MULTILINESTRING object
     * @param string $line_color color for the GIS MULTILINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param object $pdf        TCPDF instance
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        $spatial, $label, $line_color, $scale_data, $pdf
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
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
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      label for the GIS MULTILINESTRING object
     * @param string $line_color color for the GIS MULTILINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        $spatial, $label, $line_color, $scale_data, $output
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'svg',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                '/^(<polyline points="48,260 70,242 100,138 " name="svg" class="linestring vector" fill="none" stroke="#B02EE0" stroke-width="2" id="svg)(\d+)("\/><polyline points="48,268 10,242 332,182 " name="svg" class="linestring vector" fill="none" stroke="#B02EE0" stroke-width="2" id="svg)(\d+)("\/>)$/'
            )
        );
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS MULTILINESTRING object
     * @param string $line_color color for the GIS MULTILINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        $spatial, $srid, $label, $line_color, $scale_data, $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial, $srid, $label, $line_color, $scale_data
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                4326,
                'Ol',
                '#B02EE0',
                array(
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ),
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));vectorLayer.addFeatures(new OpenLayers.Feature.Vector(new OpenLayers.Geometry.MultiLineString(new Array(new OpenLayers.Geometry.LineString(new Array((new OpenLayers.Geometry.Point(36,14)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(47,23)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(62,75)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()))), new OpenLayers.Geometry.LineString(new Array((new OpenLayers.Geometry.Point(36,10)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(17,23)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(178,53)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()))))), null, {"strokeColor":"#B02EE0","strokeWidth":2,"label":"Ol","fontSize":10}));'
            )
        );
    }
}
?>
