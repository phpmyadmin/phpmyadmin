<?php
/**
 * Base class for all GIS data type classes
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function array_map;
use function array_reverse;
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
     * @param string  $spatial   GIS data object
     * @param string  $label     label for the GIS data object
     * @param int[]   $color     color for the GIS data object
     * @param mixed[] $scaleData data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    abstract public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scaleData): string;

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS POLYGON object
     * @param string  $label     Label for the GIS POLYGON object
     * @param int[]   $color     Color for the GIS POLYGON object
     * @param mixed[] $scaleData Array containing data related to scaling
     */
    abstract public function prepareRowAsPng(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        ImageWrapper $image,
    ): ImageWrapper;

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS data object
     * @param string  $label     label for the GIS data object
     * @param int[]   $color     color for the GIS data object
     * @param mixed[] $scaleData array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    abstract public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
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
     * @param mixed[]     $gisData GIS data
     * @param int         $index   index into the parameter object
     * @param string|null $empty   value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    abstract public function generateWkt(array $gisData, int $index, string|null $empty = ''): string;

    /**
     * Updates the min, max values with the given point set.
     *
     * @param string         $pointSet  point set
     * @param ScaleData|null $scaleData existing min, max values
     *
     * @return ScaleData|null the updated min, max values
     */
    protected function setMinMax(string $pointSet, ScaleData|null $scaleData = null): ScaleData|null
    {
        // Separate each point
        $points = explode(',', $pointSet);

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
        $geomTypes = '(POINT|MULTIPOINT|LINESTRING|MULTILINESTRING|POLYGON|MULTIPOLYGON|GEOMETRYCOLLECTION)';
        $srid = 0;
        $wkt = '';

        if (preg_match("/^'" . $geomTypes . "\(.*\)',[0-9]*$/i", $value)) {
            $lastComma = mb_strripos($value, ',');
            $srid = (int) trim(mb_substr($value, $lastComma + 1));
            $wkt = trim(mb_substr($value, 1, $lastComma - 2));
        } elseif (preg_match('/^' . $geomTypes . '\(.*\)$/i', $value)) {
            $wkt = $value;
        }

        return ['srid' => $srid, 'wkt' => $wkt];
    }

    /**
     * Generate coordinate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $wkt Value of the GIS column
     *
     * @return mixed[] Coordinate params for the GIS data editor from the value of the GIS column
     */
    abstract protected function getCoordinateParams(string $wkt): array;

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value Value of the GIS column
     *
     * @return mixed[] params for the GIS data editor from the value of the GIS column
     */
    public function generateParams(string $value): array
    {
        $data = $this->parseWktAndSrid($value);
        $index = 0;
        $wkt = $data['wkt'];
        $wktType = strtoupper(array_reverse(explode('Gis', static::class))[0]);

        return ['srid' => $data['srid'], $index => [$wktType => $this->getCoordinateParams($wkt)]];
    }

    /**
     * Extracts points, scales and returns them as an array.
     *
     * @param string       $pointSet  string of comma separated points
     * @param mixed[]|null $scaleData data related to scaling
     * @param bool         $linear    if true, as a 1D array, else as a 2D array
     *
     * @return float[]|float[][] scaled points
     */
    private function extractPointsInternal(string $pointSet, array|null $scaleData, bool $linear): array
    {
        $pointsArr = [];

        // Separate each point
        $points = explode(',', $pointSet);

        foreach ($points as $point) {
            $point = str_replace(['(', ')'], '', $point);
            // Extract coordinates of the point
            $coordinates = explode(' ', $point);

            if (isset($coordinates[1]) && trim($coordinates[0]) != '' && trim($coordinates[1]) != '') {
                if ($scaleData === null) {
                    $x = (float) $coordinates[0];
                    $y = (float) $coordinates[1];
                } else {
                    $x = (float) (((float) $coordinates[0] - $scaleData['x']) * $scaleData['scale']);
                    $y = (float) ($scaleData['height']
                        - ((float) $coordinates[1] - $scaleData['y']) * $scaleData['scale']);
                }
            } else {
                $x = 0.0;
                $y = 0.0;
            }

            if ($linear) {
                $pointsArr[] = $x;
                $pointsArr[] = $y;
            } else {
                $pointsArr[] = [$x, $y];
            }
        }

        return $pointsArr;
    }

    /**
     * Extracts points, scales and returns them as an array.
     *
     * @param string       $wktCoords string of comma separated points
     * @param mixed[]|null $scaleData data related to scaling
     *
     * @return float[][] scaled points
     */
    protected function extractPoints1d(string $wktCoords, array|null $scaleData): array
    {
        /** @var float[][] $pointsArr */
        $pointsArr = $this->extractPointsInternal($wktCoords, $scaleData, false);

        return $pointsArr;
    }

    /**
     * Extracts points, scales and returns them as an linear array.
     *
     * @param string       $wktCoords string of comma separated points
     * @param mixed[]|null $scaleData data related to scaling
     *
     * @return float[] scaled points
     */
    protected function extractPoints1dLinear(string $wktCoords, array|null $scaleData): array
    {
        /** @var float[] $pointsArr */
        $pointsArr = $this->extractPointsInternal($wktCoords, $scaleData, true);

        return $pointsArr;
    }

    /**
     * @param string       $wktCoords string of ),( separated points
     * @param mixed[]|null $scaleData data related to scaling
     *
     * @return float[][][]  scaled points
     */
    protected function extractPoints2d(string $wktCoords, array|null $scaleData): array
    {
        $parts = explode('),(', $wktCoords);

        return array_map(function ($coord) use ($scaleData) {
            return $this->extractPoints1d($coord, $scaleData);
        }, $parts);
    }

    /**
     * @param string       $wktCoords string of )),(( separated points
     * @param mixed[]|null $scaleData data related to scaling
     *
     * @return float[][][][] scaled points
     */
    protected function extractPoints3d(string $wktCoords, array|null $scaleData): array
    {
        $parts = explode(')),((', $wktCoords);

        return array_map(function ($coord) use ($scaleData) {
            return $this->extractPoints2d($coord, $scaleData);
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
