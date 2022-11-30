<?php
/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionProperty;
use TCPDF;

use function getcwd;
use function md5;

/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */
abstract class GisGeomTestCase extends AbstractTestCase
{
    /** @var string */
    protected $testDir = '';

    /** @var GisGeometry */
    protected $object;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = 'file://' . getcwd() . '/test/test_data/gis';
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
     * Create a new pdf document with predictable timestamps and ids.
     *
     * @param string $id Used as a seed for the internal file_id
     *
     * @return TCPDF A pdf document with an empty page
     */
    protected function createEmptyPdf(string $id): TCPDF
    {
        $pdf = new TCPDF();
        $prop = new ReflectionProperty($pdf, 'file_id');
        $prop->setAccessible(true);
        $prop->setValue($pdf, md5($id));
        $pdf->setDocCreationTimestamp(1600000000);
        $pdf->setDocModificationTimestamp(1600000000);
        $pdf->setAutoPageBreak(false);
        $pdf->setCompression(false);
        $pdf->setPrintFooter(false);
        $pdf->setPrintHeader(false);
        $pdf->AddPage();

        return $pdf;
    }
}
