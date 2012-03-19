<?php
/**
 * Test for PMA_GIS_Multipolygon
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_multipolygon.php';

/**
 * Tests for PMA_GIS_Multipolygon class
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
}
?>
