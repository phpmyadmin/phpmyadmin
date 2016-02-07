<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for all GIS data type classes
 *
 * @package PhpMyAdmin-GIS
 */

namespace PMA\libraries\gis;

use \TCPDF;

/**
 * Base class for all GIS data type classes.
 *
 * @package PhpMyAdmin-GIS
 */
abstract class GISGeometry
{
    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS data object
     * @param string $label      label for the GIS data object
     * @param string $color      color for the GIS data object
     * @param array  $scale_data data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     * @access public
     */
    public abstract function prepareRowAsSvg($spatial, $label, $color, $scale_data);

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS data object
     * @param string $label      label for the GIS data object
     * @param string $color      color for the GIS data object
     * @param array  $scale_data array containing data related to scaling
     * @param object $image      image object
     *
     * @return object the modified image object
     * @access public
     */
    public abstract function prepareRowAsPng(
        $spatial,
        $label,
        $color,
        $scale_data,
        $image
    );

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS data object
     * @param string $label      label for the GIS data object
     * @param string $color      color for the GIS data object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     * @access public
     */
    public abstract function prepareRowAsPdf(
        $spatial,
        $label,
        $color,
        $scale_data,
        $pdf
    );

    /**
     * Prepares the JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS data object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS data object
     * @param string $color      color for the GIS data object
     * @param array  $scale_data array containing data related to scaling
     *
     * @return string the JavaScript related to a row in the GIS dataset
     * @access public
     */
    public abstract function prepareRowAsOl(
        $spatial,
        $srid,
        $label,
        $color,
        $scale_data
    );

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array array containing the min, max values for x and y coordinates
     * @access public
     */
    public abstract function scaleRow($spatial);

    /**
     * Generates the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    index into the parameter object
     * @param string $empty    value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     * @access public
     */
    public abstract function generateWkt($gis_data, $index, $empty = '');

    /**
     * Returns OpenLayers.Bounds object that correspond to the bounds of GIS data.
     *
     * @param string $srid       spatial reference ID
     * @param array  $scale_data data related to scaling
     *
     * @return string OpenLayers.Bounds object that
     *                correspond to the bounds of GIS data
     * @access protected
     */
    protected function getBoundsForOl($srid, $scale_data)
    {
        return 'bound = new OpenLayers.Bounds(); '
        . 'bound.extend(new OpenLayers.LonLat('
        . $scale_data['minX'] . ', ' . $scale_data['minY']
        . ').transform(new OpenLayers.Projection("EPSG:'
        . $srid . '"), map.getProjectionObject())); '
        . 'bound.extend(new OpenLayers.LonLat('
        . $scale_data['maxX'] . ', ' . $scale_data['maxY']
        . ').transform(new OpenLayers.Projection("EPSG:'
        . $srid . '"), map.getProjectionObject()));';
    }

    /**
     * Updates the min, max values with the given point set.
     *
     * @param string $point_set point set
     * @param array  $min_max   existing min, max values
     *
     * @return array the updated min, max values
     * @access protected
     */
    protected function setMinMax($point_set, $min_max)
    {
        // Separate each point
        $points = explode(",", $point_set);

        foreach ($points as $point) {
            // Extract coordinates of the point
            $cordinates = explode(" ", $point);

            $x = (float)$cordinates[0];
            if (!isset($min_max['maxX']) || $x > $min_max['maxX']) {
                $min_max['maxX'] = $x;
            }
            if (!isset($min_max['minX']) || $x < $min_max['minX']) {
                $min_max['minX'] = $x;
            }
            $y = (float)$cordinates[1];
            if (!isset($min_max['maxY']) || $y > $min_max['maxY']) {
                $min_max['maxY'] = $y;
            }
            if (!isset($min_max['minY']) || $y < $min_max['minY']) {
                $min_max['minY'] = $y;
            }
        }

        return $min_max;
    }

    /**
     * Generates parameters for the GIS data editor from the value of the GIS column.
     * This method performs common work.
     * More specific work is performed by each of the geom classes.
     *
     * @param string $value value of the GIS column
     *
     * @return array parameters for the GIS editor from the value of the GIS column
     * @access protected
     */
    protected function generateParams($value)
    {
        $geom_types = '(POINT|MULTIPOINT|LINESTRING|MULTILINESTRING'
            . '|POLYGON|MULTIPOLYGON|GEOMETRYCOLLECTION)';
        $srid = 0;
        $wkt = '';

        if (preg_match("/^'" . $geom_types . "\(.*\)',[0-9]*$/i", $value)) {
            $last_comma = mb_strripos($value, ",");
            $srid = trim(mb_substr($value, $last_comma + 1));
            $wkt = trim(mb_substr($value, 1, $last_comma - 2));
        } elseif (preg_match("/^" . $geom_types . "\(.*\)$/i", $value)) {
            $wkt = $value;
        }

        return array('srid' => $srid, 'wkt' => $wkt);
    }

