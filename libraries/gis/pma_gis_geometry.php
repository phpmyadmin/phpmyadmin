<?php
/**
 * Base class for all GIS data type classes.
 *
 * @package phpMyAdmin
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
     * @param string $line_color Color for the GIS data object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public abstract function prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf);

    /**
     * Prepares the JavaScript related to a row in the GIS dataset to visualize it with OpenLayers.
     *
     * @param string $spatial     GIS data object
     * @param int    $srid        Spatial reference ID
     * @param string $label       Label for the GIS data object
     * @param string $point_color Color for the GIS data object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return the JavaScript related to a row in the GIS dataset
     */
    public abstract function prepareRowAsOl($spatial, $srid, $label, $point_color, $scale_data);

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public abstract function scaleRow($spatial);

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
            . $scale_data['minX'] . ', ' . $scale_data['minY'] . ').transform(new OpenLayers.Projection("EPSG:'
            . $srid . '"), map.getProjectionObject())); bound.extend(new OpenLayers.LonLat('
            . $scale_data['maxX'] . ', ' . $scale_data['maxY'] . ').transform(new OpenLayers.Projection("EPSG:'
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

            if ($scale_data != null) {
                $x = ($cordinates[0] - $scale_data['x']) * $scale_data['scale'];
                $y = $scale_data['height'] - ($cordinates[1] - $scale_data['y']) * $scale_data['scale'];
            } else {
                $x = $cordinates[0];
                $y = $cordinates[1];
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
}
?>
