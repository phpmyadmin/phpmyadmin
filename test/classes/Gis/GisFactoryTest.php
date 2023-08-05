<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisGeometryCollection;
use PhpMyAdmin\Gis\GisLineString;
use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Gis\GisMultiPolygon;
use PhpMyAdmin\Gis\GisPoint;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(GisFactory::class)]
class GisFactoryTest extends AbstractTestCase
{
    /**
     * Test fromType method
     *
     * @param string $type geometry type
     * @psalm-param class-string|null $classString geometry
     */
    #[DataProvider('providerForTestFromType')]
    public function testFromType(string $type, string|null $classString): void
    {
        $geometry = GisFactory::fromType($type);
        if ($classString === null) {
            $this->assertNull($geometry);
        } else {
            $this->assertInstanceOf($classString, $geometry);
        }
    }

    /**
     * data provider for testFactory
     *
     * @return array<array{string, class-string|null}>
     */
    public static function providerForTestFromType(): array
    {
        return [
            ['MULTIPOLYGON', GisMultiPolygon::class],
            ['POLYGON', GisPolygon::class],
            ['MULTILINESTRING', GisMultiLineString::class],
            ['LineString', GisLineString::class],
            ['MULTIPOINT', GisMultiPoint::class],
            ['point', GisPoint::class],
            ['GEOMETRYCOLLECTION', GisGeometryCollection::class],
            ['asdf', null],
        ];
    }

    /**
     * Test fromWkt method
     *
     * @param string $wkt Wkt string
     * @psalm-param class-string|null $classString geometry
     */
    #[DataProvider('providerForTestFromWkt')]
    public function testFromWkt(string $wkt, string|null $classString): void
    {
        $geometry = GisFactory::fromWkt($wkt);
        if ($classString === null) {
            $this->assertNull($geometry);
        } else {
            $this->assertInstanceOf($classString, $geometry);
        }
    }

    /**
     * data provider for testFromWkt
     *
     * @return array<array{string, class-string|null}>
     */
    public static function providerForTestFromWkt(): array
    {
        return [
            ['MULTIPOLYGON(((1 1,2 3,3 2,1 1)))', GisMultiPolygon::class],
            ['POLYGON((1 1,2 3,3 2,1 1))', GisPolygon::class],
            ['MULTILINESTRING((5 5, 5 7))', GisMultiLineString::class],
            ['LineString(2 3, 4 4)', GisLineString::class],
            ['MULTIPOINT(1 1,2 2)', GisMultiPoint::class],
            ['point(1 1)', GisPoint::class],
            ['GEOMETRYCOLLECTION()', GisGeometryCollection::class],
            ['asdf', null],
        ];
    }
}
