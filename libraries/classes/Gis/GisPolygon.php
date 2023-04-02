<?php
/**
 * Handles actions related to GIS POLYGON objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function array_merge;
use function array_slice;
use function count;
use function explode;
use function json_encode;
use function max;
use function mb_substr;
use function min;
use function round;
use function sprintf;
use function sqrt;
use function trim;

/**
 * Handles actions related to GIS POLYGON objects
 */
class GisPolygon extends GisGeometry
{
    private static self $instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return GisPolygon the singleton
     */
    public static function singleton(): GisPolygon
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisPolygon();
        }

        return self::$instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return ScaleData|null the min, max values for x and y coordinates
     */
    public function scaleRow(string $spatial): ScaleData|null
    {
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);
        $wktOuterRing = explode('),(', $polygon)[0];

        return $this->setMinMax($wktOuterRing);
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS POLYGON object
     * @param string  $label     Label for the GIS POLYGON object
     * @param int[]   $color     Color for the GIS POLYGON object
     * @param mixed[] $scaleData Array containing data related to scaling
     */
    public function prepareRowAsPng(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        ImageWrapper $image,
    ): ImageWrapper {
        // allocate colors
        $black = $image->colorAllocate(0, 0, 0);
        $fillColor = $image->colorAllocate(...$color);

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        $pointsArr = [];
        $wktRings = explode('),(', $polygon);
        foreach ($wktRings as $wktRing) {
            $ring = $this->extractPoints1dLinear($wktRing, $scaleData);
            $pointsArr = array_merge($pointsArr, $ring);
        }

        // draw polygon
        $image->filledPolygon($pointsArr, $fillColor);
        // print label if applicable
        if ($label !== '') {
            $image->string(
                1,
                (int) round($pointsArr[2]),
                (int) round($pointsArr[3]),
                $label,
                $black,
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS POLYGON object
     * @param string  $label     Label for the GIS POLYGON object
     * @param int[]   $color     Color for the GIS POLYGON object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(string $spatial, string $label, array $color, array $scaleData, TCPDF $pdf): TCPDF
    {
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        $wktRings = explode('),(', $polygon);

        $pointsArr = [];

        foreach ($wktRings as $wktRing) {
            $ring = $this->extractPoints1dLinear($wktRing, $scaleData);
            $pointsArr = array_merge($pointsArr, $ring);
        }

        // draw polygon
        $pdf->Polygon($pointsArr, 'F*', [], $color, true);
        // print label if applicable
        if ($label !== '') {
            $pdf->setXY($pointsArr[2], $pointsArr[3]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string  $spatial   GIS POLYGON object
     * @param string  $label     Label for the GIS POLYGON object
     * @param int[]   $color     Color for the GIS POLYGON object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scaleData): string
    {
        $polygonOptions = [
            'name' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'polygon vector',
            'stroke' => 'black',
            'stroke-width' => 0.5,
            'fill' => sprintf('#%02x%02x%02x', ...$color),
            'fill-rule' => 'evenodd',
            'fill-opacity' => 0.8,
        ];

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        $row = '<path d="';

        $wktRings = explode('),(', $polygon);
        foreach ($wktRings as $wktRing) {
            $row .= $this->drawPath($wktRing, $scaleData);
        }

        $row .= '"';
        foreach ($polygonOptions as $option => $val) {
            $row .= ' ' . $option . '="' . $val . '"';
        }

        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial GIS POLYGON object
     * @param int    $srid    Spatial reference ID
     * @param string $label   Label for the GIS POLYGON object
     * @param int[]  $color   Color for the GIS POLYGON object
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl(string $spatial, int $srid, string $label, array $color): string
    {
        $color[] = 0.8;
        $fillStyle = ['color' => $color];
        $strokeStyle = ['color' => [0, 0, 0], 'width' => 0.5];
        $style = 'new ol.style.Style({'
            . 'fill: new ol.style.Fill(' . json_encode($fillStyle) . '),'
            . 'stroke: new ol.style.Stroke(' . json_encode($strokeStyle) . ')';
        if ($label !== '') {
            $textStyle = ['text' => $label];
            $style .= ',text: new ol.style.Text(' . json_encode($textStyle) . ')';
        }

        $style .= '})';

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $wktCoordinates = mb_substr($spatial, 9, -2);
        $olGeometry = $this->toOpenLayersObject(
            'ol.geom.Polygon',
            $this->extractPoints2d($wktCoordinates, null),
            $srid,
        );

        return $this->addGeometryToLayer($olGeometry, $style);
    }

    /**
     * Draws a ring of the polygon using SVG path element.
     *
     * @param string  $polygon   The ring
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return string the code to draw the ring
     */
    private function drawPath(string $polygon, array $scaleData): string
    {
        $pointsArr = $this->extractPoints1d($polygon, $scaleData);

        $row = ' M ' . $pointsArr[0][0] . ', ' . $pointsArr[0][1];
        $otherPoints = array_slice($pointsArr, 1, count($pointsArr) - 2);
        foreach ($otherPoints as $point) {
            $row .= ' L ' . $point[0] . ', ' . $point[1];
        }

        $row .= ' Z ';

        return $row;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param mixed[]     $gisData GIS data
     * @param int         $index   Index into the parameter object
     * @param string|null $empty   Value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gisData, int $index, string|null $empty = ''): string
    {
        $noOfLines = $gisData[$index]['POLYGON']['no_of_lines'] ?? 1;
        if ($noOfLines < 1) {
            $noOfLines = 1;
        }

        $wkt = 'POLYGON(';
        for ($i = 0; $i < $noOfLines; $i++) {
            $noOfPoints = $gisData[$index]['POLYGON'][$i]['no_of_points'] ?? 4;
            if ($noOfPoints < 4) {
                $noOfPoints = 4;
            }

            $wkt .= '(';
            for ($j = 0; $j < $noOfPoints; $j++) {
                $wkt .= (isset($gisData[$index]['POLYGON'][$i][$j]['x'])
                        && trim((string) $gisData[$index]['POLYGON'][$i][$j]['x']) != ''
                        ? $gisData[$index]['POLYGON'][$i][$j]['x'] : $empty)
                    . ' ' . (isset($gisData[$index]['POLYGON'][$i][$j]['y'])
                        && trim((string) $gisData[$index]['POLYGON'][$i][$j]['y']) != ''
                        ? $gisData[$index]['POLYGON'][$i][$j]['y'] : $empty) . ',';
            }

            $wkt = mb_substr($wkt, 0, -1);
            $wkt .= '),';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
    }

    /**
     * Calculates the area of a closed simple polygon.
     *
     * @param mixed[] $ring array of points forming the ring
     *
     * @return float the area of a closed simple polygon
     */
    public static function area(array $ring): float
    {
        $noOfPoints = count($ring);

        // If the last point is same as the first point ignore it
        $last = count($ring) - 1;
        if (($ring[0]['x'] == $ring[$last]['x']) && ($ring[0]['y'] == $ring[$last]['y'])) {
            $noOfPoints--;
        }

        //         _n-1
        // A = _1_ \    (X(i) * Y(i+1)) - (Y(i) * X(i+1))
        //      2  /__
        //         i=0
        $area = 0;
        for ($i = 0; $i < $noOfPoints; $i++) {
            $j = ($i + 1) % $noOfPoints;
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
     * @param mixed[] $ring array of points forming the ring
     */
    public static function isOuterRing(array $ring): bool
    {
        // If area is negative then it's in clockwise orientation,
        // i.e. it's an outer ring
        return self::area($ring) < 0;
    }

    /**
     * Determines whether a given point is inside a given polygon.
     *
     * @param mixed[] $point   x, y coordinates of the point
     * @param mixed[] $polygon array of points forming the ring
     */
    public static function isPointInsidePolygon(array $point, array $polygon): bool
    {
        // If first point is repeated at the end remove it
        $last = count($polygon) - 1;
        if (($polygon[0]['x'] == $polygon[$last]['x']) && ($polygon[0]['y'] == $polygon[$last]['y'])) {
            $polygon = array_slice($polygon, 0, $last);
        }

        $noOfPoints = count($polygon);
        $counter = 0;

        // Use ray casting algorithm
        $p1 = $polygon[0];
        for ($i = 1; $i <= $noOfPoints; $i++) {
            $p2 = $polygon[$i % $noOfPoints];
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
     * @param mixed[] $ring array of points forming the ring
     *
     * @return mixed[]|false a point on the surface of the ring
     */
    public static function getPointOnSurface(array $ring): array|false
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
        $denominator = sqrt(($y1 - $y0) ** 2 + ($x0 - $x1) ** 2);
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
            if (self::isPointInsidePolygon($pointA, $ring)) {
                return $pointA;
            }

            if (self::isPointInsidePolygon($pointB, $ring)) {
                return $pointB;
            }

            //If both are outside the polygon reduce the epsilon and
            //recalculate the points(reduce exponentially for faster convergence)
            $epsilon **= 2;
            if ($epsilon == 0) {
                return false;
            }
        }
    }

    /**
     * Generate coordinate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $wkt Value of the GIS column
     *
     * @return mixed[] Coordinate params for the GIS data editor from the value of the GIS column
     */
    protected function getCoordinateParams(string $wkt): array
    {
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $wktPolygon = mb_substr($wkt, 9, -2);
        $wktRings = explode('),(', $wktPolygon);
        $coords = ['no_of_lines' => count($wktRings)];

        foreach ($wktRings as $j => $wktRing) {
            $points = $this->extractPoints1d($wktRing, null);
            $noOfPoints = count($points);
            $coords[$j] = ['no_of_points' => $noOfPoints];
            for ($i = 0; $i < $noOfPoints; $i++) {
                $coords[$j][$i] = ['x' => $points[$i][0], 'y' => $points[$i][1]];
            }
        }

        return $coords;
    }
}
