<?php
/**
 * Handles the visualization of GIS MULTILINESTRING objects.
 *
 * @package PhpMyAdmin-GIS
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
        $black = imagecolorallocate($image, 0, 0, 0);
        $red   = hexdec(substr($line_color, 1, 2));
        $green = hexdec(substr($line_color, 3, 2));
        $blue  = hexdec(substr($line_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($spatial, 17, (strlen($spatial) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $first_line = true;
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
            // print label if applicable
            if (isset($label) && trim($label) != '' && $first_line) {
                imagestring($image, 1, $points_arr[1][0], $points_arr[1][1], trim($label), $black);
            }
            $first_line = false;
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

        $first_line = true;
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
            // print label
            if (isset($label) && trim($label) != '' && $first_line) {
                $pdf->SetXY($points_arr[1][0], $points_arr[1][1]);
                $pdf->SetFontSize(5);
                $pdf->Cell(0, 0, trim($label));
            }
            $first_line = false;
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

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    Index into the parameter object
     * @param string $empty    Value for empty points
     *
     * @return WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt($gis_data, $index, $empty = '')
    {
        $no_of_lines = isset($gis_data[$index]['MULTILINESTRING']['no_of_lines'])
            ? $gis_data[$index]['MULTILINESTRING']['no_of_lines'] : 1;
        if ($no_of_lines < 1) {
            $no_of_lines = 1;
        }
        $wkt = 'MULTILINESTRING(';
        for ($i = 0; $i < $no_of_lines; $i++) {
            $no_of_points = isset($gis_data[$index]['MULTILINESTRING'][$i]['no_of_points'])
                ? $gis_data[$index]['MULTILINESTRING'][$i]['no_of_points'] : 2;
            if ($no_of_points < 2) {
                $no_of_points = 2;
            }
            $wkt .= '(';
            for ($j = 0; $j < $no_of_points; $j++) {
                $wkt .= ((isset($gis_data[$index]['MULTILINESTRING'][$i][$j]['x'])
                    && trim($gis_data[$index]['MULTILINESTRING'][$i][$j]['x']) != '')
                    ? $gis_data[$index]['MULTILINESTRING'][$i][$j]['x'] : $empty)
                    . ' ' . ((isset($gis_data[$index]['MULTILINESTRING'][$i][$j]['y'])
                    && trim($gis_data[$index]['MULTILINESTRING'][$i][$j]['y']) != '')
                    ? $gis_data[$index]['MULTILINESTRING'][$i][$j]['y'] : $empty) . ',';
            }
            $wkt = substr($wkt, 0, strlen($wkt) - 1);
            $wkt .= '),';
        }
        $wkt = substr($wkt, 0, strlen($wkt) - 1);
        $wkt .= ')';
        return $wkt;
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param array $row_data GIS data
     *
     * @return the WKT for the data from ESRI shape files
     */
    public function getShape($row_data)
    {
        $wkt = 'MULTILINESTRING(';
        for ($i = 0; $i < $row_data['numparts']; $i++) {
            $wkt .= '(';
            foreach ($row_data['parts'][$i]['points'] as $point) {
                $wkt .= $point['x'] . ' ' . $point['y'] . ',';
            }
            $wkt = substr($wkt, 0, strlen($wkt) - 1);
            $wkt .= '),';
        }
        $wkt = substr($wkt, 0, strlen($wkt) - 1);
        $wkt .= ')';
        return $wkt;
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     * @param index  $index of the geometry
     *
     * @return  parameters for the GIS data editor from the value of the GIS column
     */
    public function generateParams($value, $index = -1)
    {
        if ($index == -1) {
            $index = 0;
            $params = array();
            $data = PMA_GIS_Geometry::generateParams($value);
            $params['srid'] = $data['srid'];
            $wkt = $data['wkt'];
        } else {
            $params[$index]['gis_type'] = 'MULTILINESTRING';
            $wkt = $value;
        }

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = substr($wkt, 17, (strlen($wkt) - 19));
        // Seperate each linestring
        $linestirngs = explode("),(", $multilinestirng);
        $params[$index]['MULTILINESTRING']['no_of_lines'] = count($linestirngs);

        $j = 0;
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, null);
            $no_of_points = count($points_arr);
            $params[$index]['MULTILINESTRING'][$j]['no_of_points'] = $no_of_points;
            for ($i = 0; $i < $no_of_points; $i++) {
                $params[$index]['MULTILINESTRING'][$j][$i]['x'] = $points_arr[$i][0];
                $params[$index]['MULTILINESTRING'][$j][$i]['y'] = $points_arr[$i][1];
            }
            $j++;
        }
        return $params;
    }
}
?>
