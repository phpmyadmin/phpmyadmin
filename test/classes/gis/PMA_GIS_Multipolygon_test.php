<?php
/**
 * Test for PMA_GIS_Multipolygon
 *
 * @package phpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_multipolygon.php';

/**
 * Tests for PMA_GIS_Multipolygon class
 */
class PMA_GIS_MultipolygonTest extends PHPUnit_Framework_TestCase
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
     * @return nothing
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
     * @return nothing
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

        return array(
            array(
                $temp,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))'
            ),
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
}
?>
