<?php
/**
 * Test for PMA_GIS_Geometry
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';

/**
 * Tests for PMA_GIS_Geometry class
 */
class PMA_GIS_GeometryTest extends PHPUnit_Framework_TestCase
{
    /**
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
        $this->object = $this->getMockForAbstractClass('PMA_GIS_Geometry');
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
     * Call protected functions by making the visibitlity to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_GIS_Geometry');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * tests setMinMax method
     *
     * @param string $point_set Point set
     * @param array  $min_max   Existing min, max values
     * @param array  $output    Expected output array
     *
     * @dataProvider providerForTestSetMinMax
     * @return void
     */
    public function testSetMinMax($point_set, $min_max, $output)
    {
        $this->assertEquals(
            $this->_callProtectedFunction(
                'setMinMax',
                array($point_set, $min_max)
            ),
            $output
        );
    }

    /**
     * data provider for testSetMinMax
     *
     * @return data for testSetMinMax
     */
    public function providerForTestSetMinMax()
    {
        return array(
            array(
                '12 35,48 75,69 23,25 45,14 53,35 78',
                array(),
                array(
                    'minX' => 12,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 78
                )
            ),
            array(
                '12 35,48 75,69 23,25 45,14 53,35 78',
                array(
                    'minX' => 2,
                    'maxX' => 29,
                    'minY' => 23,
                    'maxY' => 128
                ),
                array(
                    'minX' => 2,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 128
                )
            )
        );
    }

    /**
     * tests generateParams method
     *
     * @param string $value  Geometry data
     * @param string $output Expected output
     *
     * @dataProvider providerForTestGenerateParams
     * @return void
     */
    public function testGenerateParams($value, $output)
    {
        $this->assertEquals(
            $this->_callProtectedFunction(
                'generateParams',
                array($value)
            ),
            $output
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
                "'MULTIPOINT(125 50,156 25,178 43,175 80)',125",
                array(
                    'srid' => '125',
                    'wkt'  => 'MULTIPOINT(125 50,156 25,178 43,175 80)',
                ),
            ),
            array(
                'MULTIPOINT(125 50,156 25,178 43,175 80)',
                array(
                    'srid' => '0',
                    'wkt'  => 'MULTIPOINT(125 50,156 25,178 43,175 80)',
                ),
            ),
            array(
                "foo",
                array(
                    'srid' => '0',
                    'wkt'  => '',
                ),
            ),
        );
    }

    /**
     * tests extractPoints method
     *
     * @param string  $point_set  String of comma sperated points
     * @param array   $scale_data Data related to scaling
     * @param boolean $linear     If true, as a 1D array, else as a 2D array
     * @param array   $output     Expected output
     *
     * @dataProvider providerForTestExtractPoints
     * @return void
     */
    public function testExtractPoints($point_set, $scale_data, $linear, $output)
    {
        $this->assertEquals(
            $this->_callProtectedFunction(
                'extractPoints',
                array($point_set, $scale_data, $linear)
            ),
            $output
        );
    }

    /**
     * data provider for testExtractPoints
     *
     * @return data for testExtractPoints
     */
    public function providerForTestExtractPoints()
    {
        return array(
            // with no scale data
            array(
                '12 35,48 75,69 23',
                null,
                false,
                array(
                    0 => array(12, 35),
                    1 => array(48, 75),
                    2 => array(69, 23),
                ),
            ),
            // with scale data
            array(
                '12 35,48 75,69 23',
                array(
                    'x'      => 5,
                    'y'      => 5,
                    'scale'  => 2,
                    'height' => 200,
                ),
                false,
                array(
                    0 => array(14, 140),
                    1 => array(86, 60),
                    2 => array(128, 164),
                ),
            ),
            // linear output
            array(
                '12 35,48 75,69 23',
                null,
                true,
                array(12, 35, 48, 75, 69, 23),
            ),
            // if a single part of a coordinate is empty
            array(
                '12 35,48 75,69 ',
                null,
                false,
                array(
                    0 => array(12, 35),
                    1 => array(48, 75),
                    2 => array('', ''),
                ),
            ),
        );
    }
}
?>