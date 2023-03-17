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
     * @param string $spatial    GIS POLYGON object
     * @param string $label      Label for the GIS POLYGON object
     * @param int[]  $color      Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     */
    public function prepareRowAsPng(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        ImageWrapper $image,
    ): ImageWrapper {
        // allocate colors
        $black = $image->colorAllocate(0, 0, 0);
        $point_color = $image->colorAllocate(...$color);

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints1dLinear($point, $scale_data);

        // draw a small circle to mark the point
        if ($points_arr[0] != '' && $points_arr[0] != '') {
            $image->arc(
                (int) round($points_arr[0]),
                (int) round($points_arr[1]),
                7,
                7,
                0,
                360,
                $point_color,
            );
            // print label if applicable
            if ($label !== '') {
                $image->string(
                    1,
                    (int) round($points_arr[0]),
                    (int) round($points_arr[1]),
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
     * @param string $spatial    GIS POINT object
     * @param string $label      Label for the GIS POINT object
     * @param int[]  $color      Color for the GIS POINT object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        TCPDF $pdf,
    ): TCPDF {
        $line = [
            'width' => 1.25,
            'color' => $color,
        ];

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints1dLinear($point, $scale_data);

        // draw a small circle to mark the point
        if ($points_arr[0] != '' && $points_arr[1] != '') {
            $pdf->Circle($points_arr[0], $points_arr[1], 2, 0, 360, 'D', $line);
            // print label if applicable
            if ($label !== '') {
                $pdf->setXY($points_arr[0], $points_arr[1]);
                $pdf->setFontSize(5);
                $pdf->Cell(0, 0, $label);
            }
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS POINT object
     * @param string $label      Label for the GIS POINT object
     * @param int[]  $color      Color for the GIS POINT object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scale_data): string
    {
        $point_options = [
            'name' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'point vector',
            'fill' => 'white',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints1dLinear($point, $scale_data);

        $row = '';
        if ($points_arr[0] !== 0.0 && $points_arr[1] !== 0.0) {
            $row .= '<circle cx="' . $points_arr[0]
                . '" cy="' . $points_arr[1] . '" r="3"';
            foreach ($point_options as $option => $val) {
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
        $fill_style = ['color' => 'white'];
        $stroke_style = [
            'color' => $color,
            'width' => 2,
        ];
        $style = 'new ol.style.Style({'
            . 'image: new ol.style.Circle({'
            . 'fill: new ol.style.Fill(' . json_encode($fill_style) . '),'
            . 'stroke: new ol.style.Stroke(' . json_encode($stroke_style) . '),'
            . 'radius: 3'
            . '})';
        if ($label !== '') {
            $text_style = [
                'text' => $label,
                'offsetY' => -9,
            ];
            $style .= ',text: new ol.style.Text(' . json_encode($text_style) . ')';
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
     * @param array       $gis_data GIS data
     * @param int         $index    Index into the parameter object
     * @param string|null $empty    Point does not adhere to this parameter
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gis_data, int $index, string|null $empty = ''): string
    {
        return 'POINT('
        . (isset($gis_data[$index]['POINT']['x'])
            && trim((string) $gis_data[$index]['POINT']['x']) != ''
            ? $gis_data[$index]['POINT']['x'] : '')
        . ' '
        . (isset($gis_data[$index]['POINT']['y'])
            && trim((string) $gis_data[$index]['POINT']['y']) != ''
            ? $gis_data[$index]['POINT']['y'] : '') . ')';
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param array $row_data GIS data
     *
     * @return string the WKT for the data from ESRI shape files
     */
    public function getShape(array $row_data): string
    {
        return 'POINT(' . ($row_data['x'] ?? '')
        . ' ' . ($row_data['y'] ?? '') . ')';
    }

    /**
     * Generate coordinate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $wkt Value of the GIS column
     *
     * @return array Coordinate params for the GIS data editor from the value of the GIS column
     */
    protected function getCoordinateParams(string $wkt): array
    {
        // Trim to remove leading 'POINT(' and trailing ')'
        $wkt_point = mb_substr($wkt, 6, -1);
        $points = $this->extractPoints1d($wkt_point, null);

        return [
            'x' => $points[0][0],
            'y' => $points[0][1],
        ];
    }
}
