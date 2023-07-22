<?php
/**
 * Handles actions related to GIS MULTIPOLYGON objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\Polygon;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function array_merge;
use function array_slice;
use function count;
use function explode;
use function implode;
use function json_encode;
use function max;
use function mb_substr;
use function round;
use function sprintf;

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
     * Get coordinates extent for this wkt.
     *
     * @param string $wkt Well Known Text representation of the geometry
     *
     * @return Extent the min, max values for x and y coordinates
     */
    public function getExtent(string $wkt): Extent
    {
        $extent = Extent::empty();

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($wkt, 15, -3);
        $wktPolygons = explode(')),((', $multipolygon);

        foreach ($wktPolygons as $wktPolygon) {
            $wktOuterRing = explode('),(', $wktPolygon)[0];
            $extent = $extent->merge($this->getCoordinatesExtent($wktOuterRing));
        }

        return $extent;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string    $spatial   GIS POLYGON object
     * @param string    $label     Label for the GIS POLYGON object
     * @param int[]     $color     Color for the GIS POLYGON object
     * @param ScaleData $scaleData Array containing data related to scaling
     */
    public function prepareRowAsPng(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        ImageWrapper $image,
    ): void {
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

        if ($label === '' || ! isset($labelPoint)) {
            return;
        }

        // print label if applicable
        $image->string(
            1,
            (int) round($labelPoint[0]),
            (int) round($labelPoint[1]),
            $label,
            $black,
        );
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string    $spatial   GIS MULTIPOLYGON object
     * @param string    $label     Label for the GIS MULTIPOLYGON object
     * @param int[]     $color     Color for the GIS MULTIPOLYGON object
     * @param ScaleData $scaleData Array containing data related to scaling
     */
    public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        TCPDF $pdf,
    ): void {
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
            $pdf->Polygon($pointsArr, 'F*', [], $color);
            // mark label point if applicable
            if (isset($labelPoint)) {
                continue;
            }

            $labelPoint = [$pointsArr[2], $pointsArr[3]];
        }

        if ($label === '' || ! isset($labelPoint)) {
            return;
        }

        // print label if applicable
        $pdf->setXY($labelPoint[0], $labelPoint[1]);
        $pdf->setFontSize(5);
        $pdf->Cell(0, 0, $label);
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string    $spatial   GIS MULTIPOLYGON object
     * @param string    $label     Label for the GIS MULTIPOLYGON object
     * @param int[]     $color     Color for the GIS MULTIPOLYGON object
     * @param ScaleData $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, ScaleData $scaleData): string
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
     * @param string    $polygon   The ring
     * @param ScaleData $scaleData Array containing data related to scaling
     *
     * @return string the code to draw the ring
     */
    private function drawPath(string $polygon, ScaleData $scaleData): string
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
     * @param mixed[] $gisData GIS data
     * @param int     $index   Index into the parameter object
     * @param string  $empty   Value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gisData, int $index, string $empty = ''): string
    {
        $dataRow = $gisData[$index]['MULTIPOLYGON'] ?? null;
        $noOfPolygons = max(1, $dataRow['data_length'] ?? 0);

        $wktPolygons = [];
        /** @infection-ignore-all */
        for ($k = 0; $k < $noOfPolygons; $k++) {
            $noOfLines = max(1, $dataRow[$k]['data_length'] ?? 0);

            $wktRings = [];
            for ($i = 0; $i < $noOfLines; $i++) {
                $noOfPoints = max(4, $dataRow[$k][$i]['data_length'] ?? 0);

                $wktPoints = [];
                for ($j = 0; $j < $noOfPoints; $j++) {
                    $wktPoints[] = $this->getWktCoord($dataRow[$k][$i][$j] ?? null, $empty);
                }

                $wktRings[] = '(' . implode(',', $wktPoints) . ')';
            }

            $wktPolygons[] = '(' . implode(',', $wktRings) . ')';
        }

        return 'MULTIPOLYGON(' . implode(',', $wktPolygons) . ')';
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
        // Buffer polygons for further use
        /** @var Polygon[] $polygons */
        $polygons = [];
        foreach ($rowData['parts'] as $i => $ring) {
            $polygons[$i] = Polygon::fromXYArray($ring['points']);

            // Determines whether each line ring is an inner ring or an outer ring.
            // If it's an inner ring get a point on the surface which can be used to
            // correctly classify inner rings to their respective outer rings.
            $rowData['parts'][$i]['isOuter'] = $polygons[$i]->isOuterRing();
            if ($rowData['parts'][$i]['isOuter']) {
                continue;
            }

            // Find points on surface for inner rings
            $rowData['parts'][$i]['pointOnSurface'] = $polygons[$i]->getPointOnSurface();
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
                if (! $ring1['pointOnSurface']->isInsidePolygon($polygons[$k])) {
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
        $coords = ['data_length' => count($wktPolygons)];

        foreach ($wktPolygons as $k => $wktPolygon) {
            $wktRings = explode('),(', $wktPolygon);
            $coords[$k] = ['data_length' => count($wktRings)];
            foreach ($wktRings as $j => $wktRing) {
                $points = $this->extractPoints1d($wktRing, null);
                $noOfPoints = count($points);
                $coords[$k][$j] = ['data_length' => $noOfPoints];
                /** @infection-ignore-all */
                for ($i = 0; $i < $noOfPoints; $i++) {
                    $coords[$k][$j][$i] = ['x' => $points[$i][0], 'y' => $points[$i][1]];
                }
            }
        }

        return $coords;
    }

    protected function getType(): string
    {
        return 'MULTIPOLYGON';
    }
}
