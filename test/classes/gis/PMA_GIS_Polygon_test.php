<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Polygon
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_polygon.php';
require_once 'libraries/tcpdf/tcpdf.php';
require_once 'libraries/Util.class.php';

/**
 * Tests for PMA_GIS_Polygon class
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_PolygonTest extends PMA_GIS_GeomTest
{
    /**
     * @var    PMA_GIS_Polygon
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
        $this->object = PMA_GIS_Polygon::singleton();
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
     * Provide some common data to data providers
     *
     * @return array common data for data providers
     */
    private function _getData()
    {
        return array(
            'POLYGON' => array(
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
        unset($temp1[0]['POLYGON'][1][3]['y']);

        $temp2 = $temp;
        $temp2[0]['POLYGON']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['POLYGON'][1]['no_of_points'] = 3;

        return array(
            array(
                $temp,
                0,
                null,
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'
            ),
            // values at undefined index
            array(
                $temp,
                1,
                null,
                'POLYGON(( , , , ))'
            ),
            // if a coordinate is missing, default is empty string
            array(
                $temp1,
                0,
                null,
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 ))'
            ),
            // missing coordinates are replaced with provided values (3rd parameter)
            array(
                $temp1,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 0))'
            ),
            // should have atleast one ring
            array(
                $temp2,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10))'
            ),
            // a ring should have atleast four points
            array(
                $temp3,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'
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

        $temp1 = $temp;
        $temp1['gis_type'] = 'POLYGON';

        return array(
            array(
                "'POLYGON((35 10,10 20,15 40,45 45,35 10),"
                    . "(20 30,35 32,30 20,20 30))',124",
                null,
                array(
                    'srid' => '124',
                    0 => $temp
                )
            ),
            array(
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',
                2,
                array(
                    2 => $temp1
                )
            )
        );
    }

    /**
     * test for Area
     *
     * @param array $ring array of points forming the ring
     * @param fload $area area of the ring
     *
     * @dataProvider providerForTestArea
     * @return void
     */
    public function testArea($ring, $area)
    {
        $this->assertEquals($this->object->area($ring), $area);
    }

    /**
     * data provider for testArea
     *
     * @return data for testArea
     */
    public function providerForTestArea()
    {
        return array(
            array(
                array(
                    0 => array('x' => 35, 'y' => 10),
                    1 => array('x' => 10, 'y' => 10),
                    2 => array('x' => 15, 'y' => 40)
                ),
                -375.00
            ),
            // first point of the ring repeated as the last point
            array(
                array(
                    0 => array('x' => 35, 'y' => 10),
                    1 => array('x' => 10, 'y' => 10),
                    2 => array('x' => 15, 'y' => 40),
                    3 => array('x' => 35, 'y' => 10)
                ),
                -375.00
            ),
            // anticlockwise gives positive area
            array(
                array(
                    0 => array('x' => 15, 'y' => 40),
                    1 => array('x' => 10, 'y' => 10),
                    2 => array('x' => 35, 'y' => 10)
                ),
                375.00
            )
        );
    }

    /**
     * test for isPointInsidePolygon
     *
     * @param array $point    x, y coordinates of the point
     * @param array $polygon  array of points forming the ring
     * @param bool  $isInside output
     *
     * @dataProvider providerForTestIsPointInsidePolygon
     * @return void
     */
    public function testIsPointInsidePolygon($point, $polygon, $isInside)
    {
        $this->assertEquals(
            $this->object->isPointInsidePolygon($point, $polygon),
            $isInside
        );
    }

    /**
     * data provider for testIsPointInsidePolygon
     *
     * @return data for testIsPointInsidePolygon
     */
    public function providerForTestIsPointInsidePolygon()
    {
        $ring = array(
            0 => array('x' => 35, 'y' => 10),
            1 => array('x' => 10, 'y' => 10),
            2 => array('x' => 15, 'y' => 40),
            3 => array('x' => 35, 'y' => 10)
        );

        return array(
            // point inside the ring
            array(
                array('x' => 20, 'y' => 15),
                $ring,
                true
            ),
            // point on an edge of the ring
            array(
                array('x' => 20, 'y' => 10),
                $ring,
                false
            ),
            // point on a vertex of the ring
            array(
                array('x' => 10, 'y' => 10),
                $ring,
                false
            ),
            // point outside the ring
            array(
                array('x' => 5, 'y' => 10),
                $ring,
                false
            ),
        );
    }

    /**
     * test for getPointOnSurface
     *
     * @param array $ring array of points forming the ring
     *
     * @dataProvider providerForTestGetPointOnSurface
     * @return void
     */
    public function testGetPointOnSurface($ring)
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
     * @return data for testGetPointOnSurface
     */
    public function providerForTestGetPointOnSurface()
    {
        $temp = $this->_getData();
        unset($temp['POLYGON'][0]['no_of_points']);
        unset($temp['POLYGON'][1]['no_of_points']);

        return array(
            array(
                $temp['POLYGON'][0]
            ),
            array(
                $temp['POLYGON'][1]
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
                'POLYGON((123 0,23 30,17 63,123 0))',
                array(
                    'minX' => 17,
                    'maxX' => 123,
                    'minY' => 0,
                    'maxY' => 63,
                )
            ),
            array(
                'POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
                array(
                    'minX' => 10,
                    'maxX' => 45,
                    'minY' => 10,
                    'maxY' => 45
                )
            ),
        );
    }

    /**
     * test case for prepareRowAsPng()
     *
     * @param string $spatial    GIS POLYGON object
     * @param string $label      label for the GIS POLYGON object
     * @param string $fill_color color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param object $image      image object
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        $spatial, $label, $fill_color, $scale_data, $image, $output
    ) {
        $return = $this->object->prepareRowAsPng(
            $spatial, $label, $fill_color, $scale_data, $image
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
                'POLYGON((123 0,23 30,17 63,123 0))',
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
     * @param string $spatial    GIS POLYGON object
     * @param string $label      label for the GIS POLYGON object
     * @param string $fill_color color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param object $pdf        TCPDF instance
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        $spatial, $label, $fill_color, $scale_data, $pdf
    ) {
        $return = $this->object->prepareRowAsPdf(
            $spatial, $label, $fill_color, $scale_data, $pdf
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
                'POLYGON((123 0,23 30,17 63,123 0))',
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
     * @param string $spatial    GIS POLYGON object
     * @param string $label      label for the GIS POLYGON object
     * @param string $fill_color color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        $spatial, $label, $fill_color, $scale_data, $output
    ) {
        $string = $this->object->prepareRowAsSvg(
            $spatial, $label, $fill_color, $scale_data
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
                'POLYGON((123 0,23 30,17 63,123 0))',
                'svg',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                '/^(<path d=" M 222, 288 L 22, 228 L 10, 162 Z " name="svg" id="svg)(\d+)(" class="polygon vector" stroke="black" stroke-width="0.5" fill="#B02EE0" fill-rule="evenodd" fill-opacity="0.8"\/>)$/'
            )
        );
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS POLYGON object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS POLYGON object
     * @param string $fill_color color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        $spatial, $srid, $label, $fill_color, $scale_data, $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial, $srid, $label, $fill_color, $scale_data
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
                'POLYGON((123 0,23 30,17 63,123 0))',
                4326,
                'Ol',
                '#B02EE0',
                array(
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ),
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));vectorLayer.addFeatures(new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Polygon(new Array(new OpenLayers.Geometry.LinearRing(new Array((new OpenLayers.Geometry.Point(123,0)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(23,30)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(17,63)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geometry.Point(123,0)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()))))), null, {"strokeColor":"#000000","strokeWidth":0.5,"fillColor":"#B02EE0","fillOpacity":0.8,"label":"Ol","fontSize":10}));'
            )
        );
    }

    /**
     * test case for isOuterRing() method
     *
     * @param array $ring coordinates of the points in a ring
     *
     * @return void
     * @dataProvider providerForIsOuterRing
     */
    public function testIsOuterRing($ring)
    {
        $this->assertTrue($this->object->isOuterRing($ring));
    }

    /**
     * data provider for testIsOuterRing() test case
     *
     * @return array test data for testIsOuterRing() test case
     */
    public function providerForIsOuterRing()
    {
        return array(
            array(
                array(
                    array('x' => 0, 'y' => 0),
                    array('x' => 0, 'y' => 1),
                    array('x' => 1, 'y' => 1),
                    array('x' => 1, 'y' => 0)
                ),
            )
        );
    }
}
?>
