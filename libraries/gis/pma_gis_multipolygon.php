<?php
/**
 * Handles the visualization of GIS MULTIPOLYGON objects.
 *
 * @package PhpMyAdmin-GIS
 */
class PMA_GIS_Multipolygon extends PMA_GIS_Geometry
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

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner ring, use polygon itself
            if (strpos($polygon, "),(") === false) {
                $ring = $polygon;
            } else {
                // Seperate outer ring and use it to determin min-max
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
     * @param image  $image      Image object
     *
     * @return the modified image object
     */
    public function prepareRowAsPng($spatial, $label, $fill_color, $scale_data, $image)
    {
        // allocate colors
        $black = imagecolorallocate($image, 0, 0, 0);
        $red   = hexdec(substr($fill_color, 1, 2));
        $green = hexdec(substr($fill_color, 3, 2));
        $blue  = hexdec(substr($fill_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        $first_poly = true;
        foreach ($polygons as $polygon) {
            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Seperate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $points_arr = $this->extractPoints($outer, $scale_data, true);

                foreach ($inner as $inner_poly) {
                    $points_arr = array_merge(
                        $points_arr, $this->extractPoints($inner_poly, $scale_data, true)
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
            imagestring($image, 1, $points_arr[2], $points_arr[3], trim($label), $black);
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
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, $label, $fill_color, $scale_data, $pdf)
    {
        // allocate colors
        $red   = hexdec(substr($fill_color, 1, 2));
        $green = hexdec(substr($fill_color, 3, 2));
        $blue  = hexdec(substr($fill_color, 4, 2));
        $color = array($red, $green, $blue);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        $first_poly = true;
        foreach ($polygons as $polygon) {
            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Seperate outer and inner polygons
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
     * @return the code related to a row in the GIS dataset
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
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            $row .= '<path d="';

            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $row .= $this->_drawPath($polygon, $scale_data);
            } else {
                // Seperate outer and inner polygons
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
     * @return JavaScript related to a row in the GIS dataset
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
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        $row .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . 'new OpenLayers.Geometry.MultiPolygon(new Array(';

        foreach ($polygons as $polygon) {
            $row .= $this->addPointsForOpenLayersPolygon($polygon, $srid);
        }
        $row = substr($row, 0, strlen($row) - 2);
        $row .= ')), null, ' . json_encode($style_options) . '));';
        return $row;
    }

    /**
     * Draws a ring of the polygon using SVG path element.
     *
     * @param string $polygon    The ring
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the code to draw the ring
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
     * @return WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt($gis_data, $index, $empty = '')
    {
        $no_of_polygons = isset($gis_data[$index]['MULTIPOLYGON']['no_of_polygons'])
            ? $gis_data[$index]['MULTIPOLYGON']['no_of_polygons'] : 1;
        if ($no_of_polygons < 1) {
            $no_of_polygons = 1;
        }
        $wkt = 'MULTIPOLYGON(';
        for ($k = 0; $k < $no_of_polygons; $k++) {
            $no_of_lines = isset($gis_data[$index]['MULTIPOLYGON'][$k]['no_of_lines'])
                ? $gis_data[$index]['MULTIPOLYGON'][$k]['no_of_lines'] : 1;
            if ($no_of_lines < 1) {
                $no_of_lines = 1;
            }
            $wkt .= '(';
            for ($i = 0; $i < $no_of_lines; $i++) {
                $no_of_points = isset($gis_data[$index]['MULTIPOLYGON'][$k][$i]['no_of_points'])
                    ? $gis_data[$index]['MULTIPOLYGON'][$k][$i]['no_of_points'] : 4;
                if ($no_of_points < 4) {
                    $no_of_points = 4;
                }
                $wkt .= '(';
                for ($j = 0; $j < $no_of_points; $j++) {
                    $wkt .= ((isset($gis_data[$index]['MULTIPOLYGON'][$k][$i][$j]['x'])
                        && trim($gis_data[$index]['MULTIPOLYGON'][$k][$i][$j]['x']) != '')
                        ? $gis_data[$index]['MULTIPOLYGON'][$k][$i][$j]['x'] : $empty)
                        . ' ' . ((isset($gis_data[$index]['MULTIPOLYGON'][$k][$i][$j]['y'])
                        && trim($gis_data[$index]['MULTIPOLYGON'][$k][$i][$j]['y']) != '')
                        ? $gis_data[$index]['MULTIPOLYGON'][$k][$i][$j]['y'] : $empty) .',';
                }
                $wkt = substr($wkt, 0, strlen($wkt) - 1);
                $wkt .= '),';
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
        // Determines whether each line ring is an inner ring or an outer ring.
        // If it's an inner ring get a point on the surface which can be used to
        // correctly classify inner rings to their respective outer rings.
        include_once './libraries/gis/pma_gis_polygon.php';
        foreach ($row_data['parts'] as $i => $ring) {
            $row_data['parts'][$i]['isOuter'] = PMA_GIS_Polygon::isOuterRing($ring['points']);
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
            if (! $ring1['isOuter']) {
                foreach ($row_data['parts'] as $k => $ring2) {
                    if ($ring2['isOuter']) {
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
            }
        }

        $wkt = 'MULTIPOLYGON(';
        // for each polygon
        foreach ($row_data['parts'] as $ring) {
            if ($ring['isOuter']) {
                $wkt .= '('; // start of polygon

                $wkt .= '('; // start of outer ring
                foreach ($ring['points'] as $point) {
                    $wkt .= $point['x'] . ' ' . $point['y'] . ',';
                }
                $wkt = substr($wkt, 0, strlen($wkt) - 1);
                $wkt .= ')'; // end of outer ring

                // inner rings if any
                if (isset($ring['inner'])) {
                    foreach ($ring['inner'] as $j) {
                        $wkt .= ',('; // start of inner ring
                        foreach ($row_data['parts'][$j]['points'] as $innerPoint) {
                            $wkt .= $innerPoint['x'] . ' ' . $innerPoint['y'] . ',';
                        }
                        $wkt = substr($wkt, 0, strlen($wkt) - 1);
                        $wkt .= ')';  // end of inner ring
                    }
                }

                $wkt .= '),'; // end of polygon
            }
        }
        $wkt = substr($wkt, 0, strlen($wkt) - 1);

        $wkt .= ')'; // end of multipolygon
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
            $params[$index]['gis_type'] = 'MULTIPOLYGON';
            $wkt = $value;
        }

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($wkt, 15, (strlen($wkt) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);
        $params[$index]['MULTIPOLYGON']['no_of_polygons'] = count($polygons);

        $k = 0;
        foreach ($polygons as $polygon) {
            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $params[$index]['MULTIPOLYGON'][$k]['no_of_lines'] = 1;
                $points_arr = $this->extractPoints($polygon, null);
                $no_of_points = count($points_arr);
                $params[$index]['MULTIPOLYGON'][$k][0]['no_of_points'] = $no_of_points;
                for ($i = 0; $i < $no_of_points; $i++) {
                    $params[$index]['MULTIPOLYGON'][$k][0][$i]['x'] = $points_arr[$i][0];
                    $params[$index]['MULTIPOLYGON'][$k][0][$i]['y'] = $points_arr[$i][1];
                }
            } else {
                // Seperate outer and inner polygons
                $parts = explode("),(", $polygon);
                $params[$index]['MULTIPOLYGON'][$k]['no_of_lines'] = count($parts);
                $j = 0;
                foreach ($parts as $ring) {
                    $points_arr = $this->extractPoints($ring, null);
                    $no_of_points = count($points_arr);
                    $params[$index]['MULTIPOLYGON'][$k][$j]['no_of_points'] = $no_of_points;
                    for ($i = 0; $i < $no_of_points; $i++) {
                        $params[$index]['MULTIPOLYGON'][$k][$j][$i]['x'] = $points_arr[$i][0];
                        $params[$index]['MULTIPOLYGON'][$k][$j][$i]['y'] = $points_arr[$i][1];
                    }
                    $j++;
                }
            }
            $k++;
        }
        return $params;
    }
}
?>
