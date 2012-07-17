<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Point
 *
 * @package PhpMyAdmin-test
 */

require_once 'PMA_GIS_Geom_test.php';
require_once 'libraries/gis/pma_gis_geometry.php';
require_once 'libraries/gis/pma_gis_point.php';
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
     *
     * @param type $spatial
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $image
     * @param type $output
     *
     *@dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng($spatial, $label, $line_color, $scale_data, $image, $output)
    {

        $return = $this->object->prepareRowAsPng($spatial, $label, $line_color, $scale_data, $image);
        $this->assertTrue(true);
    }

    public function providerForPrepareRowAsPng(){

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
                imagecreatetruecolor('120','150'),
                ''
            )

        );
    }

    /**
     *
     * @param type $spatial
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $pdf
     *
     *@dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {

        $return = $this->object->prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf);
        $this->assertTrue($return instanceof TCPDF);
    }

    public function providerForPrepareRowAsPdf(){

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
     *
     * @param type $spatial
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $output
     *
     *@dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg($spatial, $label, $line_color, $scale_data, $output)
    {

        $this->assertEquals($output, $this->object->prepareRowAsSvg($spatial, $label, $line_color, $scale_data));
//        $this->assertEquals($this->object->prepareRowAsSvg($spatial, $label, $line_color, $scale_data) , $output);
    }

    public function providerForPrepareRowAsSvg(){

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
     *
     * @param type $spatial
     * @param type $srid
     * @param type $label
     * @param type $line_color
     * @param type $scale_data
     * @param type $output
     *
     *@dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl($spatial, $srid, $label, $line_color, $scale_data, $output)
    {

        $this->assertEquals($this->object->prepareRowAsOl($spatial, $srid, $label, $line_color, $scale_data) , $output);
    }

    public function providerForPrepareRowAsOl(){

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
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));vectorLayer.addFeatures(new OpenLayers.Feature.Vector((new OpenLayers.Geometry.Point(12,35)).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()), null, {"pointRadius":3,"fillColor":"#ffffff","strokeColor":"#B02EE0","strokeWidth":2,"label":"Ol","labelYOffset":-8,"fontSize":10}));'
            )
        );
    }
}
?>
