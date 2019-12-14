<?php
/**
 * Test for PhpMyAdmin\Gis\GisFactory
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test class for PhpMyAdmin\Gis\GisFactory
 *
 * @package PhpMyAdmin-test
 */
class GisFactoryTest extends TestCase
{

    /**
     * Test factory method
     *
     * @param string $type geometry type
     * @param string $geom geometry object
     *
     * @return void
     *
     * @dataProvider providerForTestFactory
     */
    public function testFactory(string $type, string $geom): void
    {
        $this->assertInstanceOf($geom, GisFactory::factory($type));
    }

    /**
     * data provider for testFactory
     *
     * @return array[] data for testFactory
     */
    public function providerForTestFactory(): array
    {
        return [
            [
                'MULTIPOLYGON',
                'PhpMyAdmin\Gis\GisMultiPolygon',
            ],
            [
                'POLYGON',
                'PhpMyAdmin\Gis\GisPolygon',
            ],
            [
                'MULTILINESTRING',
                'PhpMyAdmin\Gis\GisMultiLineString',
            ],
            [
                'LINESTRING',
                'PhpMyAdmin\Gis\GisLineString',
            ],
            [
                'MULTIPOINT',
                'PhpMyAdmin\Gis\GisMultiPoint',
            ],
            [
                'POINT',
                'PhpMyAdmin\Gis\GisPoint',
            ],
            [
                'GEOMETRYCOLLECTION',
                'PhpMyAdmin\Gis\GisGeometryCollection',
            ],
        ];
    }
}
