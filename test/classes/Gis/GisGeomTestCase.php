<?php
/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionProperty;
use TCPDF;

use function getcwd;
use function md5;
use function php_uname;

use const PHP_INT_MAX;

/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */
abstract class GisGeomTestCase extends AbstractTestCase
{
    protected string $testDir = '';

    protected GisGeometry $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = 'file://' . getcwd() . '/test/test_data/gis';
    }

    protected function getArch(): string
    {
        $arch = php_uname('m');
        if ($arch === 'x86_64' && PHP_INT_MAX === 2147483647) {
            $arch = 'x86';
        }

        return $arch;
    }

    /**
     * test generateParams method
     *
     * @param string $wkt    point in WKT form
     * @param array  $params expected output array
     *
     * @dataProvider providerForTestGenerateParams
     */
    public function testGenerateParams(string $wkt, array $params): void
    {
        $this->assertEquals(
            $params,
            $this->object->generateParams($wkt),
        );
    }

    /**
     * test scaleRow method
     *
     * @param string    $spatial spatial data of a row
     * @param ScaleData $min_max expected results
     *
     * @dataProvider providerForTestScaleRow
     */
    public function testScaleRow(string $spatial, ScaleData $min_max): void
    {
        $this->assertEquals(
            $min_max,
            $this->object->scaleRow($spatial),
        );
    }

    /**
     * Create a new pdf document with predictable timestamps and ids.
     *
     * @param string $id Used as a seed for the internal file_id
     *
     * @return TCPDF A pdf document with an empty page
     */
    protected static function createEmptyPdf(string $id): TCPDF
    {
        $pdf = new TCPDF();
        $prop = new ReflectionProperty($pdf, 'file_id');
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
