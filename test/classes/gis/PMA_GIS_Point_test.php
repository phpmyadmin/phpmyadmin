<?php
/**
 * Test for PMA_GIS_Point
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_point.php';

/**
 * Tests for PMA_GIS_Point class.
 */
class PMA_GIS_PointTest extends PMA_GIS_GeomTest
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
     * @return void
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
        return array(
            array(
                array(0 => array('POINT' => array('x' => 5.02, 'y' => 8.45))),
                0,
                null,
                'POINT(5.02 8.45)'
            ),
            array(
                array(0 => array('POINT' => array('x' => 5.02, 'y' => 8.45))),
                1,
                null,
                'POINT( )'
            ),
            array(
                array(0 => array('POINT' => array('x' => 5.02))),
                0,
                null,
                'POINT(5.02 )'
            ),
            array(
                array(0 => array('POINT' => array('y' => 8.45))),
                0,
                null,
                'POINT( 8.45)'
            ),
            array(
                array(0 => array('POINT' => array())),
                0,
                null,
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
                array('x' => 5.02, 'y' => 8.45),
                'POINT(5.02 8.45)'
            )
        );
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
                null,
                array(
                    'srid' => '124',
                    0      => array(
                        'POINT'    => array('x' => '5.02', 'y' => '8.45')
                    ),
                )
            ),
            array(
                'POINT(5.02 8.45)',
                2,
                array(
                    2 => array(
                        'gis_type' => 'POINT',
                        'POINT'    => array('x' => '5.02', 'y' => '8.45')
                    ),
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
                'POINT(12 35)',
                array(
                    'minX' => 12,
                    'maxX' => 12,
                    'minY' => 35,
                    'maxY' => 35,
                )
            )
        );
    }
}
?>