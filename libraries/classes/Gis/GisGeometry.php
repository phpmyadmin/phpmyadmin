<?php
/**
 * Base class for all GIS data type classes
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function array_map;
use function defined;
use function explode;
use function json_encode;
use function mb_strripos;
use function mb_substr;
use function mt_getrandmax;
use function preg_match;
use function random_int;
use function str_replace;
use function strtoupper;
use function trim;

/**
 * Base class for all GIS data type classes.
 */
abstract class GisGeometry
{
    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS data object
     * @param string $label      label for the GIS data object
     * @param int[]  $color      color for the GIS data object
     * @param array  $scale_data data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    abstract public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scale_data): string;

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS POLYGON object
     * @param string $label      Label for the GIS POLYGON object
     * @param int[]  $color      Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     */
    abstract public function prepareRowAsPng(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        ImageWrapper $image,
    ): ImageWrapper;

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS data object
     * @param string $label      label for the GIS data object
     * @param int[]  $color      color for the GIS data object
     * @param array  $scale_data array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    abstract public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        TCPDF $pdf,
    ): TCPDF;

    /**
     * Prepares the JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial GIS data object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS data object
     * @param int[]  $color   color for the GIS data object
     *
     * @return string the JavaScript related to a row in the GIS dataset
     */
    abstract public function prepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
    ): string;

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return ScaleData|null min, max values for x and y coordinates
     */
    abstract public function scaleRow(string $spatial): ScaleData|null;

    /**
     * Generates the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array       $gis_data GIS data
     * @param int         $index    index into the parameter object
     * @param string|null $empty    value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    abstract public function generateWkt(array $gis_data, int $index, string|null $empty = ''): string;

    /**
     * Updates the min, max values with the given point set.
     *
     * @param string         $point_set point set
     * @param ScaleData|null $scaleData existing min, max values
     *
     * @return ScaleData|null the updated min, max values
     */
    protected function setMinMax(string $point_set, ScaleData|null $scaleData = null): ScaleData|null
    {
        // Separate each point
        $points = explode(',', $point_set);

        foreach ($points as $point) {
            // Extract coordinates of the point
            $coordinates = explode(' ', $point);

            $x = (float) $coordinates[0];
            $y = (float) $coordinates[1];

            $scaleData = $scaleData === null ? new ScaleData($x, $x, $y, $y) : $scaleData->expand($x, $y);
        }

        return $scaleData;
    }

    /**
     * Parses the wkt and optional srid from a combined string for the GIS data editor
     *
     * @param string $value value of the GIS column
     *
     * @return array<string,int|string> parameters for the GIS editor from the value of the GIS column
     * @psalm-return array{'srid':int,'wkt':string}
     */
    protected function parseWktAndSrid(string $value): array
    {
        $geom_types = '(POINT|MULTIPOINT|LINESTRING|MULTILINESTRING|POLYGON|MULTIPOLYGON|GEOMETRYCOLLECTION)';
        $srid = 0;
        $wkt = '';

        if (preg_match("/^'" . $geom_types . "\(.*\)',[0-9]*$/i", $value)) {
            $last_comma = mb_strripos($value, ',');
            $srid = (int) trim(mb_substr($value, $last_comma + 1));
            $wkt = trim(mb_substr($value, 1, $last_comma - 2));
        } elseif (preg_match('/^' . $geom_types . '\(.*\)$/i', $value)) {
            $wkt = $value;
        }

        return [
            'srid' => $srid,
            'wkt' => $wkt,
        ];
    }

    /**
     * Generate coordinate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $wkt Value of the GIS column
     *
     * @return array Coordinate params for the GIS data editor from the value of the GIS column
     */
    abstract protected function getCoordinateParams(string $wkt): array;

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value Value of the GIS column
     *
     * @return array params for the GIS data editor from the value of the GIS column
     */
    public function generateParams(string $value): array
    {
        $data = $this->parseWktAndSrid($value);
        $index = 0;
        $wkt = $data['wkt'];
        preg_match('/^\w+/', $wkt, $matches);
        $wkt_type = strtoupper($matches[0]);

        return [
            'srid' => $data['srid'],
            $index => [
                $wkt_type => $this->getCoordinateParams($wkt),
            ],
        ];
    }

    /**
     * Extracts points, scales and returns them as an array.
     *
     * @param string     $point_set  string of comma separated points
     * @param array|null $scale_data data related to scaling
     * @param bool       $linear     if true, as a 1D array, else as a 2D array
     *
     * @return float[]|float[][] scaled points
     */
    private function extractPointsInternal(string $point_set, array|null $scale_data, bool $linear): array
    {
        $points_arr = [];

        // Separate each point
        $points = explode(',', $point_set);

        foreach ($points as $point) {
            $point = str_replace(['(', ')'], '', $point);
            // Extract coordinates of the point
            $coordinates = explode(' ', $point);

            if (isset($coordinates[1]) && trim($coordinates[0]) != '' && trim($coordinates[1]) != '') {
                if ($scale_data === null) {
                    $x = (float) $coordinates[0];
                    $y = (float) $coordinates[1];
                } else {
                    $x = (float) (((float) $coordinates[0] - $scale_data['x']) * $scale_data['scale']);
                    $y = (float) ($scale_data['height']
                        - ((float) $coordinates[1] - $scale_data['y']) * $scale_data['scale']);
                }
            } else {
                $x = 0.0;
                $y = 0.0;
            }

            if ($linear) {
                $points_arr[] = $x;
                $points_arr[] = $y;
            } else {
                $points_arr[] = [$x, $y];
            }
        }

        return $points_arr;
    }

    /**
     * Extracts points, scales and returns them as an array.
     *
     * @param string     $wktCoords  string of comma separated points
     * @param array|null $scale_data data related to scaling
     *
     * @return float[][] scaled points
     */
    protected function extractPoints1d(string $wktCoords, array|null $scale_data): array
    {
        /** @var float[][] $points_arr */
        $points_arr = $this->extractPointsInternal($wktCoords, $scale_data, false);

        return $points_arr;
    }

    /**
     * Extracts points, scales and returns them as an linear array.
     *
     * @param string     $wktCoords  string of comma separated points
     * @param array|null $scale_data data related to scaling
     *
     * @return float[] scaled points
     */
    protected function extractPoints1dLinear(string $wktCoords, array|null $scale_data): array
    {
        /** @var float[] $points_arr */
        $points_arr = $this->extractPointsInternal($wktCoords, $scale_data, true);

        return $points_arr;
    }

    /**
     * @param string     $wktCoords  string of ),( separated points
     * @param array|null $scale_data data related to scaling
     *
     * @return float[][][]  scaled points
     */
    protected function extractPoints2d(string $wktCoords, array|null $scale_data): array
    {
        $parts = explode('),(', $wktCoords);

        return array_map(function ($coord) use ($scale_data) {
            return $this->extractPoints1d($coord, $scale_data);
        }, $parts);
    }

    /**
     * @param string     $wktCoords  string of )),(( separated points
     * @param array|null $scale_data data related to scaling
     *
     * @return float[][][][] scaled points
     */
    protected function extractPoints3d(string $wktCoords, array|null $scale_data): array
    {
        $parts = explode(')),((', $wktCoords);

        return array_map(function ($coord) use ($scale_data) {
            return $this->extractPoints2d($coord, $scale_data);
        }, $parts);
    }

    /**
     * @param string                                      $constructor
     * OpenLayers geometry constructor string
     * @param float[]|float[][]|float[][][]|float[][][][] $coordinates
     * Array of coordintes 1-4 dimensions
     */
    protected function toOpenLayersObject(string $constructor, array $coordinates, int $srid): string
    {
        $ol = 'new ' . $constructor . '(' . json_encode($coordinates) . ')';
        if ($srid != 3857) {
            $ol .= '.transform(\'EPSG:' . ($srid ?: 4326) . '\', \'EPSG:3857\')';
        }

        return $ol;
    }

    protected function addGeometryToLayer(string $olGeometry, string $style): string
    {
        return 'var feature = new ol.Feature(' . $olGeometry . ');'
            . 'feature.setStyle(' . $style . ');'
            . 'vectorSource.addFeature(feature);';
    }

    protected function getRandomId(): int
    {
        return ! defined('TESTSUITE') ? random_int(0, mt_getrandmax()) : 1234567890;
    }
}
