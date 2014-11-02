<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Point
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/GIS_Geometry.class.php';
require_once 'libraries/gis/GIS_Point.class.php';
require_once 'libraries/tcpdf/tcpdf.php';

/**
 * Tests for PMA_GIS_Point class.
 *
 * @package PhpMyAdmin-test
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

    /**
     * test case for prepareRowAsPng() method
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       label for the GIS POINT object
     * @param string $point_color color for the GIS POINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param object $image       image object
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        $spatial, $label, $point_color, $scale_data, $image
    ) {
        $return = $this->object->prepareRowAsPng(
            $spatial, $label, $point_color, $scale_data, $image
        );
        $this->assertImage($return);
    }

    /**
     * data provider for testPrepareRowAsPng() test case
     *
     * @return array test data for testPrepareRowAsPng() test case
     */
    public function providerForPrepareRowAsPng()
    {
        return array(
            array(
                'POINT(12 35)',
                'image',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                imagecreatetruecolor('120', '150')
            )
        );
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       label for the GIS POINT object
     * @param string $point_color color for the GIS POINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param object $pdf         TCPDF instance
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        $spatial, $label, $point_color, $scale_data, $pdf
    ) {
        $return = $this->object->prepareRowAsPdf(
            $spatial, $label, $point_color, $scale_data, $pdf
        );
        $this->assertInstanceOf('TCPDF', $return);
    }

    /**
     * data provider for prepareRowAsPdf() test case
     *
     * @return array test data for prepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf()
    {
        return array(
            array(
                'POINT(12 35)',
                'pdf',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                new TCPDF(),
            )
        );
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       label for the GIS POINT object
     * @param string $point_color color for the GIS POINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param string $output      expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        $spatial, $label, $point_color, $scale_data, $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsSvg(
                $spatial, $label, $point_color, $scale_data
            )
        );
    }

    /**
     * data provider for prepareRowAsSvg() test case
     *
     * @return array test data for prepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg()
    {
        return array(
            array(
                'POINT(12 35)',
                'svg',
                '#B02EE0',
                array(
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150
                ),
                ''
            )
        );
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial     GIS POINT object
     * @param int    $srid        spatial reference ID
     * @param string $label       label for the GIS POINT object
     * @param string $point_color color for the GIS POINT object
     * @param array  $scale_data  array containing data related to scaling
     * @param string $output      expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        $spatial, $srid, $label, $point_color, $scale_data, $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial, $srid, $label, $point_color, $scale_data
            )
        );
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForPrepareRowAsOl()
    {
        return array(
            array(
                'POINT(12 35)',
                4326,
                'Ol',
                '#B02EE0',
                array(
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ),
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.'
                . 'LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject())); bound.extend(new OpenLayers.LonLat'
                . '(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject()));vectorLayer.addFeatures(new Open'
                . 'Layers.Feature.Vector((new OpenLayers.Geometry.Point(12,35)).'
                . 'transform(new OpenLayers.Projection("EPSG:4326"), map.get'
                . 'ProjectionObject()), null, {"pointRadius":3,"fillColor":"#ffffff"'
                . ',"strokeColor":"#B02EE0","strokeWidth":2,"label":"Ol","labelY'
                . 'Offset":-8,"fontSize":10}));'
            )
        );
    }
}
?>