    /**
     * Extracts points, scales and returns them as an array.
     *
     * @param string  $point_set  string of comma separated points
     * @param array   $scale_data data related to scaling
     * @param boolean $linear     if true, as a 1D array, else as a 2D array
     *
     * @return array scaled points
     * @access protected
     */
    protected function extractPoints($point_set, $scale_data, $linear = false)
    {
        $points_arr = array();

        // Separate each point
        $points = explode(",", $point_set);

        foreach ($points as $point) {
            // Extract coordinates of the point
            $cordinates = explode(" ", $point);

            if (isset($cordinates[0]) && trim($cordinates[0]) != ''
                && isset($cordinates[1])
                && trim($cordinates[1]) != ''
            ) {
                if ($scale_data != null) {
                    $x = ($cordinates[0] - $scale_data['x']) * $scale_data['scale'];
                    $y = $scale_data['height']
                        - ($cordinates[1] - $scale_data['y']) * $scale_data['scale'];
                } else {
                    $x = trim($cordinates[0]);
                    $y = trim($cordinates[1]);
                }
            } else {
                $x = '';
                $y = '';
            }

            if (!$linear) {
                $points_arr[] = array($x, $y);
            } else {
                $points_arr[] = $x;
                $points_arr[] = $y;
            }
        }

        return $points_arr;
    }

    /**
     * Generates JavaScript for adding an array of polygons to OpenLayers.
     *
     * @param array  $polygons x and y coordinates for each polygon
     * @param string $srid     spatial reference id
     *
     * @return string JavaScript for adding an array of polygons to OpenLayers
     * @access protected
     */
    protected function getPolygonArrayForOpenLayers($polygons, $srid)
    {
        $ol_array = 'new Array(';
        foreach ($polygons as $polygon) {
            $rings = explode("),(", $polygon);
            $ol_array .= $this->getPolygonForOpenLayers($rings, $srid) . ', ';
        }

        $ol_array
            = mb_substr(
                $ol_array,
                0,
                mb_strlen($ol_array) - 2
            );
        $ol_array .= ')';

        return $ol_array;
    }

    /**
     * Generates JavaScript for adding points for OpenLayers polygon.
     *
     * @param array  $polygon x and y coordinates for each line
     * @param string $srid    spatial reference id
     *
     * @return string JavaScript for adding points for OpenLayers polygon
     * @access protected
     */
    protected function getPolygonForOpenLayers($polygon, $srid)
    {
        return 'new OpenLayers.Geometry.Polygon('
        . $this->getLineArrayForOpenLayers($polygon, $srid, false)
        . ')';
    }

    /**
     * Generates JavaScript for adding an array of LineString
     * or LineRing to OpenLayers.
     *
     * @param array  $lines          x and y coordinates for each line
     * @param string $srid           spatial reference id
     * @param bool   $is_line_string whether it's an array of LineString
     *
     * @return string JavaScript for adding an array of LineString
     *                or LineRing to OpenLayers
     * @access protected
     */
    protected function getLineArrayForOpenLayers(
        $lines,
        $srid,
        $is_line_string = true
    ) {
        $ol_array = 'new Array(';
        foreach ($lines as $line) {
            $points_arr = $this->extractPoints($line, null);
            $ol_array .= $this->getLineForOpenLayers(
                $points_arr,
                $srid,
                $is_line_string
            );
            $ol_array .= ', ';
        }

        $ol_array
            = mb_substr(
                $ol_array,
                0,
                mb_strlen($ol_array) - 2
            );
        $ol_array .= ')';

        return $ol_array;
    }

    /**
     * Generates JavaScript for adding a LineString or LineRing to OpenLayers.
     *
     * @param array  $points_arr     x and y coordinates for each point
     * @param string $srid           spatial reference id
     * @param bool   $is_line_string whether it's a LineString
     *
     * @return string JavaScript for adding a LineString or LineRing to OpenLayers
     * @access protected
     */
    protected function getLineForOpenLayers(
        $points_arr,
        $srid,
        $is_line_string = true
    ) {
        return 'new OpenLayers.Geometry.'
        . ($is_line_string ? 'LineString' : 'LinearRing') . '('
        . $this->getPointsArrayForOpenLayers($points_arr, $srid)
        . ')';
    }

    /**
     * Generates JavaScript for adding an array of points to OpenLayers.
     *
     * @param array  $points_arr x and y coordinates for each point
     * @param string $srid       spatial reference id
     *
     * @return string JavaScript for adding an array of points to OpenLayers
     * @access protected
     */
    protected function getPointsArrayForOpenLayers($points_arr, $srid)
    {
        $ol_array = 'new Array(';
        foreach ($points_arr as $point) {
            $ol_array .= $this->getPointForOpenLayers($point, $srid) . ', ';
        }

        $ol_array
            = mb_substr(
                $ol_array,
                0,
                mb_strlen($ol_array) - 2
            );
        $ol_array .= ')';

        return $ol_array;
    }

    /**
     * Generates JavaScript for adding a point to OpenLayers.
     *
     * @param array  $point array containing the x and y coordinates of the point
     * @param string $srid  spatial reference id
     *
     * @return string JavaScript for adding points to OpenLayers
     * @access protected
     */
    protected function getPointForOpenLayers($point, $srid)
    {
        return '(new OpenLayers.Geometry.Point(' . $point[0] . ',' . $point[1] . '))'
        . '.transform(new OpenLayers.Projection("EPSG:'
        . $srid . '"), map.getProjectionObject())';
    }
}
