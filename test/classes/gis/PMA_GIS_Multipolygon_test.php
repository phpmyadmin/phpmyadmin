<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Multipolygon
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_multipolygon.php';
require_once 'libraries/tcpdf/tcpdf.php';

/**
 * Tests for PMA_GIS_Multipolygon class
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_MultipolygonTest extends PMA_GIS_GeomTest
{
    /**
     * @var    PMA_GIS_Multipolygon
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
        $this->object = PMA_GIS_Multipolygon::singleton();
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

    private function _getData()
    {
        return array(
            'MULTIPOLYGON' => array(
                'no_of_polygons' => 2,
                0 => array(
                    'no_of_lines' => 2,
                    0 => array(
                        'no_of_points' => 5,
                        0 => array('x' => 35, 'y' => 10),
                        1 => array('x' => 10, 'y' => 20),
                        2 => array('x' => 15, 'y' => 40),
                        3 => array('x' => 45, 'y' => 45),
                        4 => array('x' => 35, 'y' => 10),
                    ),
                    1 => array(
                        'no_of_points' => 4,
                        0 => array('x' => 20, 'y' => 30),
                        1 => array('x' => 35, 'y' => 32),
                        2 => array('x' => 30, 'y' => 20),
                        3 => array('x' => 20, 'y' => 30),
                    )
                ),
                1 => array(
                    'no_of_lines' => 1,
                    0 => array(
                        'no_of_points' => 4,
                        0 => array('x' => 123, 'y' => 0),
                        1 => array('x' => 23, 'y' => 30),
                        2 => array('x' => 17, 'y' => 63),
                        3 => array('x' => 123, 'y' => 0),
                    )
                )
            )
        );
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return data for testGenerateWkt
     */
    public function providerForTestGenerateWkt()
    {
        $temp = array(
            0 => $this->_getData()
        );

        $temp1 = $temp;
        $temp1[0]['MULTIPOLYGON']['no_of_polygons'] = 0;

        $temp2 = $temp;
        $temp2[0]['MULTIPOLYGON'][1]['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTIPOLYGON'][1][0]['no_of_points'] = 3;

        return array(
            array(
                $temp,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))'
            ),
            // at lease one polygon should be there
            array(
                $temp1,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)))'
            ),
            // a polygon should have atleast one ring
            array(
                $temp2,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))'
            ),
            // a ring should have atleast four points
            array(
                $temp3,
                0,
                '0',
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))'
            ),
        );
    }

    /**
     * data provider for testGenerateParams
     *
     * @return data for testGenerateParams
     */
    public function providerForTestGenerateParams()
    {
        $temp = $this->_getData();

        $temp1 = $this->_getData();
        $temp1['gis_type'] = 'MULTIPOLYGON';

        return array(
            array(
                "'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),"
                . "(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',124",
                null,
                array(
                    'srid' => '124',
                    0 => $temp
                )
            ),
            array(
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
                2,
                array(
                    2 => $temp1
                )
            )
        );
    }

    /**
     * test getShape method
     *
     * @param array  $row_data array of GIS data
     * @param string $shape    expected shape in WKT
     *
     * @dataProvider providerForTestGetShape
     * @return void
     */
    public function testGetShape($row_data, $shape)
    {
        $this->assertEquals($this->object->getShape($row_data), $shape);
    }

    /**
     * data provider for testGetShape
     *
     * @return data for testGetShape
     */
    public function providerForTestGetShape()
    {
        return array(
            array(
                array(
                    'parts' => array(
                        0 => array(
                            'points' => array(
                                0 => array('x' => 10, 'y' => 10),
                                1 => array('x' => 10, 'y' => 40),
                                2 => array('x' => 50, 'y' => 40),
                                3 => array('x' => 50, 'y' => 10),
                                4 => array('x' => 10, 'y' => 10),
                            ),
                        ),
                        1 => array(
                            'points' => array(
                                0 => array('x' => 60, 'y' => 40),
                                1 => array('x' => 75, 'y' => 65),
                                2 => array('x' => 90, 'y' => 40),
                                3 => array('x' => 60, 'y' => 40),
                            ),
                        ),
                        2 => array(
                            'points' => array(
                                0 => array('x' => 20, 'y' => 20),
                                1 => array('x' => 40, 'y' => 20),
                                2 => array('x' => 25, 'y' => 30),
                                3 => array('x' => 20, 'y' => 20),
                            ),
                        ),
                    ),
                ),
                'MULTIPOLYGON(((10 10,10 40,50 40,50 10,10 10),(20 20,40 20,25 30'
                    . ',20 20)),((60 40,75 65,90 40,60 40)))'
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
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                array(
                    'minX' => 16,
                    'maxX' => 147,
                    'minY' => 0,
                    'maxY' => 83
                )
            ),
            array(
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20'
                    . ',20 30)),((105 0,56 20,78 73,105 0)))',
                array(
                    'minX' => 10,
                    'maxX' => 105,
                    'minY' => 0,
                    'maxY' => 73
                )
            )
        );
    }


    /**
     *
     * @param type $spatial
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $image
     * @param type $output
     *
     *@dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng($spatial, $label, $line_color, $scale_data, $image, $output)
    {

        $return = $this->object->prepareRowAsPng($spatial, $label, $line_color, $scale_data, $image);
        $this->assertTrue(true);
    }

    public function providerForPrepareRowAsPng(){

        return array(
            array(
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                'image',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                imagecreatetruecolor('120','150'),
                ''
            )

        );
    }

    /**
     *
     * @param type $spatial
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $pdf
     *
     *@dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {

        $return = $this->object->prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf);
        $this->assertTrue($return instanceof TCPDF);
    }

    public function providerForPrepareRowAsPdf(){

        return array(
            array(
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
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
     *
     * @param type $spatial
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $output
     *
     *@dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg($spatial, $label, $line_color, $scale_data, $output)
    {

        $string = $this->object->prepareRowAsSvg($spatial, $label, $line_color, $scale_data);
        $this->assertEquals(1, preg_match($output, $string));
//        $this->assertEquals($this->object->prepareRowAsSvg($spatial, $label, $line_color, $scale_data) , $output);
    }

    public function providerForPrepareRowAsSvg(){

        return array(
            array(
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                'svg',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                '/^(<path d=" M 248, 208 L 270, 122 L 8, 138 Z " name="svg" class="multipolygon vector" stroke="black" stroke-width="0.5" fill="#B02EE0" fill-rule="evenodd" fill-opacity="0.8" id="svg)(\d+)("\/><path d=" M 186, 288 L 88, 248 L 132, 142 Z " name="svg" class="multipolygon vector" stroke="black" stroke-width="0.5" fill="#B02EE0" fill-rule="evenodd" fill-opacity="0.8" id="svg)(\d+)("\/>)$/'
            )
        );
    }

    /**
     *
     * @param type $spatial
     * @param type $srid
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $output
     *
     *@dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl($spatial, $srid, $label, $line_color, $scale_data, $output)
    {

        $this->assertEquals($this->object->prepareRowAsOl($spatial, $srid, $label, $line_color, $scale_data) , $output);
    }

    public function providerForPrepareRowAsOl(){

        return array(
            array(
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                4326,
                'Ol',
                '#B02EE0',
                array(
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ),
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));vectorLayer.addFeatures(new OpenLayers.Feature.Vector(new OpenLayers.Geometry.MultiPolygon(new Array(new OpenLayers.Geometry.Polygon(new Array(new OpenLayers.Geometry.LinearRing(new Array((new OpenLayers.Geometry.Point(136,40)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(147,83)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(16,75)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(136,40)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()))))), new OpenLayers.Geometry.Polygon(new Array(new OpenLayers.Geometry.LinearRing(new Array((new OpenLayers.Geometry.Point(105,0)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(56,20)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(78,73)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(105,0)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()))))))), null, {"strokeColor":"#000000","strokeWidth":0.5,"fillColor":"#B02EE0","fillOpacity":0.8,"label":"Ol","fontSize":10}));'
            )
        );
    }
}
?>
