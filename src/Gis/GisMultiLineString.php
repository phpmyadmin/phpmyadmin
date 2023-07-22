<?php
/**
 * Handles actions related to GIS MULTILINESTRING objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function explode;
use function implode;
use function json_encode;
use function max;
use function mb_substr;
use function round;
use function sprintf;

/**
 * Handles actions related to GIS MULTILINESTRING objects
 */
class GisMultiLineString extends GisGeometry
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
     * @return GisMultiLineString the singleton
     */
    public static function singleton(): GisMultiLineString
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisMultiLineString();
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
        $extent = Extent::empty();

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilineString = mb_substr($wkt, 17, -2);
        // Separate each linestring
        $linestrings = explode('),(', $multilineString);

        foreach ($linestrings as $linestring) {
            $extent = $extent->merge($this->getCoordinatesExtent($linestring));
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
        $lineColor = $image->colorAllocate(...$color);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilineString = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestrings = explode('),(', $multilineString);

        $firstLine = true;
        foreach ($linestrings as $linestring) {
            $pointsArr = $this->extractPoints1d($linestring, $scaleData);
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

            unset($tempPoint);
            // print label if applicable
            if ($label !== '' && $firstLine) {
                $image->string(
                    1,
                    (int) round($pointsArr[1][0]),
                    (int) round($pointsArr[1][1]),
                    $label,
                    $black,
                );
            }

            $firstLine = false;
        }
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string    $spatial   GIS MULTILINESTRING object
     * @param string    $label     Label for the GIS MULTILINESTRING object
     * @param int[]     $color     Color for the GIS MULTILINESTRING object
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

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilineString = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestrings = explode('),(', $multilineString);

        $firstLine = true;
        foreach ($linestrings as $linestring) {
            $pointsArr = $this->extractPoints1d($linestring, $scaleData);
            foreach ($pointsArr as $point) {
                if (isset($tempPoint)) {
                    // draw line section
                    $pdf->Line($tempPoint[0], $tempPoint[1], $point[0], $point[1], $line);
                }

                $tempPoint = $point;
            }

            unset($tempPoint);
            // print label
            if ($label !== '' && $firstLine) {
                $pdf->setXY($pointsArr[1][0], $pointsArr[1][1]);
                $pdf->setFontSize(5);
                $pdf->Cell(0, 0, $label);
            }

            $firstLine = false;
        }
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string    $spatial   GIS MULTILINESTRING object
     * @param string    $label     Label for the GIS MULTILINESTRING object
     * @param int[]     $color     Color for the GIS MULTILINESTRING object
     * @param ScaleData $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, ScaleData $scaleData): string
    {
        $lineOptions = [
            'name' => $label,
            'class' => 'linestring vector',
            'fill' => 'none',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilineString = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestrings = explode('),(', $multilineString);

        $row = '';
        foreach ($linestrings as $linestring) {
            $pointsArr = $this->extractPoints1d($linestring, $scaleData);

            $row .= '<polyline points="';
            foreach ($pointsArr as $point) {
                $row .= $point[0] . ',' . $point[1] . ' ';
            }

            $row .= '"';
            $lineOptions['id'] = $label . $this->getRandomId();
            foreach ($lineOptions as $option => $val) {
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
     * @param string $spatial GIS MULTILINESTRING object
     * @param int    $srid    Spatial reference ID
     * @param string $label   Label for the GIS MULTILINESTRING object
     * @param int[]  $color   Color for the GIS MULTILINESTRING object
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

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $wktCoordinates = mb_substr($spatial, 17, -2);
        $olGeometry = $this->toOpenLayersObject(
            'ol.geom.MultiLineString',
            $this->extractPoints2d($wktCoordinates, null),
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
        $dataRow = $gisData[$index]['MULTILINESTRING'] ?? null;
        $noOfLines = max(1, $dataRow['data_length'] ?? 0);

        $wktLines = [];
        /** @infection-ignore-all */
        for ($i = 0; $i < $noOfLines; $i++) {
            $noOfPoints = max(2, $dataRow[$i]['data_length'] ?? 0);

            $wktPoints = [];
            for ($j = 0; $j < $noOfPoints; $j++) {
                $wktPoints[] = $this->getWktCoord($dataRow[$i][$j] ?? null, $empty);
            }

            $wktLines[] = '(' . implode(',', $wktPoints) . ')';
        }

        return 'MULTILINESTRING(' . implode(',', $wktLines) . ')';
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
        $wkt = 'MULTILINESTRING(';
        /** @infection-ignore-all */
        for ($i = 0; $i < $rowData['numparts']; $i++) {
            $wkt .= '(';
            foreach ($rowData['parts'][$i]['points'] as $point) {
                $wkt .= $point['x'] . ' ' . $point['y'] . ',';
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
        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $wktMultilinestring = mb_substr($wkt, 17, -2);
        $wktLinestrings = explode('),(', $wktMultilinestring);
        $coords = ['data_length' => count($wktLinestrings)];

        foreach ($wktLinestrings as $j => $wktLinestring) {
            $points = $this->extractPoints1d($wktLinestring, null);
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
        return 'MULTILINESTRING';
    }
}
