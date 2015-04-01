<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles actions related to GIS MULTILINESTRING objects
 *
 * @package PhpMyAdmin-GIS
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Handles actions related to GIS MULTILINESTRING objects
 *
 * @package PhpMyAdmin-GIS
 */
class PMA_GIS_Multilinestring extends PMA_GIS_Geometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     *
     * @access private
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return PMA_GIS_Multilinestring the singleton
     * @access public
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
     * @return array an array containing the min, max values for x and y coordinates
     * @access public
     */
    public function scaleRow($spatial)
    {
        $min_max = array();

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = /*overload*/mb_substr(
            $spatial,
            17,
            /*overload*/mb_strlen($spatial) - 19
        );
        // Separate each linestring
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
     * @param object $image      Image object
     *
     * @return object the modified image object
     * @access public
     */
    public function prepareRowAsPng($spatial, $label, $line_color,
        $scale_data, $image
    ) {
        // allocate colors
        $black = imagecolorallocate($image, 0, 0, 0);
        $red   = hexdec(/*overload*/mb_substr($line_color, 1, 2));
        $green = hexdec(/*overload*/mb_substr($line_color, 3, 2));
        $blue  = hexdec(/*overload*/mb_substr($line_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = /*overload*/mb_substr(
            $spatial,
            17,
            /*overload*/mb_strlen($spatial) - 19
        );
        // Separate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $first_line = true;
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);
            foreach ($points_arr as $point) {
                if (! isset($temp_point)) {
                    $temp_point = $point;
                } else {
                    // draw line section
                    imageline(
                        $image, $temp_point[0], $temp_point[1],
                        $point[0], $point[1], $color
                    );
                    $temp_point = $point;
                }
            }
            unset($temp_point);
            // print label if applicable
            if (isset($label) && trim($label) != '' && $first_line) {
                imagestring(
                    $image, 1, $points_arr[1][0],
                    $points_arr[1][1], trim($label), $black
                );
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
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     * @access public
     */
    public function prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {
        // allocate colors
        $red   = hexdec(/*overload*/mb_substr($line_color, 1, 2));
        $green = hexdec(/*overload*/mb_substr($line_color, 3, 2));
        $blue  = hexdec(/*overload*/mb_substr($line_color, 4, 2));
        $line  = array('width' => 1.5, 'color' => array($red, $green, $blue));

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = /*overload*/mb_substr(
            $spatial,
            17, /*overload*/mb_strlen($spatial) - 19
        );
        // Separate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $first_line = true;
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);
            foreach ($points_arr as $point) {
                if (! isset($temp_point)) {
                    $temp_point = $point;
                } else {
                    // draw line section
                    $pdf->Line(
                        $temp_point[0], $temp_point[1], $point[0], $point[1], $line
                    );
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
     * @return string the code related to a row in the GIS dataset
     * @access public
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
        $multilinestirng = /*overload*/mb_substr(
            $spatial,
            17,
            /*overload*/mb_strlen($spatial) - 19
        );
        // Separate each linestring
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
     * @return string JavaScript related to a row in the GIS dataset
     * @access public
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
        $multilinestirng = /*overload*/mb_substr(
            $spatial,
            17,
            /*overload*/mb_strlen($spatial) - 19
        );
        // Separate each linestring
        $linestirngs = explode("),(", $multilinestirng);

        $row .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . 'new OpenLayers.Geometry.MultiLineString('
            . $this->getLineArrayForOpenLayers($linestirngs, $srid)
            . '), null, ' . json_encode($style_options) . '));';
        return $row;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    Index into the parameter object
     * @param string $empty    Value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     * @access public
     */
    public function generateWkt($gis_data, $index, $empty = '')
    {
        $data_row = $gis_data[$index]['MULTILINESTRING'];

        $no_of_lines = isset($data_row['no_of_lines'])
            ? $data_row['no_of_lines'] : 1;
        if ($no_of_lines < 1) {
            $no_of_lines = 1;
        }

        $wkt = 'MULTILINESTRING(';
        for ($i = 0; $i < $no_of_lines; $i++) {
            $no_of_points = isset($data_row[$i]['no_of_points'])
                ? $data_row[$i]['no_of_points'] : 2;
            if ($no_of_points < 2) {
                $no_of_points = 2;
            }
            $wkt .= '(';
            for ($j = 0; $j < $no_of_points; $j++) {
                $wkt .= ((isset($data_row[$i][$j]['x'])
                    && trim($data_row[$i][$j]['x']) != '')
                    ? $data_row[$i][$j]['x'] : $empty)
                    . ' ' . ((isset($data_row[$i][$j]['y'])
                    && trim($data_row[$i][$j]['y']) != '')
                    ? $data_row[$i][$j]['y'] : $empty) . ',';
            }
            $wkt = /*overload*/mb_substr($wkt, 0, /*overload*/mb_strlen($wkt) - 1);
            $wkt .= '),';
        }
        $wkt = /*overload*/mb_substr($wkt, 0, /*overload*/mb_strlen($wkt) - 1);
        $wkt .= ')';
        return $wkt;
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param array $row_data GIS data
     *
     * @return string the WKT for the data from ESRI shape files
     * @access public
     */
    public function getShape($row_data)
    {
        $wkt = 'MULTILINESTRING(';
        for ($i = 0; $i < $row_data['numparts']; $i++) {
            $wkt .= '(';
            foreach ($row_data['parts'][$i]['points'] as $point) {
                $wkt .= $point['x'] . ' ' . $point['y'] . ',';
            }
            $wkt = /*overload*/mb_substr($wkt, 0, /*overload*/mb_strlen($wkt) - 1);
            $wkt .= '),';
        }
        $wkt = /*overload*/mb_substr($wkt, 0, /*overload*/mb_strlen($wkt) - 1);
        $wkt .= ')';
        return $wkt;
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value Value of the GIS column
     * @param int    $index Index of the geometry
     *
     * @return array params for the GIS data editor from the value of the GIS column
     * @access public
     */
    public function generateParams($value, $index = -1)
    {
        $params = array();
        if ($index == -1) {
            $index = 0;
            $data = PMA_GIS_Geometry::generateParams($value);
            $params['srid'] = $data['srid'];
            $wkt = $data['wkt'];
        } else {
            $params[$index]['gis_type'] = 'MULTILINESTRING';
            $wkt = $value;
        }

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = /*overload*/mb_substr(
            $wkt,
            17,
            /*overload*/mb_strlen($wkt) - 19
        );
        // Separate each linestring
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
