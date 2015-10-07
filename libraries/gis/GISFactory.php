<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains the factory class that handles the creation of geometric objects
 *
 * @package PhpMyAdmin-GIS
 */

namespace PMA\libraries\gis;

use PMA;

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Factory class that handles the creation of geometric objects.
 *
 * @package PhpMyAdmin-GIS
 */
class GISFactory
{
    /**
     * Returns the singleton instance of geometric class of the given type.
     *
     * @param string $type type of the geometric object
     *
     * @return GISGeometry the singleton instance of geometric class
     *                          of the given type
     *
     * @access public
     * @static
     */
    public static function factory($type)
    {
        $type_lower = strtolower($type);
        $file = './libraries/gis/GIS' . ucfirst($type_lower) . '.php';
        if (!PMA_isValid($type_lower, PMA\libraries\Util::getGISDatatypes())
            || !file_exists($file)
        ) {
            return false;
        }
        if (include_once $file) {
            switch (strtoupper($type)) {
            case 'MULTIPOLYGON' :
                return GISMultipolygon::singleton();
            case 'POLYGON' :
                return GISPolygon::singleton();
            case 'MULTIPOINT' :
                return GISMultipoint::singleton();
            case 'POINT' :
                return GISPoint::singleton();
            case 'MULTILINESTRING' :
                return GISMultilinestring::singleton();
            case 'LINESTRING' :
                return GISLinestring::singleton();
            case 'GEOMETRYCOLLECTION' :
                return GISGeometrycollection::singleton();
            default :
                return false;
            }
        } else {
            return false;
        }
    }
}
