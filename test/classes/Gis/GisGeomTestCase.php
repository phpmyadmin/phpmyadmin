<?php
/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Tests\AbstractTestCase;
use TCPDF;

use function preg_match;

/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */
abstract class GisGeomTestCase extends AbstractTestCase
{
    /** @var GisGeometry */
    protected $object;

    /**
     * Test for generateWkt
     *
     * @param array       $gis_data array of GIS data
     * @param int         $index    index in $gis_data
     * @param string|null $empty    empty parameter
     * @param string      $output   expected output
     *
     * @dataProvider providerForTestGenerateWkt
     */
    public function testGenerateWkt(array $gis_data, int $index, ?string $empty, string $output): void
    {
        $this->assertEquals(
            $output,
            $this->object->generateWkt($gis_data, $index, $empty)
        );
    }

    /**
     * test generateParams method
     *
     * @param string   $wkt    point in WKT form
     * @param int|null $index  index
     * @param array    $params expected output array
     *
     * @dataProvider providerForTestGenerateParams
     */
    public function testGenerateParams(string $wkt, ?int $index, array $params): void
    {
        if ($index === null) {
            $this->assertEquals(
                $params,
                $this->object->generateParams($wkt)
            );

            return;
        }

        /** @var GisPolygon $obj or another GisGeometry that supports this definition */
        $obj = $this->object;
        $this->assertEquals(
            $params,
            $obj->generateParams($wkt, $index)
        );
    }

    /**
     * test scaleRow method
     *
     * @param string $spatial spatial data of a row
     * @param array  $min_max expected results
     *
     * @dataProvider providerForTestScaleRow
     */
    public function testScaleRow(string $spatial, array $min_max): void
    {
        $this->assertEquals(
            $min_max,
            $this->object->scaleRow($spatial)
        );
    }

    /**
     * test prepareRowAsPng method
     *
     * @dataProvider providerForTestPrepareRowAsPng
     * @requires extension gd
     */
    public function testPrepareRowAsPng(string $wkt): void
    {
        $image = ImageWrapper::create(120, 150);
        $this->assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            $wkt,
            'image',
            '#B02EE0',
            ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
            $image
        );
        $this->assertEquals(120, $return->width());
        $this->assertEquals(150, $return->height());
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS object
     * @param string $label      label for the GIS object
     * @param string $color      color for the GIS object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     * @psalm-param array{
     *   x: float,
     *   y: float,
     *   height: int,
     *   scale: float,
     *   minX: float,
     *   maxX: float,
     *   minY: float,
     *   maxY: float,
     * } $scale_data
     *
     * @dataProvider providerForTestPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        string $color,
        array $scale_data,
        TCPDF $pdf
    ): void {
        $return = $this->object->prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf);
        $this->assertInstanceOf(TCPDF::class, $return);
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial   GIS object
     * @param string $label     label for the GIS object
     * @param string $color     color for the GIS object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
     * @psalm-param array{
     *   x: float,
     *   y: float,
     *   height: int,
     *   scale: float,
     *   minX: float,
     *   maxX: float,
     *   minY: float,
     *   maxY: float,
     * } $scaleData
     *
     * @dataProvider providerForTestPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        string $color,
        array $scaleData,
        string $output
    ): void {
        $string = $this->object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals(1, preg_match($output, $string));
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS object
     * @param int[]  $color      color for the GIS object
     * @param array  $scale_data array containing data related to scaling
     * @param string $expected   expected output
     * @psalm-param array{
     *   x: float,
     *   y: float,
     *   height: int,
     *   scale: float,
     *   minX: float,
     *   maxX: float,
     *   minY: float,
     *   maxY: float,
     * } $scale_data
     *
     * @dataProvider providerForTestPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        array $scale_data,
        string $expected
    ): void {
        $actual = $this->object->prepareRowAsOl(
            $spatial,
            $srid,
            $label,
            $color,
            $scale_data
        );
        $this->assertEquals($expected, $actual);
    }
}
