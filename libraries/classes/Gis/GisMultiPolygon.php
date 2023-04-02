<?php
/**
 * Handles actions related to GIS MULTIPOLYGON objects
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
use function mb_substr;
use function round;
use function sprintf;
use function trim;

/**
 * Handles actions related to GIS MULTIPOLYGON objects
 */
class GisMultiPolygon extends GisGeometry
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
     * @return GisMultiPolygon the singleton
     */
    public static function singleton(): GisMultiPolygon
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisMultiPolygon();
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
        $minMax = null;

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        $wktPolygons = explode(')),((', $multipolygon);

        foreach ($wktPolygons as $wktPolygon) {
            $wktOuterRing = explode('),(', $wktPolygon)[0];
            $minMax = $this->setMinMax($wktOuterRing, $minMax);
        }

        return $minMax;
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

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        foreach ($polygons as $polygon) {
            $wktRings = explode('),(', $polygon);

            $pointsArr = [];

            foreach ($wktRings as $wktRing) {
                $ring = $this->extractPoints1dLinear($wktRing, $scaleData);
                $pointsArr = array_merge($pointsArr, $ring);
            }

            // draw polygon
            $image->filledPolygon($pointsArr, $fillColor);
            // mark label point if applicable
            if (isset($labelPoint)) {
                continue;
            }

            $labelPoint = [$pointsArr[2], $pointsArr[3]];
        }

        // print label if applicable
        if ($label !== '' && isset($labelPoint)) {
            $image->string(
                1,
                (int) round($labelPoint[0]),
                (int) round($labelPoint[1]),
                $label,
                $black,
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS MULTIPOLYGON object
     * @param string  $label     Label for the GIS MULTIPOLYGON object
     * @param int[]   $color     Color for the GIS MULTIPOLYGON object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(string $spatial, string $label, array $color, array $scaleData, TCPDF $pdf): TCPDF
    {
        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $wktPolygons = explode(')),((', $multipolygon);

        foreach ($wktPolygons as $wktPolygon) {
            $wktRings = explode('),(', $wktPolygon);
            $pointsArr = [];

            foreach ($wktRings as $wktRing) {
                $ring = $this->extractPoints1dLinear($wktRing, $scaleData);
                $pointsArr = array_merge($pointsArr, $ring);
            }

            // draw polygon
            $pdf->Polygon($pointsArr, 'F*', [], $color, true);
            // mark label point if applicable
            if (isset($labelPoint)) {
                continue;
            }

            $labelPoint = [$pointsArr[2], $pointsArr[3]];
        }

        // print label if applicable
        if ($label !== '' && isset($labelPoint)) {
            $pdf->setXY($labelPoint[0], $labelPoint[1]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string  $spatial   GIS MULTIPOLYGON object
     * @param string  $label     Label for the GIS MULTIPOLYGON object
     * @param int[]   $color     Color for the GIS MULTIPOLYGON object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scaleData): string
    {
        $polygonOptions = [
            'name' => $label,
            'class' => 'multipolygon vector',
            'stroke' => 'black',
            'stroke-width' => 0.5,
            'fill' => sprintf('#%02x%02x%02x', ...$color),
            'fill-rule' => 'evenodd',
            'fill-opacity' => 0.8,
        ];

        $row = '';

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $wktPolygons = explode(')),((', $multipolygon);

        foreach ($wktPolygons as $wktPolygon) {
            $row .= '<path d="';

            $wktRings = explode('),(', $wktPolygon);
            foreach ($wktRings as $wktRing) {
                $row .= $this->drawPath($wktRing, $scaleData);
            }

            $polygonOptions['id'] = $label . $this->getRandomId();
            $row .= '"';
            foreach ($polygonOptions as $option => $val) {
                $row .= ' ' . $option . '="' . $val . '"';
            }

            $row .= '/>';
        }

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial GIS MULTIPOLYGON object
     * @param int    $srid    Spatial reference ID
     * @param string $label   Label for the GIS MULTIPOLYGON object
     * @param int[]  $color   Color for the GIS MULTIPOLYGON object
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

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $wktCoordinates = mb_substr($spatial, 15, -3);
        $olGeometry = $this->toOpenLayersObject(
            'ol.geom.MultiPolygon',
            $this->extractPoints3d($wktCoordinates, null),
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
        $dataRow = $gisData[$index]['MULTIPOLYGON'];

        $noOfPolygons = $dataRow['no_of_polygons'] ?? 1;
        if ($noOfPolygons < 1) {
            $noOfPolygons = 1;
        }

        $wkt = 'MULTIPOLYGON(';
        for ($k = 0; $k < $noOfPolygons; $k++) {
            $noOfLines = $dataRow[$k]['no_of_lines'] ?? 1;
            if ($noOfLines < 1) {
                $noOfLines = 1;
            }

            $wkt .= '(';
            for ($i = 0; $i < $noOfLines; $i++) {
                $noOfPoints = $dataRow[$k][$i]['no_of_points'] ?? 4;
                if ($noOfPoints < 4) {
                    $noOfPoints = 4;
                }

                $wkt .= '(';
                for ($j = 0; $j < $noOfPoints; $j++) {
                    $wkt .= (isset($dataRow[$k][$i][$j]['x'])
                            && trim((string) $dataRow[$k][$i][$j]['x']) != ''
                            ? $dataRow[$k][$i][$j]['x'] : $empty)
                        . ' ' . (isset($dataRow[$k][$i][$j]['y'])
                            && trim((string) $dataRow[$k][$i][$j]['y']) != ''
                            ? $dataRow[$k][$i][$j]['y'] : $empty) . ',';
                }

                $wkt = mb_substr($wkt, 0, -1);
                $wkt .= '),';
            }

            $wkt = mb_substr($wkt, 0, -1);
            $wkt .= '),';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param mixed[] $rowData GIS data
     *
     * @return string the WKT for the data from ESRI shape files
     */
    public function getShape(array $rowData): string
    {
        // Determines whether each line ring is an inner ring or an outer ring.
        // If it's an inner ring get a point on the surface which can be used to
        // correctly classify inner rings to their respective outer rings.
        foreach ($rowData['parts'] as $i => $ring) {
            $rowData['parts'][$i]['isOuter'] = GisPolygon::isOuterRing($ring['points']);
        }

        // Find points on surface for inner rings
        foreach ($rowData['parts'] as $i => $ring) {
            if ($ring['isOuter']) {
                continue;
            }

            $rowData['parts'][$i]['pointOnSurface'] = GisPolygon::getPointOnSurface($ring['points']);
        }

        // Classify inner rings to their respective outer rings.
        foreach ($rowData['parts'] as $j => $ring1) {
            if ($ring1['isOuter']) {
                continue;
            }

            foreach ($rowData['parts'] as $k => $ring2) {
                if (! $ring2['isOuter']) {
                    continue;
                }

                // If the pointOnSurface of the inner ring
                // is also inside the outer ring
                if (! GisPolygon::isPointInsidePolygon($ring1['pointOnSurface'], $ring2['points'])) {
                    continue;
                }

                if (! isset($ring2['inner'])) {
                    $rowData['parts'][$k]['inner'] = [];
                }

                $rowData['parts'][$k]['inner'][] = $j;
            }
        }

        $wkt = 'MULTIPOLYGON(';
        // for each polygon
        foreach ($rowData['parts'] as $ring) {
            if (! $ring['isOuter']) {
                continue;
            }

            $wkt .= '('; // start of polygon

            $wkt .= '('; // start of outer ring
            foreach ($ring['points'] as $point) {
                $wkt .= $point['x'] . ' ' . $point['y'] . ',';
            }

            $wkt = mb_substr($wkt, 0, -1);
            $wkt .= ')'; // end of outer ring

            // inner rings if any
            if (isset($ring['inner'])) {
                foreach ($ring['inner'] as $j) {
                    $wkt .= ',('; // start of inner ring
                    foreach ($rowData['parts'][$j]['points'] as $innerPoint) {
                        $wkt .= $innerPoint['x'] . ' ' . $innerPoint['y'] . ',';
                    }

                    $wkt = mb_substr($wkt, 0, -1);
                    $wkt .= ')'; // end of inner ring
                }
            }

            $wkt .= '),'; // end of polygon
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
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
        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $wktMultiPolygon = mb_substr($wkt, 15, -3);
        $wktPolygons = explode(')),((', $wktMultiPolygon);
        $coords = ['no_of_polygons' => count($wktPolygons)];

        foreach ($wktPolygons as $k => $wktPolygon) {
            $wktRings = explode('),(', $wktPolygon);
            $coords[$k] = ['no_of_lines' => count($wktRings)];
            foreach ($wktRings as $j => $wktRing) {
                $points = $this->extractPoints1d($wktRing, null);
                $noOfPoints = count($points);
                $coords[$k][$j] = ['no_of_points' => $noOfPoints];
                for ($i = 0; $i < $noOfPoints; $i++) {
                    $coords[$k][$j][$i] = ['x' => $points[$i][0], 'y' => $points[$i][1]];
                }
            }
        }

        return $coords;
    }
}
