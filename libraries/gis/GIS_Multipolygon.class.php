<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles actions related to GIS MULTIPOLYGON objects
 *
 * @package PhpMyAdmin-GIS
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Handles actions related to GIS MULTIPOLYGON objects
 *
 * @package PhpMyAdmin-GIS
 */
class PMA_GIS_Multipolygon extends PMA_GIS_Geometry
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
     * @return PMA_GIS_Multipolygon the singleton
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

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = /*overload*/mb_substr(
            $spatial,
            15,
            /*overload*/mb_strlen($spatial) - 18
        );
        // Separate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner ring, use polygon itself
            if (/*overload*/mb_strpos($polygon, "),(") === false) {
                $ring = $polygon;
            } else {
                // Separate outer ring and use it to determine min-max
                $parts = explode("),(", $polygon);
                $ring = $parts[0];
            }
            $min_max = $this->setMinMax($ring, $min_max);
        }

        return $min_max;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     * @param object $image      Image object
     *
     * @return object the modified image object
     * @access public
     */
    public function prepareRowAsPng($spatial, $label, $fill_color,
        $scale_data, $image
    ) {
        // allocate colors
        $black = imagecolorallocate($image, 0, 0, 0);
        $red   = hexdec(/*overload*/mb_substr($fill_color, 1, 2));
        $green = hexdec(/*overload*/mb_substr($fill_color, 3, 2));
        $blue  = hexdec(/*overload*/mb_substr($fill_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = /*overload*/mb_substr(
            $spatial,
            15,
            /*overload*/mb_strlen($spatial) - 18
        );
        // Separate each polygon
        $polygons = explode(")),((", $multipolygon);

        $first_poly = true;
        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner polygon
            if (/*overload*/mb_strpos($polygon, "),(") === false) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Separate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $points_arr = $this->extractPoints($outer, $scale_data, true);

                foreach ($inner as $inner_poly) {
                    $points_arr = array_merge(
                        $points_arr,
                        $this->extractPoints($inner_poly, $scale_data, true)
                    );
                }
            }
            // draw polygon
            imagefilledpolygon($image, $points_arr, sizeof($points_arr) / 2, $color);
            // mark label point if applicable
            if (isset($label) && trim($label) != '' && $first_poly) {
                $label_point = array($points_arr[2], $points_arr[3]);
            }
            $first_poly = false;
        }
        // print label if applicable
        if (isset($label_point)) {
            imagestring(
                $image, 1, $points_arr[2], $points_arr[3], trim($label), $black
            );
        }
        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     * @access public
     */
    public function prepareRowAsPdf($spatial, $label, $fill_color, $scale_data, $pdf)
    {
        // allocate colors
        $red   = hexdec(/*overload*/mb_substr($fill_color, 1, 2));
        $green = hexdec(/*overload*/mb_substr($fill_color, 3, 2));
        $blue  = hexdec(/*overload*/mb_substr($fill_color, 4, 2));
        $color = array($red, $green, $blue);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = /*overload*/mb_substr(
            $spatial,
            15,
            /*overload*/mb_strlen($spatial) - 18
        );
        // Separate each polygon
        $polygons = explode(")),((", $multipolygon);

        $first_poly = true;
        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner polygon
            if (/*overload*/mb_strpos($polygon, "),(") === false) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Separate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $points_arr = $this->extractPoints($outer, $scale_data, true);

                foreach ($inner as $inner_poly) {
                    $points_arr = array_merge(
                        $points_arr,
                        $this->extractPoints($inner_poly, $scale_data, true)
                    );
                }
            }
            // draw polygon
            $pdf->Polygon($points_arr, 'F*', array(), $color, true);
            // mark label point if applicable
            if (isset($label) && trim($label) != '' && $first_poly) {
                $label_point = array($points_arr[2], $points_arr[3]);
            }
            $first_poly = false;
        }

        // print label if applicable
        if (isset($label_point)) {
            $pdf->SetXY($label_point[0], $label_point[1]);
            $pdf->SetFontSize(5);
            $pdf->Cell(0, 0, trim($label));
        }
        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     * @access public
     */
    public function prepareRowAsSvg($spatial, $label, $fill_color, $scale_data)
    {
        $polygon_options = array(
            'name'        => $label,
            'class'       => 'multipolygon vector',
            'stroke'      => 'black',
            'stroke-width'=> 0.5,
            'fill'        => $fill_color,
            'fill-rule'   => 'evenodd',
            'fill-opacity'=> 0.8,
        );

        $row = '';

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = /*overload*/mb_substr(
            $spatial,
            15,
            /*overload*/mb_strlen($spatial) - 18
        );
        // Separate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            $row .= '<path d="';

            // If the polygon doesn't have an inner polygon
            if (/*overload*/mb_strpos($polygon, "),(") === false) {
                $row .= $this->_drawPath($polygon, $scale_data);
            } else {
                // Separate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $row .= $this->_drawPath($outer, $scale_data);

                foreach ($inner as $inner_poly) {
                    $row .= $this->_drawPath($inner_poly, $scale_data);
                }
            }
            $polygon_options['id'] = $label . rand();
            $row .= '"';
            foreach ($polygon_options as $option => $val) {
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
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     * @access public
     */
    public function prepareRowAsOl($spatial, $srid, $label, $fill_color, $scale_data)
    {
        $style_options = array(
            'strokeColor' => '#000000',
            'strokeWidth' => 0.5,
            'fillColor'   => $fill_color,
            'fillOpacity' => 0.8,
            'label'       => $label,
            'fontSize'    => 10,
        );
        if ($srid == 0) {
            $srid = 4326;
        }
        $row = $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = /*overload*/mb_substr(
            $spatial,
            15,
            /*overload*/mb_strlen($spatial) - 18
        );
        // Separate each polygon
        $polygons = explode(")),((", $multipolygon);

        $row .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . 'new OpenLayers.Geometry.MultiPolygon('
            . $this->getPolygonArrayForOpenLayers($polygons, $srid)
            . '), null, ' . json_encode($style_options) . '));';
        return $row;
    }

    /**
     * Draws a ring of the polygon using SVG path element.
     *
     * @param string $polygon    The ring
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code to draw the ring
     * @access private
     */
    private function _drawPath($polygon, $scale_data)
    {
        $points_arr = $this->extractPoints($polygon, $scale_data);

        $row = ' M ' . $points_arr[0][0] . ', ' . $points_arr[0][1];
        $other_points = array_slice($points_arr, 1, count($points_arr) - 2);
        foreach ($other_points as $point) {
            $row .= ' L ' . $point[0] . ', ' . $point[1];
        }
        $row .= ' Z ';

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
        $data_row = $gis_data[$index]['MULTIPOLYGON'];

        $no_of_polygons = isset($data_row['no_of_polygons'])
            ? $data_row['no_of_polygons'] : 1;
        if ($no_of_polygons < 1) {
            $no_of_polygons = 1;
        }

        $wkt = 'MULTIPOLYGON(';
        for ($k = 0; $k < $no_of_polygons; $k++) {
            $no_of_lines = isset($data_row[$k]['no_of_lines'])
                ? $data_row[$k]['no_of_lines'] : 1;
            if ($no_of_lines < 1) {
                $no_of_lines = 1;
            }
            $wkt .= '(';
            for ($i = 0; $i < $no_of_lines; $i++) {
                $no_of_points = isset($data_row[$k][$i]['no_of_points'])
                    ? $data_row[$k][$i]['no_of_points'] : 4;
                if ($no_of_points < 4) {
                    $no_of_points = 4;
                }
                $wkt .= '(';
                for ($j = 0; $j < $no_of_points; $j++) {
                    $wkt .= ((isset($data_row[$k][$i][$j]['x'])
                        && trim($data_row[$k][$i][$j]['x']) != '')
                        ? $data_row[$k][$i][$j]['x'] : $empty)
                        . ' ' . ((isset($data_row[$k][$i][$j]['y'])
                        && trim($data_row[$k][$i][$j]['y']) != '')
                        ? $data_row[$k][$i][$j]['y'] : $empty) . ',';
                }
                $wkt = /*overload*/mb_substr(
                    $wkt,
                    0,
                    /*overload*/mb_strlen($wkt) - 1
                );
                $wkt .= '),';
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
        // Determines whether each line ring is an inner ring or an outer ring.
        // If it's an inner ring get a point on the surface which can be used to
        // correctly classify inner rings to their respective outer rings.
        include_once './libraries/gis/GIS_Polygon.class.php';
        foreach ($row_data['parts'] as $i => $ring) {
            $row_data['parts'][$i]['isOuter']
                = PMA_GIS_Polygon::isOuterRing($ring['points']);
        }

        // Find points on surface for inner rings
        foreach ($row_data['parts'] as $i => $ring) {
            if (! $ring['isOuter']) {
                $row_data['parts'][$i]['pointOnSurface']
                    = PMA_GIS_Polygon::getPointOnSurface($ring['points']);
            }
        }

        // Classify inner rings to their respective outer rings.
        foreach ($row_data['parts'] as $j => $ring1) {
            if ($ring1['isOuter']) {
                continue;
            }
            foreach ($row_data['parts'] as $k => $ring2) {
                if (!$ring2['isOuter']) {
                    continue;
                }

                // If the pointOnSurface of the inner ring
                // is also inside the outer ring
                if (PMA_GIS_Polygon::isPointInsidePolygon(
                    $ring1['pointOnSurface'], $ring2['points']
                )) {
                    if (! isset($ring2['inner'])) {
                        $row_data['parts'][$k]['inner'] = array();
                    }
                    $row_data['parts'][$k]['inner'][] = $j;
                }
            }
        }

        $wkt = 'MULTIPOLYGON(';
        // for each polygon
        foreach ($row_data['parts'] as $ring) {
            if (!$ring['isOuter']) {
                continue;
            }

            $wkt .= '('; // start of polygon

            $wkt .= '('; // start of outer ring
            foreach ($ring['points'] as $point) {
                $wkt .= $point['x'] . ' ' . $point['y'] . ',';
            }
            $wkt = /*overload*/mb_substr($wkt, 0, /*overload*/mb_strlen($wkt) - 1);
            $wkt .= ')'; // end of outer ring

            // inner rings if any
            if (isset($ring['inner'])) {
                foreach ($ring['inner'] as $j) {
                    $wkt .= ',('; // start of inner ring
                    foreach ($row_data['parts'][$j]['points'] as $innerPoint) {
                        $wkt .= $innerPoint['x'] . ' ' . $innerPoint['y'] . ',';
                    }
                    $wkt = /*overload*/mb_substr(
                        $wkt,
                        0,
                        /*overload*/mb_strlen($wkt) - 1
                    );
                    $wkt .= ')';  // end of inner ring
                }
            }

            $wkt .= '),'; // end of polygon
        }
        $wkt = /*overload*/mb_substr($wkt, 0, /*overload*/mb_strlen($wkt) - 1);

        $wkt .= ')'; // end of multipolygon
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
            $params[$index]['gis_type'] = 'MULTIPOLYGON';
            $wkt = $value;
        }

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = /*overload*/mb_substr(
            $wkt,
            15,
            /*overload*/mb_strlen($wkt) - 18
        );
        // Separate each polygon
        $polygons = explode(")),((", $multipolygon);

        $param_row =& $params[$index]['MULTIPOLYGON'];
        $param_row['no_of_polygons'] = count($polygons);

        $k = 0;
        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner polygon
            if (/*overload*/mb_strpos($polygon, "),(") === false) {
                $param_row[$k]['no_of_lines'] = 1;
                $points_arr = $this->extractPoints($polygon, null);
                $no_of_points = count($points_arr);
                $param_row[$k][0]['no_of_points'] = $no_of_points;
                for ($i = 0; $i < $no_of_points; $i++) {
                    $param_row[$k][0][$i]['x'] = $points_arr[$i][0];
                    $param_row[$k][0][$i]['y'] = $points_arr[$i][1];
                }
            } else {
                // Separate outer and inner polygons
                $parts = explode("),(", $polygon);
                $param_row[$k]['no_of_lines'] = count($parts);
                $j = 0;
                foreach ($parts as $ring) {
                    $points_arr = $this->extractPoints($ring, null);
                    $no_of_points = count($points_arr);
                    $param_row[$k][$j]['no_of_points'] = $no_of_points;
                    for ($i = 0; $i < $no_of_points; $i++) {
                        $param_row[$k][$j][$i]['x'] = $points_arr[$i][0];
                        $param_row[$k][$j][$i]['y'] = $points_arr[$i][1];
                    }
                    $j++;
                }
            }
            $k++;
        }
        return $params;
    }
}
