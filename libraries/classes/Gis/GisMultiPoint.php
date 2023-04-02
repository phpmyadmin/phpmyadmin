<?php
/**
 * Handles actions related to GIS MULTIPOINT objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function json_encode;
use function mb_substr;
use function round;
use function sprintf;
use function trim;

/**
 * Handles actions related to GIS MULTIPOINT objects
 */
class GisMultiPoint extends GisGeometry
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
     * @return GisMultiPoint the singleton
     */
    public static function singleton(): GisMultiPoint
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisMultiPoint();
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
        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);

        return $this->setMinMax($multipoint);
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
        $pointColor = $image->colorAllocate(...$color);

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $pointsArr = $this->extractPoints1d($multipoint, $scaleData);

        foreach ($pointsArr as $point) {
            // draw a small circle to mark the point
            if ($point[0] == '' || $point[1] == '') {
                continue;
            }

            $image->arc(
                (int) round($point[0]),
                (int) round($point[1]),
                7,
                7,
                0,
                360,
                $pointColor,
            );
        }

        // print label for each point
        if ($label !== '' && ($pointsArr[0][0] != '' && $pointsArr[0][1] != '')) {
            $image->string(
                1,
                (int) round($pointsArr[0][0]),
                (int) round($pointsArr[0][1]),
                $label,
                $black,
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS MULTIPOINT object
     * @param string  $label     Label for the GIS MULTIPOINT object
     * @param int[]   $color     Color for the GIS MULTIPOINT object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        TCPDF $pdf,
    ): TCPDF {
        $line = ['width' => 1.25, 'color' => $color];

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $pointsArr = $this->extractPoints1d($multipoint, $scaleData);

        foreach ($pointsArr as $point) {
            // draw a small circle to mark the point
            if ($point[0] == '' || $point[1] == '') {
                continue;
            }

            $pdf->Circle($point[0], $point[1], 2, 0, 360, 'D', $line);
        }

        // print label for each point
        if ($label !== '' && ($pointsArr[0][0] != '' && $pointsArr[0][1] != '')) {
            $pdf->setXY($pointsArr[0][0], $pointsArr[0][1]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string  $spatial   GIS MULTIPOINT object
     * @param string  $label     Label for the GIS MULTIPOINT object
     * @param int[]   $color     Color for the GIS MULTIPOINT object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scaleData): string
    {
        $pointOptions = [
            'name' => $label,
            'class' => 'multipoint vector',
            'fill' => 'white',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $pointsArr = $this->extractPoints1d($multipoint, $scaleData);

        $row = '';
        foreach ($pointsArr as $point) {
            if ($point[0] === 0.0 || $point[1] === 0.0) {
                continue;
            }

            $row .= '<circle cx="' . $point[0] . '" cy="'
                . $point[1] . '" r="3"';
            $pointOptions['id'] = $label . $this->getRandomId();
            foreach ($pointOptions as $option => $val) {
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
     * @param string $spatial GIS MULTIPOINT object
     * @param int    $srid    Spatial reference ID
     * @param string $label   Label for the GIS MULTIPOINT object
     * @param int[]  $color   Color for the GIS MULTIPOINT object
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
    ): string {
        $fillStyle = ['color' => 'white'];
        $strokeStyle = ['color' => $color, 'width' => 2];
        $style = 'new ol.style.Style({'
            . 'image: new ol.style.Circle({'
            . 'fill: new ol.style.Fill(' . json_encode($fillStyle) . '),'
            . 'stroke: new ol.style.Stroke(' . json_encode($strokeStyle) . '),'
            . 'radius: 3'
            . '})';
        if ($label !== '') {
            $textStyle = ['text' => $label, 'offsetY' => -9];
            $style .= ',text: new ol.style.Text(' . json_encode($textStyle) . ')';
        }

        $style .= '})';

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $wktCoordinates = mb_substr($spatial, 11, -1);
        $olGeometry = $this->toOpenLayersObject(
            'ol.geom.MultiPoint',
            $this->extractPoints1d($wktCoordinates, null),
            $srid,
        );

        return $this->addGeometryToLayer($olGeometry, $style);
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param mixed[]     $gisData GIS data
     * @param int         $index   Index into the parameter object
     * @param string|null $empty   Multipoint does not adhere to this
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gisData, int $index, string|null $empty = ''): string
    {
        $noOfPoints = $gisData[$index]['MULTIPOINT']['no_of_points'] ?? 1;
        if ($noOfPoints < 1) {
            $noOfPoints = 1;
        }

        $wkt = 'MULTIPOINT(';
        for ($i = 0; $i < $noOfPoints; $i++) {
            $wkt .= (isset($gisData[$index]['MULTIPOINT'][$i]['x'])
                    && trim((string) $gisData[$index]['MULTIPOINT'][$i]['x']) != ''
                    ? $gisData[$index]['MULTIPOINT'][$i]['x'] : '')
                . ' ' . (isset($gisData[$index]['MULTIPOINT'][$i]['y'])
                    && trim((string) $gisData[$index]['MULTIPOINT'][$i]['y']) != ''
                    ? $gisData[$index]['MULTIPOINT'][$i]['y'] : '') . ',';
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
        $wkt = 'MULTIPOINT(';
        for ($i = 0; $i < $rowData['numpoints']; $i++) {
            $wkt .= $rowData['points'][$i]['x'] . ' '
                . $rowData['points'][$i]['y'] . ',';
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
        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $wktPoints = mb_substr($wkt, 11, -1);
        $points = $this->extractPoints1d($wktPoints, null);

        $noOfPoints = count($points);
        $coords = ['no_of_points' => $noOfPoints];
        for ($i = 0; $i < $noOfPoints; $i++) {
            $coords[$i] = ['x' => $points[$i][0], 'y' => $points[$i][1]];
        }

        return $coords;
    }
}
