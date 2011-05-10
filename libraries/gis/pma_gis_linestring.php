<?php
/**
 * Handles the visualization of GIS LINESTRING objects.
 * @package phpMyAdmin
 */
class PMA_GIS_linestring extends PMA_GIS_geometry
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
     * @param string $spatial  GIS LINESTRING object
     * @param string $label  Label for the GIS LINESTRING object
     * @param string $color  Color for the GIS LINESTRING object
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRow($spatial, $label, $line_color) {

        $line_options = array('lineWidth' => 2.0, 'show' => true);

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = substr($spatial, 11, (strlen($spatial) - 12));

        $row = array('data' => $this->extractPoints($linesrting), 'label' => $label,
            'lines' => $line_options, 'color' => $line_color, 'hoverable' => true);

        return $row;
    }
}
?>
