<?php
/**
 * Handles actions related to GIS POINT objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function json_encode;
use function mb_substr;
use function round;
use function sprintf;
use function trim;

/**
 * Handles actions related to GIS POINT objects
 */
class GisPoint extends GisGeometry
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
     * @return GisPoint the singleton
     */
    public static function singleton(): GisPoint
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisPoint();
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
        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);

        return $this->setMinMax($point);
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

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $pointsArr = $this->extractPoints1dLinear($point, $scaleData);

        // draw a small circle to mark the point
        if ($pointsArr[0] != '' && $pointsArr[0] != '') {
            $image->arc(
                (int) round($pointsArr[0]),
                (int) round($pointsArr[1]),
                7,
                7,
                0,
                360,
                $pointColor,
            );
            // print label if applicable
            if ($label !== '') {
                $image->string(
                    1,
                    (int) round($pointsArr[0]),
                    (int) round($pointsArr[1]),
                    $label,
                    $black,
                );
            }
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS POINT object
     * @param string  $label     Label for the GIS POINT object
     * @param int[]   $color     Color for the GIS POINT object
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

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $pointsArr = $this->extractPoints1dLinear($point, $scaleData);

        // draw a small circle to mark the point
        if ($pointsArr[0] != '' && $pointsArr[1] != '') {
            $pdf->Circle($pointsArr[0], $pointsArr[1], 2, 0, 360, 'D', $line);
            // print label if applicable
            if ($label !== '') {
                $pdf->setXY($pointsArr[0], $pointsArr[1]);
                $pdf->setFontSize(5);
                $pdf->Cell(0, 0, $label);
            }
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string  $spatial   GIS POINT object
     * @param string  $label     Label for the GIS POINT object
     * @param int[]   $color     Color for the GIS POINT object
     * @param mixed[] $scaleData Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scaleData): string
    {
        $pointOptions = [
            'name' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'point vector',
            'fill' => 'white',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $pointsArr = $this->extractPoints1dLinear($point, $scaleData);

        $row = '';
        if ($pointsArr[0] !== 0.0 && $pointsArr[1] !== 0.0) {
            $row .= '<circle cx="' . $pointsArr[0]
                . '" cy="' . $pointsArr[1] . '" r="3"';
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
     * @param string $spatial GIS POINT object
     * @param int    $srid    Spatial reference ID
     * @param string $label   Label for the GIS POINT object
     * @param int[]  $color   Color for the GIS POINT object
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

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $olGeometry = $this->toOpenLayersObject(
            'ol.geom.Point',
            $this->extractPoints1dLinear($point, null),
            $srid,
        );

        return $this->addGeometryToLayer($olGeometry, $style);
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param mixed[]     $gisData GIS data
     * @param int         $index   Index into the parameter object
     * @param string|null $empty   Point does not adhere to this parameter
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gisData, int $index, string|null $empty = ''): string
    {
        return 'POINT('
        . (isset($gisData[$index]['POINT']['x'])
            && trim((string) $gisData[$index]['POINT']['x']) != ''
            ? $gisData[$index]['POINT']['x'] : '')
        . ' '
        . (isset($gisData[$index]['POINT']['y'])
            && trim((string) $gisData[$index]['POINT']['y']) != ''
            ? $gisData[$index]['POINT']['y'] : '') . ')';
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
        return 'POINT(' . ($rowData['x'] ?? '')
        . ' ' . ($rowData['y'] ?? '') . ')';
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
        // Trim to remove leading 'POINT(' and trailing ')'
        $wktPoint = mb_substr($wkt, 6, -1);
        $points = $this->extractPoints1d($wktPoint, null);

        return ['x' => $points[0][0], 'y' => $points[0][1]];
    }
}
