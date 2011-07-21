<?php
/**
 * Handles the visualization of GIS MULTILINESTRING objects.
 *
 * @package phpMyAdmin-GIS
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
            $class = __CLASS__;
            self::$_instance = new $class;
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
     * @return the modified image object
     */
    public function prepareRowAsPng($spatial, $label, $line_color, $scale_data, $image)
    {
        // allocate colors
        $red   = hexdec(substr($line_color, 1, 2));
        $green = hexdec(substr($line_color, 3, 2));
        $blue  = hexdec(substr($line_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

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
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param string $line_color Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {
        // allocate colors
        $red   = hexdec(substr($line_color, 1, 2));
        $green = hexdec(substr($line_color, 3, 2));
        $blue  = hexdec(substr($line_color, 4, 2));
        $line  = array('width' => 1.5, 'color' => array($red, $green, $blue));

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
            'class'       => 'linestring vector',
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

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param string $line_color Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, $srid, $label, $line_color, $scale_data)
    {
        $style_options = array(
            'strokeColor' => $line_color,
            'strokeWidth' => 2,
            'label'       => $label,
            'fontSize'    => 10,
        );
        if ($srid == 0) {
            $srid = 4326;
        }
        $row = $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $row .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . 'new OpenLayers.Geometry.MultiLineString(new Array(';
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, null);
            $row .= 'new OpenLayers.Geometry.LineString(new Array(';
            foreach ($points_arr as $point) {
                $row .= '(new OpenLayers.Geometry.Point(' . $point[0] . ', '
                    . $point[1] . ')).transform(new OpenLayers.Projection("EPSG:'
                    . $srid . '"), map.getProjectionObject()), ';
            }
            $row = substr($row, 0, strlen($row) - 2);
            $row .= ')), ';
        }
        $row = substr($row, 0, strlen($row) - 2);
        $row .= ')), null, ' . json_encode($style_options) . '));';
        return $row;
    }
}
?>
