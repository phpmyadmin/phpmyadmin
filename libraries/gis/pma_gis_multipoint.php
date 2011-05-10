<?php
/**
 * Handles the visualization of GIS MULTIPOINT objects.
 * @package phpMyAdmin
 */
class PMA_GIS_multipoint extends PMA_GIS_geometry
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
     * @param string $spatial  GIS MULTIPOINT object
     * @param string $label  Label for the GIS MULTIPOINT object
     * @param string $color  Color for the GIS MULTIPOINT object
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRow($spatial, $label, $point_color) {

        $point_options = array('show' => true);

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = substr($spatial, 11, (strlen($spatial) - 12));

        $row = array('data' => $this->extractPoints($multipoint), 'label' => $label,
            'points' => $point_options, 'hoverable' => true, 'color' => $point_color);

        return $row;
    }
}
?>
