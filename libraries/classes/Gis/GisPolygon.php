<?php
/**
 * Handles actions related to GIS POLYGON objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Gis\Ds\ScaleData;
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
