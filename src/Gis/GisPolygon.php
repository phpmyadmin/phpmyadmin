<?php
/**
 * Handles actions related to GIS POLYGON objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
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
     * Get coordinate extent for this wkt.
     *
     * @param string $wkt Well Known Text represenatation of the geometry
     *
     * @return Extent the min, max values for x and y coordinates
     */
    public function getExtent(string $wkt): Extent
    {
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($wkt, 9, -2);
        $wktOuterRing = explode('),(', $polygon)[0];

        return $this->getCoordinatesExtent($wktOuterRing);
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
        if ($label === '') {
            return;
        }

        // print label if applicable
        $image->string(
            1,
            (int) round($pointsArr[2]),
            (int) round($pointsArr[3]),
            $label,
            $black,
        );
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string    $spatial   GIS POLYGON object
     * @param string    $label     Label for the GIS POLYGON object
     * @param int[]     $color     Color for the GIS POLYGON object
     * @param ScaleData $scaleData Array containing data related to scaling
     */
    public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        TCPDF $pdf,
    ): void {
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        $wktRings = explode('),(', $polygon);

        $pointsArr = [];

        foreach ($wktRings as $wktRing) {
            $ring = $this->extractPoints1dLinear($wktRing, $scaleData);
            $pointsArr = array_merge($pointsArr, $ring);
        }

        // draw polygon
        $pdf->Polygon($pointsArr, 'F*', [], $color);
        if ($label === '') {
            return;
        }

        // print label if applicable
        $pdf->setXY($pointsArr[2], $pointsArr[3]);
        $pdf->setFontSize(5);
        $pdf->Cell(0, 0, $label);
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string    $spatial   GIS POLYGON object
     * @param string    $label     Label for the GIS POLYGON object
     * @param int[]     $color     Color for the GIS POLYGON object
     * @param ScaleData $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, ScaleData $scaleData): string
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
        $dataRow = $gisData[$index]['POLYGON'] ?? null;
        $noOfLines = max(1, $dataRow['data_length'] ?? 0);

        $wktRings = [];
        /** @infection-ignore-all */
        for ($i = 0; $i < $noOfLines; $i++) {
            $noOfPoints = max(4, $dataRow[$i]['data_length'] ?? 0);

            $wktPoints = [];
            for ($j = 0; $j < $noOfPoints; $j++) {
                $wktPoints[] = $this->getWktCoord($dataRow[$i][$j] ?? null, $empty);
            }

            $wktRings[] = '(' . implode(',', $wktPoints) . ')';
        }

        return 'POLYGON(' . implode(',', $wktRings) . ')';
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
        $coords = ['data_length' => count($wktRings)];

        foreach ($wktRings as $j => $wktRing) {
            $points = $this->extractPoints1d($wktRing, null);
            $noOfPoints = count($points);
            $coords[$j] = ['data_length' => $noOfPoints];
            /** @infection-ignore-all */
            for ($i = 0; $i < $noOfPoints; $i++) {
                $coords[$j][$i] = ['x' => $points[$i][0], 'y' => $points[$i][1]];
            }
        }

        return $coords;
    }

    protected function getType(): string
    {
        return 'POLYGON';
    }
}
