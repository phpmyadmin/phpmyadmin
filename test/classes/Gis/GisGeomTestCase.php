<?php
/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Abstract parent class for all Gis<Geom_type> test classes
 */
abstract class GisGeomTestCase extends AbstractTestCase
{
    /** @var GisGeometry */
    protected $object;

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
}
