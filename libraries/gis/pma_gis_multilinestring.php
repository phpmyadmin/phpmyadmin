<?php
/**
 * Handles the visualization of GIS MULTILINESTRING objects.
 * @package phpMyAdmin
 */
class PMA_GIS_multilinestring extends PMA_GIS_geometry
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
     * @param string $spatial  GIS MULTILINESTRING object
     * @param string $label  Label for the GIS MULTILINESTRING object
     * @param string $color  Color for the GIS MULTILINESTRING object
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRow($spatial, $label, $line_color) {

        $line_options = array('lineWidth' => 2.0, 'show' => true);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $row_arr = array();
        foreach($linestirngs as $linestirng) {
            $row_arr[] = array('data' => $this->extractPoints($linestirng), 'hoverable' => true,
                'lines' => $line_options, 'color' => $line_color, 'label' => $label);
        }

        return $row_arr;
    }
}
?>
