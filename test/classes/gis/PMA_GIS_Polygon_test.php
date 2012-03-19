<?php
/**
 * Test for PMA_GIS_Polygon
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_polygon.php';

/**
 * Tests for PMA_GIS_Polygon class
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
                "'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',124",
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
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                array(
                    'minX' => 10,
                    'maxX' => 45,
                    'minY' => 10,
                    'maxY' => 45
                )
            ),
        );
    }
}
?>