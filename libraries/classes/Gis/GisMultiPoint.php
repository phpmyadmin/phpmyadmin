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
     * @param string $spatial    GIS POLYGON object
     * @param string $label      Label for the GIS POLYGON object
     * @param int[]  $color      Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     */
    public function prepareRowAsPng(
        $spatial,
        string $label,
        array $color,
        array $scale_data,
        ImageWrapper $image,
    ): ImageWrapper {
        // allocate colors
        $black = $image->colorAllocate(0, 0, 0);
        $point_color = $image->colorAllocate(...$color);

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($multipoint, $scale_data);

        foreach ($points_arr as $point) {
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
                $point_color,
            );
        }

        // print label for each point
        if ($label !== '' && ($points_arr[0][0] != '' && $points_arr[0][1] != '')) {
            $image->string(
                1,
                (int) round($points_arr[0][0]),
                (int) round($points_arr[0][1]),
                $label,
                $black,
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTIPOINT object
     * @param string $label      Label for the GIS MULTIPOINT object
     * @param int[]  $color      Color for the GIS MULTIPOINT object
     * @param array  $scale_data Array containing data related to scaling
     * @param TCPDF  $pdf
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(
        $spatial,
        string $label,
        array $color,
        array $scale_data,
        $pdf,
    ): TCPDF {
        $line = [
            'width' => 1.25,
            'color' => $color,
        ];

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($multipoint, $scale_data);

        foreach ($points_arr as $point) {
            // draw a small circle to mark the point
            if ($point[0] == '' || $point[1] == '') {
                continue;
            }

            $pdf->Circle($point[0], $point[1], 2, 0, 360, 'D', $line);
        }

        // print label for each point
        if ($label !== '' && ($points_arr[0][0] != '' && $points_arr[0][1] != '')) {
            $pdf->setXY($points_arr[0][0], $points_arr[0][1]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS MULTIPOINT object
     * @param string $label      Label for the GIS MULTIPOINT object
     * @param int[]  $color      Color for the GIS MULTIPOINT object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, string $label, array $color, array $scale_data): string
    {
        $point_options = [
            'name' => $label,
            'class' => 'multipoint vector',
            'fill' => 'white',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($multipoint, $scale_data);

        $row = '';
        foreach ($points_arr as $point) {
            if (((float) $point[0]) === 0.0 || ((float) $point[1]) === 0.0) {
                continue;
            }

            $row .= '<circle cx="' . $point[0] . '" cy="'
                . $point[1] . '" r="3"';
            $point_options['id'] = $label . $this->getRandomId();
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
     * @param string $spatial    GIS MULTIPOINT object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS MULTIPOINT object
     * @param int[]  $color      Color for the GIS MULTIPOINT object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl(
        $spatial,
        int $srid,
        string $label,
        array $color,
        array $scale_data,
    ): string {
        $fill_style = ['color' => 'white'];
        $stroke_style = [
            'color' => $color,
            'width' => 2,
        ];
        $result = 'var fill = new ol.style.Fill(' . json_encode($fill_style) . ');'
            . 'var stroke = new ol.style.Stroke(' . json_encode($stroke_style) . ');'
            . 'var style = new ol.style.Style({'
            . 'image: new ol.style.Circle({'
            . 'fill: fill,'
            . 'stroke: stroke,'
            . 'radius: 3'
            . '}),'
            . 'fill: fill,'
            . 'stroke: stroke';
        if ($label !== '') {
            $text_style = [
                'text' => $label,
                'offsetY' => -9,
            ];
            $result .= ',text: new ol.style.Text(' . json_encode($text_style) . ')';
        }

        $result .= '});';

        if ($srid === 0) {
            $srid = 4326;
        }

        $result .= $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($multipoint, null);

        return $result . 'var multiPoint = new ol.geom.MultiPoint('
            . $this->getPointsArrayForOpenLayers($points_arr, $srid) . ');'
            . 'var feature = new ol.Feature({geometry: multiPoint});'
            . 'feature.setStyle(style);'
            . 'vectorLayer.addFeature(feature);';
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array       $gis_data GIS data
     * @param int         $index    Index into the parameter object
     * @param string|null $empty    Multipoint does not adhere to this
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gis_data, $index, $empty = ''): string
    {
        $no_of_points = $gis_data[$index]['MULTIPOINT']['no_of_points'] ?? 1;
        if ($no_of_points < 1) {
            $no_of_points = 1;
        }

        $wkt = 'MULTIPOINT(';
        for ($i = 0; $i < $no_of_points; $i++) {
            $wkt .= (isset($gis_data[$index]['MULTIPOINT'][$i]['x'])
                    && trim((string) $gis_data[$index]['MULTIPOINT'][$i]['x']) != ''
                    ? $gis_data[$index]['MULTIPOINT'][$i]['x'] : '')
                . ' ' . (isset($gis_data[$index]['MULTIPOINT'][$i]['y'])
                    && trim((string) $gis_data[$index]['MULTIPOINT'][$i]['y']) != ''
                    ? $gis_data[$index]['MULTIPOINT'][$i]['y'] : '') . ',';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
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
        $wkt = 'MULTIPOINT(';
        for ($i = 0; $i < $row_data['numpoints']; $i++) {
            $wkt .= $row_data['points'][$i]['x'] . ' '
                . $row_data['points'][$i]['y'] . ',';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
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
        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $wkt_points = mb_substr($wkt, 11, -1);
        $points = $this->extractPoints($wkt_points, null);

        $no_of_points = count($points);
        $coords = ['no_of_points' => $no_of_points];
        for ($i = 0; $i < $no_of_points; $i++) {
            $coords[$i] = [
                'x' => $points[$i][0],
                'y' => $points[$i][1],
            ];
        }

        return $coords;
    }

    /**
     * Overridden to make sure that only the points having valid values
     * for x and y coordinates are added.
     *
     * @param array $points_arr x and y coordinates for each point
     * @param int   $srid       spatial reference id
     *
     * @return string JavaScript for adding an array of points to OpenLayers
     */
    protected function getPointsArrayForOpenLayers(array $points_arr, int $srid): string
    {
        $ol_array = 'new Array(';
        foreach ($points_arr as $point) {
            if ($point[0] == '' || $point[1] == '') {
                continue;
            }

            $ol_array .= $this->getPointForOpenLayers($point, $srid) . '.getCoordinates(), ';
        }

        if (mb_substr($ol_array, -2) === ', ') {
            $ol_array = mb_substr($ol_array, 0, -2);
        }

        return $ol_array . ')';
    }
}
