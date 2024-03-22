<?php
/**
 * Handles actions related to GIS LINESTRING objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function implode;
use function json_encode;
use function max;
use function mb_substr;
use function round;
use function sprintf;

/**
 * Handles actions related to GIS LINESTRING objects
 */
class GisLineString extends GisGeometry
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
     * @return GisLineString the singleton
     */
    public static function singleton(): GisLineString
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisLineString();
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
        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = mb_substr($wkt, 11, -1);

        return $this->getCoordinatesExtent($linestring);
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
        $lineColor = $image->colorAllocate(...$color);

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $lineString = mb_substr($spatial, 11, -1);
        $pointsArr = $this->extractPoints1d($lineString, $scaleData);

        foreach ($pointsArr as $point) {
            if (isset($tempPoint)) {
                // draw line section
                $image->line(
                    (int) round($tempPoint[0]),
                    (int) round($tempPoint[1]),
                    (int) round($point[0]),
                    (int) round($point[1]),
                    $lineColor,
                );
            }

            $tempPoint = $point;
        }

        if ($label === '') {
            return;
        }

        // print label if applicable
        $image->string(
            1,
            (int) round($pointsArr[1][0]),
            (int) round($pointsArr[1][1]),
            $label,
            $black,
        );
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string    $spatial   GIS LINESTRING object
     * @param string    $label     Label for the GIS LINESTRING object
     * @param int[]     $color     Color for the GIS LINESTRING object
     * @param ScaleData $scaleData Array containing data related to scaling
     */
    public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        TCPDF $pdf,
    ): void {
        $line = ['width' => 1.5, 'color' => $color];

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = mb_substr($spatial, 11, -1);
        $pointsArr = $this->extractPoints1d($linestring, $scaleData);

        foreach ($pointsArr as $point) {
            if (isset($tempPoint)) {
                // draw line section
                $pdf->Line($tempPoint[0], $tempPoint[1], $point[0], $point[1], $line);
            }

            $tempPoint = $point;
        }

        if ($label === '') {
            return;
        }

        // print label
        $pdf->setXY($pointsArr[1][0], $pointsArr[1][1]);
        $pdf->setFontSize(5);
        $pdf->Cell(0, 0, $label);
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string    $spatial   GIS LINESTRING object
     * @param string    $label     Label for the GIS LINESTRING object
     * @param int[]     $color     Color for the GIS LINESTRING object
     * @param ScaleData $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, ScaleData $scaleData): string
    {
        $lineOptions = [
            'name' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'linestring vector',
            'fill' => 'none',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = mb_substr($spatial, 11, -1);
        $pointsArr = $this->extractPoints1d($linestring, $scaleData);

        $row = '<polyline points="';
        foreach ($pointsArr as $point) {
            $row .= $point[0] . ',' . $point[1] . ' ';
        }

        $row .= '"';
        foreach ($lineOptions as $option => $val) {
            $row .= ' ' . $option . '="' . $val . '"';
        }

        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial GIS LINESTRING object
     * @param int    $srid    Spatial reference ID
     * @param string $label   Label for the GIS LINESTRING object
     * @param int[]  $color   Color for the GIS LINESTRING object
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl(string $spatial, int $srid, string $label, array $color): string
    {
        $strokeStyle = ['color' => $color, 'width' => 2];

        $style = 'new ol.style.Style({'
            . 'stroke: new ol.style.Stroke(' . json_encode($strokeStyle) . ')';
        if ($label !== '') {
            $textStyle = ['text' => $label];
            $style .= ', text: new ol.style.Text(' . json_encode($textStyle) . ')';
        }

        $style .= '})';

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $wktCoordinates = mb_substr($spatial, 11, -1);
        $olGeometry = $this->toOpenLayersObject(
            'ol.geom.LineString',
            $this->extractPoints1d($wktCoordinates, null),
            $srid,
        );

        return $this->addGeometryToLayer($olGeometry, $style);
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
        $dataRow = $gisData[$index]['LINESTRING'] ?? null;
        $noOfPoints = max(2, $dataRow['data_length'] ?? 0);

        $wktPoints = [];
        /** @infection-ignore-all */
        for ($i = 0; $i < $noOfPoints; $i++) {
            $wktPoints[] = $this->getWktCoord($dataRow[$i] ?? null, $empty);
        }

        return 'LINESTRING(' . implode(',', $wktPoints) . ')';
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
        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = mb_substr($wkt, 11, -1);
        $pointsArr = $this->extractPoints1d($linestring, null);

        $noOfPoints = count($pointsArr);
        $coords = ['data_length' => $noOfPoints];
        /** @infection-ignore-all */
        for ($i = 0; $i < $noOfPoints; $i++) {
            $coords[$i] = ['x' => $pointsArr[$i][0], 'y' => $pointsArr[$i][1]];
        }

        return $coords;
    }

    protected function getType(): string
    {
        return 'LINESTRING';
    }
}
