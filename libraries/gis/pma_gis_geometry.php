<?php
/**
 * Base class for all GIS data type classes.
 * @package phpMyAdmin
 */
abstract class PMA_GIS_geometry
{
    /**
     * Prepares and returns the code related to a row in the GIS dataset.
     *
     * @param string $spatial  GIS data object
     * @param string $label  Label for the GIS data object
     * @param string $color  Color for the GIS data object     *
     * @return the code related to a row in the GIS dataset
     */
    public abstract function prepareRow($spatial, $label, $color);

    /**
     * Extracts points and returns them as an array.
     *
     * @param string $point_set  string of comma sperated points
     * @return extracted points
     */
    protected function extractPoints($point_set) {

        $cordinates_arr = array();
        $points_arr = array();

        // Seperate each point
        $points = explode(",", $point_set);

        foreach($points as $point) {
            // Extract cordinates of the point
            $cordinates = explode(" ", $point);
            $cordinate_arr[] = $cordinates[0];
            $cordinate_arr[] = $cordinates[1];

            $points_arr[] = $cordinate_arr;
            unset($cordinate_arr);
        }

        return $points_arr;
    }
}
?>
