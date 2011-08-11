<?php
/**
 * Test for PMA_GIS_Point
 *
 * @package phpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_point.php';

/**
 * Tests for PMA_GIS_Point class.
 */
class PMA_GIS_PointTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    PMA_GIS_Point
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
        $this->object = PMA_GIS_Point::singleton();
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

    /**
     * test generateWkt method
     *
     * @param array  $gis_data array containing the GIS data
     * @param int    $index    index to the array
     * @param string $wkt      expected Well Known Text
     *
     * @dataProvider providerForTestGenerateWkt
     * @return nothing
     */
    public function testGenerateWkt($gis_data, $index, $wkt)
    {
        $this->assertEquals($this->object->generateWkt($gis_data, $index), $wkt);
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return data for testGenerateWkt
     */
    public function providerForTestGenerateWkt()
    {
        return array(
            array(
                array(0 => array('POINT' => array('x' => 5.02, 'y' => 8.45))),
                0,
                'POINT(5.02 8.45)'
            ),
            array(
                array(0 => array('POINT' => array('x' => 5.02, 'y' => 8.45))),
                1,
                'POINT( )'
            ),
            array(
                array(0 => array('POINT' => array('x' => 5.02))),
                0,
                'POINT(5.02 )'
            ),
            array(
                array(0 => array('POINT' => array('y' => 8.45))),
                0,
                'POINT( 8.45)'
            ),
            array(
                array(0 => array('POINT' => array())),
                0,
                'POINT( )'
            ),
        );
    }

    /**
     * test getShape method
     *
     * @param array  $row_data array of GIS data
     * @param string $shape    expected shape in WKT
     *
     * @dataProvider providerForTestGetShape
     * @return nothing
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
                array('x' => 5.02, 'y' => 8.45),
                'POINT(5.02 8.45)'
            )
        );
    }

    /**
     * test generateParams method
     *
     * @param string $wkt    point in WKT form
     * @param array  $params expected output array
     * @param index  $index  index
     *
     * @dataProvider providerForTestGenerateParams
     * @return nothing
     */
    public function testGenerateParams($wkt, $params, $index)
    {
        if ($index == 0) {
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
        return array(
            array(
                "'POINT(5.02 8.45)',124",
                array(
                    'srid' => '124',
                    0      => array(
                    	'POINT'    => array('x' => '5.02', 'y' => '8.45')
                    ),
                ),
                0
            ),
            array(
            	'POINT(5.02 8.45)',
                array(
                    2 => array(
                    	'gis_type' => 'POINT',
                        'POINT'    => array('x' => '5.02', 'y' => '8.45')
                    ),
                ),
                2
            )
        );
    }
}
?>