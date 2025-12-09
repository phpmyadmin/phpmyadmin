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
    public static function fromType(string $type): GisGeometry|null
    {
        return match (strtoupper($type)) {
            'MULTIPOLYGON' => new GisMultiPolygon(),
            'POLYGON' => new GisPolygon(),
            'MULTIPOINT' => new GisMultiPoint(),
            'POINT' => new GisPoint(),
            'MULTILINESTRING' => new GisMultiLineString(),
            'LINESTRING' => new GisLineString(),
            'GEOMETRYCOLLECTION' => new GisGeometryCollection(),
            default => null,
        };
    }

    /**
     * Returns the instance of geometric class of the given wkt type.
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
