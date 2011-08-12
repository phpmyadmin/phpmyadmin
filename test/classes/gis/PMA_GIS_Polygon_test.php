<?php
/**
 * Test for PMA_GIS_Polygon
 *
 * @package phpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_polygon.php';

/**
 * Tests for PMA_GIS_Polygon class
 */
class PMA_GIS_PolygonTest extends PHPUnit_Framework_TestCase
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
     * @return nothing
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
     * @return nothing
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
     * test generateWkt method
     *
     * @param array  $gis_data array of GIS data
     * @param int    $index    index
     * @param string $empty    string to be insterted in place of missing values
     * @param string $wkt      expected WKT
     *
     * @return nothing
     * @dataProvider providerForTestGenerateWkt
     */
    public function testGenerateWkt($gis_data, $index, $empty, $wkt)
    {
        if ($empty == null) {
            $this->assertEquals($this->object->generateWkt($gis_data, $index), $wkt);
        } else {
            $this->assertEquals(
                $this->object->generateWkt($gis_data, $index, $empty),
                $wkt
            );
        }
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
            )
        );
    }

    /**
     * test generateParams method
     *
     * @param string $wkt    point in WKT form
     * @param index  $index  index
     * @param array  $params expected output array
     *
     * @dataProvider providerForTestGenerateParams
     * @return nothing
     */
    public function testGenerateParams($wkt, $index, $params)
    {
        if ($index == null) {
            $this->assertEquals($this->object->generateParams($wkt), $params);
        } else {
            $this->assertEquals(
                $this->object->generateParams($wkt, $index),
                $params
            );
        }
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
}
?>