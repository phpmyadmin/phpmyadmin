<?php
/**
 * Handles the visualization of GIS POINT objects.
 * @package phpMyAdmin
 */
class PMA_GIS_point extends PMA_GIS_geometry
{
    // Hold the singleton instance of the class
    private static $instance;

    // A private constructor; prevents direct creation of object
    private function __construct() {
    }

    /**
     * Returns the singleton.
     *
     * @return the singleton
     */
    public static function singleton() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset.
     *
     * @param string $spatial  GIS POINT object
     * @param string $label  Label for the GIS POINT object
     * @param string $color  Color for the GIS POINT object
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRow($spatial, $label, $point_color) {

        $point_options = array('show' => true);

        // Trim to remove leading 'POINT(' and trailing ')'
        $multipoint = substr($spatial, 6, (strlen($spatial) - 7));

        $row = array('data' => $this->extractPoints($multipoint), 'hoverable' => true,
            'points' => $point_options, 'label' => $label, 'color' => $point_color);

        return $row;
    }
}
?>
