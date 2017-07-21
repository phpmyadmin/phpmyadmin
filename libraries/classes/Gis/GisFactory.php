<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains the factory class that handles the creation of geometric objects
 *
 * @package PhpMyAdmin-GIS
 */
namespace PhpMyAdmin\Gis;

/**
 * Factory class that handles the creation of geometric objects.
 *
 * @package PhpMyAdmin-GIS
 */
class GisFactory
{
    /**
     * Returns the singleton instance of geometric class of the given type.
     *
     * @param string $type type of the geometric object
     *
     * @return GisGeometry the singleton instance of geometric class
     *                          of the given type
     *
     * @access public
     * @static
     */
    public static function factory($type)
    {
        switch (strtoupper($type)) {
        case 'MULTIPOLYGON' :
            return GisMultiPolygon::singleton();
        case 'POLYGON' :
            return GisPolygon::singleton();
        case 'MULTIPOINT' :
            return GisMultiPoint::singleton();
        case 'POINT' :
            return GisPoint::singleton();
        case 'MULTILINESTRING' :
            return GisMultiLineString::singleton();
        case 'LINESTRING' :
            return GisLineString::singleton();
        case 'GEOMETRYCOLLECTION' :
            return GisGeometryCollection::singleton();
        default :
            return false;
        }
    }
}
