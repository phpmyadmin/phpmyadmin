<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
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
     * @dataProvider providerForTestFactory
     * @return void
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
