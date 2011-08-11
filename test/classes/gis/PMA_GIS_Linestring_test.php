<?php
/**
 * Test for PMA_GIS_Linestring
 *
 * @package phpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_linestring.php';

/**
 * Tests for PMA_GIS_Linestring class
 */
class PMA_GIS_LinestringTest extends PHPUnit_Framework_TestCase
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
     * @return nothing
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
                0,
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
}
?>