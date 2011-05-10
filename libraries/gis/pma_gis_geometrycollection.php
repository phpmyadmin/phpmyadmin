<?php
/**
 * Handles the visualization of GIS GEOMETRYCOLLECTION objects.
 * @package phpMyAdmin
 */
class PMA_GIS_geometrycollection extends PMA_GIS_geometry
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
     * @param string $spatial  GIS GEOMETRYCOLLECTION object
     * @param string $label  Label for the GIS GEOMETRYCOLLECTION object
     * @param string $color  Color for the GIS GEOMETRYCOLLECTION object
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRow($spatial, $label, $line_color) {

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($spatial, 19, (strlen($spatial) - 20));

        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            switch($type) {
                case 'MULTIPOLYGON' :
                    $gis_obj = PMA_GIS_multipolygon::singleton();
                    break;
                case 'POLYGON' :
                    $gis_obj = PMA_GIS_polygon::singleton();
                    break;
                case 'MULTIPOINT' :
                    $gis_obj = PMA_GIS_multipoint::singleton();
                    break;
                case 'POINT' :
                    $gis_obj = PMA_GIS_point::singleton();
                    break;
                case 'MULTILINESTRING' :
                    $gis_obj = PMA_GIS_multilinestring::singleton();
                    break;
                case 'LINESTRING' :
                    $gis_obj = PMA_GIS_linestring::singleton();
                    break;
                default :
                    die(__('Unknown GIS data type'));
            }

            $temp_results = $gis_obj->prepareRow($sub_part, $label, $line_color);
            if (isset($temp_results[0]) && is_array($temp_results[0])) {
                $results_arr = array_merge($results_arr, $temp_results);
            } else {
                $results_arr[] = $temp_results;
            }
        }
        return $results_arr;
    }

    // Split the GEOMETRYCOLLECTION object and get its constituents.
    private function explodeGeomCol($goem_col) {
        $sub_parts = array();
        $br_count = 0;
        $start = 0;
        $count = 0;
        foreach (str_split($goem_col) as $char) {
            if ($char == '(') {
                $br_count++;
            } elseif ($char == ')') {
                $br_count--;
                if ($br_count == 0) {
                    $sub_parts[] = substr($goem_col, $start, ($count + 1 - $start));
                    $start = $count + 2;
                }
            }
            $count++;
        }
        return $sub_parts;
    }
}
?>
