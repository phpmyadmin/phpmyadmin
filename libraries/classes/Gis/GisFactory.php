<?php
/**
 * Contains the factory class that handles the creation of geometric objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use function strtoupper;

/**
 * Factory class that handles the creation of geometric objects.
 */
class GisFactory
{
    /**
     * Returns the singleton instance of geometric class of the given type.
     *
     * @param string $type type of the geometric object
     *
     * @return GisGeometry|false the singleton instance of geometric class of the given type
     */
    public static function factory(string $type): GisGeometry|false
    {
        return match (strtoupper($type)) {
            'MULTIPOLYGON' => GisMultiPolygon::singleton(),
            'POLYGON' => GisPolygon::singleton(),
            'MULTIPOINT' => GisMultiPoint::singleton(),
            'POINT' => GisPoint::singleton(),
            'MULTILINESTRING' => GisMultiLineString::singleton(),
            'LINESTRING' => GisLineString::singleton(),
            'GEOMETRYCOLLECTION' => GisGeometryCollection::singleton(),
            default => false,
        };
    }
}
