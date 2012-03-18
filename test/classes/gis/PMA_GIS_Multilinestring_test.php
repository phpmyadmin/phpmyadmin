<?php
/**
 * Test for PMA_GIS_Multilinestring
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_multilinestring.php';

/**
 * Tests for PMA_GIS_Multilinestring class
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
}
?>