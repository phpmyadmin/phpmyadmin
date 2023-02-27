<?php
/**
 * Handles actions related to GIS MULTILINESTRING objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function explode;
use function json_encode;
use function mb_substr;
use function round;
use function sprintf;
use function trim;

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
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return ScaleData|null the min, max values for x and y coordinates
     */
    public function scaleRow(string $spatial): ScaleData|null
    {
        $min_max = null;

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestirngs = explode('),(', $multilinestirng);

        foreach ($linestirngs as $linestring) {
            $min_max = $this->setMinMax($linestring, $min_max);
        }

        return $min_max;
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
        $line_color = $image->colorAllocate(...$color);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestirngs = explode('),(', $multilinestirng);

        $first_line = true;
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);
            foreach ($points_arr as $point) {
                if (isset($temp_point)) {
                    // draw line section
                    $image->line(
                        (int) round($temp_point[0]),
                        (int) round($temp_point[1]),
                        (int) round($point[0]),
                        (int) round($point[1]),
                        $line_color,
                    );
                }

                $temp_point = $point;
            }

            unset($temp_point);
            // print label if applicable
            if ($label !== '' && $first_line) {
                $image->string(
                    1,
                    (int) round($points_arr[1][0]),
                    (int) round($points_arr[1][1]),
                    $label,
                    $black,
                );
            }

            $first_line = false;
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param int[]  $color      Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     * @param TCPDF  $pdf
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, string $label, array $color, array $scale_data, $pdf): TCPDF
    {
        $line = [
            'width' => 1.5,
            'color' => $color,
        ];

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestirngs = explode('),(', $multilinestirng);

        $first_line = true;
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);
            foreach ($points_arr as $point) {
                if (isset($temp_point)) {
                    // draw line section
                    $pdf->Line($temp_point[0], $temp_point[1], $point[0], $point[1], $line);
                }

                $temp_point = $point;
            }

            unset($temp_point);
            // print label
            if ($label !== '' && $first_line) {
                $pdf->setXY($points_arr[1][0], $points_arr[1][1]);
                $pdf->setFontSize(5);
                $pdf->Cell(0, 0, $label);
            }

            $first_line = false;
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param int[]  $color      Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, string $label, array $color, array $scale_data): string
    {
        $line_options = [
            'name' => $label,
            'class' => 'linestring vector',
            'fill' => 'none',
            'stroke' => sprintf('#%02x%02x%02x', ...$color),
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestirngs = explode('),(', $multilinestirng);

        $row = '';
        foreach ($linestirngs as $linestring) {
            $points_arr = $this->extractPoints($linestring, $scale_data);

            $row .= '<polyline points="';
            foreach ($points_arr as $point) {
                $row .= $point[0] . ',' . $point[1] . ' ';
            }

            $row .= '"';
            $line_options['id'] = $label . $this->getRandomId();
            foreach ($line_options as $option => $val) {
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
     * @param string $spatial    GIS MULTILINESTRING object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS MULTILINESTRING object
     * @param int[]  $color      Color for the GIS MULTILINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, int $srid, string $label, array $color, array $scale_data): string
    {
        $stroke_style = [
            'color' => $color,
            'width' => 2,
        ];

        $row = 'var style = new ol.style.Style({'
            . 'stroke: new ol.style.Stroke(' . json_encode($stroke_style) . ')';
        if ($label !== '') {
            $text_style = ['text' => $label];
            $row .= ', text: new ol.style.Text(' . json_encode($text_style) . ')';
        }

        $row .= '});';

        if ($srid === 0) {
            $srid = 4326;
        }

        $row .= $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $multilinestirng = mb_substr($spatial, 17, -2);
        // Separate each linestring
        $linestirngs = explode('),(', $multilinestirng);

        return $row . $this->getLineArrayForOpenLayers($linestirngs, $srid)
            . 'var multiLineString = new ol.geom.MultiLineString(arr);'
            . 'var feature = new ol.Feature({geometry: multiLineString});'
            . 'feature.setStyle(style);'
            . 'vectorLayer.addFeature(feature);';
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array       $gis_data GIS data
     * @param int         $index    Index into the parameter object
     * @param string|null $empty    Value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gis_data, $index, $empty = ''): string
    {
        $data_row = $gis_data[$index]['MULTILINESTRING'];

        $no_of_lines = $data_row['no_of_lines'] ?? 1;
        if ($no_of_lines < 1) {
            $no_of_lines = 1;
        }

        $wkt = 'MULTILINESTRING(';
        for ($i = 0; $i < $no_of_lines; $i++) {
            $no_of_points = $data_row[$i]['no_of_points'] ?? 2;
            if ($no_of_points < 2) {
                $no_of_points = 2;
            }

            $wkt .= '(';
            for ($j = 0; $j < $no_of_points; $j++) {
                $wkt .= (isset($data_row[$i][$j]['x'])
                        && trim((string) $data_row[$i][$j]['x']) != ''
                        ? $data_row[$i][$j]['x'] : $empty)
                    . ' ' . (isset($data_row[$i][$j]['y'])
                        && trim((string) $data_row[$i][$j]['y']) != ''
                        ? $data_row[$i][$j]['y'] : $empty) . ',';
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
     * @param array $row_data GIS data
     *
     * @return string the WKT for the data from ESRI shape files
     */
    public function getShape(array $row_data): string
    {
        $wkt = 'MULTILINESTRING(';
        for ($i = 0; $i < $row_data['numparts']; $i++) {
            $wkt .= '(';
            foreach ($row_data['parts'][$i]['points'] as $point) {
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
     * @return array Coordinate params for the GIS data editor from the value of the GIS column
     */
    protected function getCoordinateParams(string $wkt): array
    {
        // Trim to remove leading 'MULTILINESTRING((' and trailing '))'
        $wkt_multilinestring = mb_substr($wkt, 17, -2);
        $wkt_linestrings = explode('),(', $wkt_multilinestring);
        $coords = ['no_of_lines' => count($wkt_linestrings)];

        foreach ($wkt_linestrings as $j => $wkt_linestring) {
            $points = $this->extractPoints($wkt_linestring, null);
            $no_of_points = count($points);
            $coords[$j] = ['no_of_points' => $no_of_points];
            for ($i = 0; $i < $no_of_points; $i++) {
                $coords[$j][$i] = [
                    'x' => $points[$i][0],
                    'y' => $points[$i][1],
                ];
            }
        }

        return $coords;
    }
}
