<?php
/**
 * Handles the visualization of GIS MULTILINESTRING objects.
 *
 * @package phpMyAdmin
 */
class PMA_GIS_Multilinestring extends PMA_GIS_Geometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return the singleton
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public function scaleRow($spatial)
    {
        $min_max = array();

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        foreach ($linestirngs as $linestring) {
            $min_max = $this->setMinMax($linestring, $min_max);
        }

        return $min_max;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param string $line_color Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $image      Image object
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsPng($spatial, $label, $line_color, $scale_data, $image)
    {
        // allocate colors
        $r = hexdec(substr($line_color, 1, 2));
        $g = hexdec(substr($line_color, 3, 2));
        $b = hexdec(substr($line_color, 4, 2));
        $color = imagecolorallocate($image, $r, $g, $b);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);
            foreach ($points_arr as $point) {
                if (! isset($temp_point)) {
                    $temp_point = $point;
                } else {
                    // draw line section
                    imageline($image, $temp_point[0], $temp_point[1], $point[0], $point[1], $color);
                    $temp_point = $point;
                }
            }
            unset($temp_point);
        }
        return $image;
    }

    /**
     * Adds to the PDF object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param string $line_color Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        Pdf object
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {
        // allocate colors
        $r = hexdec(substr($line_color, 1, 2));
        $g = hexdec(substr($line_color, 3, 2));
        $b = hexdec(substr($line_color, 4, 2));
        $line = array('width' => 1.5, 'color' => array($r, $g, $b));

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);
            foreach ($points_arr as $point) {
                if (! isset($temp_point)) {
                    $temp_point = $point;
                } else {
                    // draw line section
                    $pdf->Line($temp_point[0], $temp_point[1], $point[0], $point[1], $line);
                    $temp_point = $point;
                }
            }
            unset($temp_point);
        }
        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param string $line_color Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $line_color, $scale_data)
    {
        $line_options = array(
            'name'        => $label,
            'class'       => 'linestring',
            'fill'        => 'none',
            'stroke'      => $line_color,
            'stroke-width'=> 2,
        );

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $row = '';
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);

            $row .= '<polyline points="';
            foreach ($points_arr as $point) {
                $row .= $point[0] . ',' . $point[1] . ' ';
            }
            $row .= '"';
            $line_options['id'] = $label . rand();
            foreach ($line_options as $option => $val) {
                $row .= ' ' . $option . '="' . trim($val) . '"';
            }
            $row .= '/>';
        }

        return $row;
    }
}
?>
