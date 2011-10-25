<?php
/**
 * Base class for all GIS data type classes.
 *
 * @package PhpMyAdmin-GIS
 */
abstract class PMA_GIS_Geometry
{
    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS data object
     * @param string $label      Label for the GIS data object
     * @param string $color      Color for the GIS data object
     * @param array  $scale_data Data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public abstract function prepareRowAsSvg($spatial, $label, $color, $scale_data);

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS data object
     * @param string $label      Label for the GIS data object
     * @param string $color      Color for the GIS data object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $image      Image object
     *
     * @return the modified image object
     */
    public abstract function prepareRowAsPng($spatial, $label, $color, $scale_data, $image);

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS data object
     * @param string $label      Label for the GIS data object
     * @param string $color      Color for the GIS data object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public abstract function prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf);

    /**
     * Prepares the JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS data object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS data object
     * @param string $color      Color for the GIS data object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the JavaScript related to a row in the GIS dataset
     */
    public abstract function prepareRowAsOl($spatial, $srid, $label, $color, $scale_data);

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public abstract function scaleRow($spatial);

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    Index into the parameter object
     * @param string $empty    Value for empty points
     *
     * @return WKT with the set of parameters passed by the GIS editor
     */
    public abstract function generateWkt($gis_data, $index, $empty);

    /**
     * Returns OpenLayers.Bounds object that correspond to the bounds of GIS data.
     *
     * @param string $srid       Spatial reference ID
     * @param array  $scale_data Data related to scaling
     *
     * @return OpenLayers.Bounds object that correspond to the bounds of GIS data
     */
    protected function getBoundsForOl($srid, $scale_data)
    {
        return 'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.LonLat('
            . $scale_data['minX'] . ', ' . $scale_data['minY']
            . ').transform(new OpenLayers.Projection("EPSG:'
            . $srid . '"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat('
            . $scale_data['maxX'] . ', ' . $scale_data['maxY']
            . ').transform(new OpenLayers.Projection("EPSG:'
            . $srid . '"), map.getProjectionObject()));';
    }

    /**
     * Update the min, max values with the given point set.
     *
     * @param string $point_set Point set
     * @param array  $min_max   Existing min, max values
     *
     * @return the updated min, max values
     */
    protected function setMinMax($point_set, $min_max)
    {
        // Seperate each point
        $points = explode(",", $point_set);

        foreach ($points as $point) {
            // Extract cordinates of the point
            $cordinates = explode(" ", $point);

            $x = (float) $cordinates[0];
            if (! isset($min_max['maxX']) || $x > $min_max['maxX']) {
                $min_max['maxX'] = $x;
            }
            if (! isset($min_max['minX']) || $x < $min_max['minX']) {
                $min_max['minX'] = $x;
            }
            $y = (float) $cordinates[1];
            if (! isset($min_max['maxY']) || $y > $min_max['maxY']) {
                $min_max['maxY'] = $y;
            }
            if (! isset($min_max['minY']) || $y < $min_max['minY']) {
                $min_max['minY'] = $y;
            }
        }
        return $min_max;
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     * This method performs common work.
     * More specific work is performed by each of the geom classes.
     *
     * @param $gis_string $value of the GIS column
     *
     * @return array parameters for the GIS editor from the value of the GIS column
     */
    protected function generateParams($value)
    {
        $geom_types = '(POINT|MULTIPOINT|LINESTRING|MULTILINESTRING|POLYGON|MULTIPOLYGON|GEOMETRYCOLLECTION)';
        $srid = 0;
        $wkt = '';
        if (preg_match("/^'" . $geom_types . "\(.*\)',[0-9]*$/i", $value)) {
            $last_comma = strripos($value, ",");
            $srid = trim(substr($value, $last_comma + 1));
            $wkt = trim(substr($value, 1, $last_comma - 2));
        } elseif (preg_match("/^" . $geom_types . "\(.*\)$/i", $value)) {
            $wkt = $value;
        }
        return array('srid' => $srid, 'wkt' => $wkt);
    }

    /**
     * Extracts points, scales and returns them as an array.
     *
     * @param string  $point_set  String of comma sperated points
     * @param array   $scale_data Data related to scaling
     * @param boolean $linear     If true, as a 1D array, else as a 2D array
     *
     * @return scaled points
     */
    protected function extractPoints($point_set, $scale_data, $linear = false)
    {
        $points_arr = array();

        // Seperate each point
        $points = explode(",", $point_set);

        foreach ($points as $point) {
            // Extract cordinates of the point
            $cordinates = explode(" ", $point);

            if (isset($cordinates[0]) && trim($cordinates[0]) != ''
                && isset($cordinates[1]) && trim($cordinates[1]) != ''
            ) {
                if ($scale_data != null) {
                    $x = ($cordinates[0] - $scale_data['x']) * $scale_data['scale'];
                    $y = $scale_data['height'] - ($cordinates[1] - $scale_data['y']) * $scale_data['scale'];
                } else {
                    $x = trim($cordinates[0]);
                    $y = trim($cordinates[1]);
                }
            } else {
                $x = '';
                $y = '';
            }


            if (! $linear) {
                $points_arr[] = array($x, $y);
            } else {
                $points_arr[] = $x;
                $points_arr[] = $y;
            }
        }

        return $points_arr;
    }

    /**
     * Generates JavaScriipt for adding points for OpenLayers polygon.
     *
     * @param string $polygon points of a polygon in WKT form
     * @param string $srid    spatial reference id
     *
     * @return JavaScriipt for adding points for OpenLayers polygon
     */
    protected function addPointsForOpenLayersPolygon($polygon, $srid)
    {
        $row = 'new OpenLayers.Geometry.Polygon(new Array(';
        // If the polygon doesnt have an inner polygon
        if (strpos($polygon, "),(") === false) {
            $points_arr = $this->extractPoints($polygon, null);
            $row .= 'new OpenLayers.Geometry.LinearRing(new Array(';
            foreach ($points_arr as $point) {
                $row .= '(new OpenLayers.Geometry.Point('
                    . $point[0] . ', ' . $point[1] . '))'
                    . '.transform(new OpenLayers.Projection("EPSG:'
                    . $srid . '"), map.getProjectionObject()), ';
            }
            $row = substr($row, 0, strlen($row) - 2);
            $row .= '))';
        } else {
            // Seperate outer and inner polygons
            $parts = explode("),(", $polygon);
            foreach ($parts as $ring) {
                $points_arr = $this->extractPoints($ring, null);
                $row .= 'new OpenLayers.Geometry.LinearRing(new Array(';
                foreach ($points_arr as $point) {
                    $row .= '(new OpenLayers.Geometry.Point('
                        . $point[0] . ', ' . $point[1] . '))'
                        . '.transform(new OpenLayers.Projection("EPSG:'
                        . $srid . '"), map.getProjectionObject()), ';
                }
                $row = substr($row, 0, strlen($row) - 2);
                $row .= ')), ';
            }
            $row = substr($row, 0, strlen($row) - 2);
        }
        $row .= ')), ';
        return $row;
    }
}
?>
