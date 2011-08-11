<?php
/**
 * Test for PMA_GIS_Multipoint
 *
 * @package phpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_multipoint.php';

/**
 * Tests for PMA_GIS_Multipoint class
 */
class PMA_GIS_MultipointTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    PMA_GIS_Multipoint
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
        $this->object = PMA_GIS_Multipoint::singleton();
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
     * @param array  $gis_data array of GIS data
     * @param int    $index    index
     * @param string $wkt      expected WKT
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
                'MULTIPOINT(5.02 8.45,1.56 4.36)'
            ),
            array(
                $gis_data2,
                0,
                'MULTIPOINT(5.02 8.45)'
            )
        );
    }

    /**
     * test getShape method
     *
     * @return nothing
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
                0,
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
}
?>