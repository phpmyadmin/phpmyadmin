<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles actions related to GIS POLYGON objects
 *
 * @package PhpMyAdmin-GIS
 */
declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use TCPDF;

/**
 * Handles actions related to GIS POLYGON objects
 *
 * @package PhpMyAdmin-GIS
 */
class GisPolygon extends GisGeometry
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
     * @return GisPolygon the singleton
     * @access public
     */
    public static function singleton()
    {
        if (! isset(self::$_instance)) {
            self::$_instance = new GisPolygon();
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
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr(
            $spatial,
            9,
            mb_strlen($spatial) - 11
        );

        // If the polygon doesn't have an inner ring, use polygon itself
        if (mb_strpos($polygon, "),(") === false) {
            $ring = $polygon;
        } else {
            // Separate outer ring and use it to determine min-max
            $parts = explode("),(", $polygon);
            $ring = $parts[0];
        }

        return $this->setMinMax($ring, []);
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS POLYGON object
     * @param string|null $label      Label for the GIS POLYGON object
     * @param string      $fill_color Color for the GIS POLYGON object
     * @param array       $scale_data Array containing data related to scaling
     * @param resource    $image      Image object
     *
     * @return resource the modified image object
     * @access public
     */
    public function prepareRowAsPng(
        $spatial,
        ?string $label,
        $fill_color,
        array $scale_data,
        $image
    ) {
        // allocate colors
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = hexdec(mb_substr($fill_color, 1, 2));
        $green = hexdec(mb_substr($fill_color, 3, 2));
        $blue = hexdec(mb_substr($fill_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr(
            $spatial,
            9,
            mb_strlen($spatial) - 11
        );

        // If the polygon doesn't have an inner polygon
        if (mb_strpos($polygon, "),(") === false) {
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
        imagefilledpolygon($image, $points_arr, count($points_arr) / 2, $color);
        // print label if applicable
        if (isset($label) && trim($label) != '') {
            imagestring(
                $image,
                1,
                $points_arr[2],
                $points_arr[3],
                trim($label),
                $black
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS POLYGON object
     * @param string|null $label      Label for the GIS POLYGON object
     * @param string      $fill_color Color for the GIS POLYGON object
     * @param array       $scale_data Array containing data related to scaling
     * @param TCPDF       $pdf        TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     * @access public
     */
    public function prepareRowAsPdf($spatial, ?string $label, $fill_color, array $scale_data, $pdf)
    {
        // allocate colors
        $red = hexdec(mb_substr($fill_color, 1, 2));
        $green = hexdec(mb_substr($fill_color, 3, 2));
        $blue = hexdec(mb_substr($fill_color, 4, 2));
        $color = [
            $red,
            $green,
            $blue,
        ];

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr(
            $spatial,
            9,
            mb_strlen($spatial) - 11
        );

        // If the polygon doesn't have an inner polygon
        if (mb_strpos($polygon, "),(") === false) {
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
        $pdf->Polygon($points_arr, 'F*', [], $color, true);
        // print label if applicable
        if (isset($label) && trim($label) != '') {
            $pdf->SetXY($points_arr[2], $points_arr[3]);
            $pdf->SetFontSize(5);
            $pdf->Cell(0, 0, trim($label));
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS POLYGON object
     * @param string $label      Label for the GIS POLYGON object
     * @param string $fill_color Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     * @access public
     */
    public function prepareRowAsSvg($spatial, $label, $fill_color, array $scale_data)
    {
        $polygon_options = [
            'name'         => $label,
            'id'           => $label . mt_rand(),
            'class'        => 'polygon vector',
            'stroke'       => 'black',
            'stroke-width' => 0.5,
            'fill'         => $fill_color,
            'fill-rule'    => 'evenodd',
            'fill-opacity' => 0.8,
        ];

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon
            = mb_substr(
                $spatial,
                9,
                mb_strlen($spatial) - 11
            );

        $row = '<path d="';

        // If the polygon doesn't have an inner polygon
        if (mb_strpos($polygon, "),(") === false) {
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

        $row .= '"';
        foreach ($polygon_options as $option => $val) {
            $row .= ' ' . $option . '="' . trim((string) $val) . '"';
        }
        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS POLYGON object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS POLYGON object
     * @param string $fill_color Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     * @access public
     */
    public function prepareRowAsOl($spatial, $srid, $label, $fill_color, array $scale_data)
    {
        $style_options = [
            'strokeColor' => '#000000',
            'strokeWidth' => 0.5,
            'fillColor'   => $fill_color,
            'fillOpacity' => 0.8,
            'label'       => $label,
            'fontSize'    => 10,
        ];
        if ($srid == 0) {
            $srid = 4326;
        }
        $row = $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon
            =
            mb_substr(
                $spatial,
                9,
                mb_strlen($spatial) - 11
            );

        // Separate outer and inner polygons
        $parts = explode("),(", $polygon);
        $row .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . $this->getPolygonForOpenLayers($parts, $srid)
            . ', null, ' . json_encode($style_options) . '));';

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
    private function _drawPath($polygon, array $scale_data)
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
    public function generateWkt(array $gis_data, $index, $empty = '')
    {
        $no_of_lines = isset($gis_data[$index]['POLYGON']['no_of_lines'])
            ? $gis_data[$index]['POLYGON']['no_of_lines'] : 1;
        if ($no_of_lines < 1) {
            $no_of_lines = 1;
        }

        $wkt = 'POLYGON(';
        for ($i = 0; $i < $no_of_lines; $i++) {
            $no_of_points = isset($gis_data[$index]['POLYGON'][$i]['no_of_points'])
                ? $gis_data[$index]['POLYGON'][$i]['no_of_points'] : 4;
            if ($no_of_points < 4) {
                $no_of_points = 4;
            }
            $wkt .= '(';
            for ($j = 0; $j < $no_of_points; $j++) {
                $wkt .= ((isset($gis_data[$index]['POLYGON'][$i][$j]['x'])
                        && trim((string) $gis_data[$index]['POLYGON'][$i][$j]['x']) != '')
                        ? $gis_data[$index]['POLYGON'][$i][$j]['x'] : $empty)
                    . ' ' . ((isset($gis_data[$index]['POLYGON'][$i][$j]['y'])
                        && trim((string) $gis_data[$index]['POLYGON'][$i][$j]['y']) != '')
                        ? $gis_data[$index]['POLYGON'][$i][$j]['y'] : $empty) . ',';
            }
            $wkt
                =
                mb_substr(
                    $wkt,
                    0,
                    mb_strlen($wkt) - 1
                );
            $wkt .= '),';
        }
        $wkt
            =
            mb_substr(
                $wkt,
                0,
                mb_strlen($wkt) - 1
            );
        $wkt .= ')';

        return $wkt;
    }

    /**
     * Calculates the area of a closed simple polygon.
     *
     * @param array $ring array of points forming the ring
     *
     * @return float the area of a closed simple polygon
     * @access public
     * @static
     */
    public static function area(array $ring)
    {

        $no_of_points = count($ring);

        // If the last point is same as the first point ignore it
        $last = count($ring) - 1;
        if (($ring[0]['x'] == $ring[$last]['x'])
            && ($ring[0]['y'] == $ring[$last]['y'])
        ) {
            $no_of_points--;
        }

        //         _n-1
        // A = _1_ \    (X(i) * Y(i+1)) - (Y(i) * X(i+1))
        //      2  /__
        //         i=0
        $area = 0;
        for ($i = 0; $i < $no_of_points; $i++) {
            $j = ($i + 1) % $no_of_points;
            $area += $ring[$i]['x'] * $ring[$j]['y'];
            $area -= $ring[$i]['y'] * $ring[$j]['x'];
        }
        $area /= 2.0;

        return $area;
    }

    /**
     * Determines whether a set of points represents an outer ring.
     * If points are in clockwise orientation then, they form an outer ring.
     *
     * @param array $ring array of points forming the ring
     *
     * @return bool whether a set of points represents an outer ring
     * @access public
     * @static
     */
    public static function isOuterRing(array $ring)
    {
        // If area is negative then it's in clockwise orientation,
        // i.e. it's an outer ring
        return GisPolygon::area($ring) < 0;
    }

    /**
     * Determines whether a given point is inside a given polygon.
     *
     * @param array $point   x, y coordinates of the point
     * @param array $polygon array of points forming the ring
     *
     * @return bool whether a given point is inside a given polygon
     * @access public
     * @static
     */
    public static function isPointInsidePolygon(array $point, array $polygon)
    {
        // If first point is repeated at the end remove it
        $last = count($polygon) - 1;
        if (($polygon[0]['x'] == $polygon[$last]['x'])
            && ($polygon[0]['y'] == $polygon[$last]['y'])
        ) {
            $polygon = array_slice($polygon, 0, $last);
        }

        $no_of_points = count($polygon);
        $counter = 0;

        // Use ray casting algorithm
        $p1 = $polygon[0];
        for ($i = 1; $i <= $no_of_points; $i++) {
            $p2 = $polygon[$i % $no_of_points];
            if ($point['y'] <= min([$p1['y'], $p2['y']])) {
                $p1 = $p2;
                continue;
            }

            if ($point['y'] > max([$p1['y'], $p2['y']])) {
                $p1 = $p2;
                continue;
            }

            if ($point['x'] > max([$p1['x'], $p2['x']])) {
                $p1 = $p2;
                continue;
            }

            if ($p1['y'] != $p2['y']) {
                $xinters = ($point['y'] - $p1['y'])
                    * ($p2['x'] - $p1['x'])
                    / ($p2['y'] - $p1['y']) + $p1['x'];
                if ($p1['x'] == $p2['x'] || $point['x'] <= $xinters) {
                    $counter++;
                }
            }

            $p1 = $p2;
        }

        return $counter % 2 != 0;
    }

    /**
     * Returns a point that is guaranteed to be on the surface of the ring.
     * (for simple closed rings)
     *
     * @param array $ring array of points forming the ring
     *
     * @return array|bool a point on the surface of the ring
     * @access public
     * @static
     */
    public static function getPointOnSurface(array $ring)
    {
        $x0 = null;
        $x1 = null;
        $y0 = null;
        $y1 = null;
        // Find two consecutive distinct points.
        for ($i = 0, $nb = count($ring) - 1; $i < $nb; $i++) {
            if ($ring[$i]['y'] != $ring[$i + 1]['y']) {
                $x0 = $ring[$i]['x'];
                $x1 = $ring[$i + 1]['x'];
                $y0 = $ring[$i]['y'];
                $y1 = $ring[$i + 1]['y'];
                break;
            }
        }

        if (! isset($x0)) {
            return false;
        }

        // Find the mid point
        $x2 = ($x0 + $x1) / 2;
        $y2 = ($y0 + $y1) / 2;

        // Always keep $epsilon < 1 to go with the reduction logic down here
        $epsilon = 0.1;
        $denominator = sqrt(pow($y1 - $y0, 2) + pow($x0 - $x1, 2));
        $pointA = [];
        $pointB = [];

        while (true) {
            // Get the points on either sides of the line
            // with a distance of epsilon to the mid point
            $pointA['x'] = $x2 + ($epsilon * ($y1 - $y0)) / $denominator;
            $pointA['y'] = $y2 + ($pointA['x'] - $x2) * ($x0 - $x1) / ($y1 - $y0);

            $pointB['x'] = $x2 + ($epsilon * ($y1 - $y0)) / (0 - $denominator);
            $pointB['y'] = $y2 + ($pointB['x'] - $x2) * ($x0 - $x1) / ($y1 - $y0);

            // One of the points should be inside the polygon,
            // unless epsilon chosen is too large
            if (GisPolygon::isPointInsidePolygon($pointA, $ring)) {
                return $pointA;
            }

            if (GisPolygon::isPointInsidePolygon($pointB, $ring)) {
                return $pointB;
            }

            //If both are outside the polygon reduce the epsilon and
            //recalculate the points(reduce exponentially for faster convergence)
            $epsilon = pow($epsilon, 2);
            if ($epsilon == 0) {
                return false;
            }
        }
    }

    /** Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value Value of the GIS column
     * @param int    $index Index of the geometry
     *
     * @return array params for the GIS data editor from the value of the GIS column
     * @access public
     */
    public function generateParams($value, $index = -1)
    {
        $params = [];
        if ($index == -1) {
            $index = 0;
            $data = GisGeometry::generateParams($value);
            $params['srid'] = $data['srid'];
            $wkt = $data['wkt'];
        } else {
            $params[$index]['gis_type'] = 'POLYGON';
            $wkt = $value;
        }

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon
            =
            mb_substr(
                $wkt,
                9,
                mb_strlen($wkt) - 11
            );
        // Separate each linestring
        $linerings = explode("),(", $polygon);
        $params[$index]['POLYGON']['no_of_lines'] = count($linerings);

        $j = 0;
        foreach ($linerings as $linering) {
            $points_arr = $this->extractPoints($linering, null);
            $no_of_points = count($points_arr);
            $params[$index]['POLYGON'][$j]['no_of_points'] = $no_of_points;
            for ($i = 0; $i < $no_of_points; $i++) {
                $params[$index]['POLYGON'][$j][$i]['x'] = $points_arr[$i][0];
                $params[$index]['POLYGON'][$j][$i]['y'] = $points_arr[$i][1];
            }
            $j++;
        }

        return $params;
    }
}
