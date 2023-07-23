<?php
/**
 * Contains the factory class that handles the creation of geometric objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use function mb_strpos;
use function mb_substr;
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
     * @return GisGeometry|null the singleton instance of geometric class of the given type
     */
    public static function fromType(string $type): GisGeometry|null
    {
        return match (strtoupper($type)) {
            'MULTIPOLYGON' => GisMultiPolygon::singleton(),
            'POLYGON' => GisPolygon::singleton(),
            'MULTIPOINT' => GisMultiPoint::singleton(),
            'POINT' => GisPoint::singleton(),
            'MULTILINESTRING' => GisMultiLineString::singleton(),
            'LINESTRING' => GisLineString::singleton(),
            'GEOMETRYCOLLECTION' => GisGeometryCollection::singleton(),
            default => null,
        };
    }

    /**
     * Returns the singleton instance of geometric class of the given wkt type.
     */
    public static function fromWkt(string $wkt): GisGeometry|null
    {
        $typePos = mb_strpos($wkt, '(');
        if ($typePos === false) {
            return null;
        }

        $type = mb_substr($wkt, 0, $typePos);

        return self::fromType($type);
    }
}
