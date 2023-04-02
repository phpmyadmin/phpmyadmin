<?php
/**
 * Handles actions related to GIS GEOMETRYCOLLECTION objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use ErrorException;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function mb_strpos;
use function mb_substr;
use function str_split;
use function strtoupper;

/**
 * Handles actions related to GIS GEOMETRYCOLLECTION objects
 */
class GisGeometryCollection extends GisGeometry
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
     * @return GisGeometryCollection the singleton
     */
    public static function singleton(): GisGeometryCollection
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisGeometryCollection();
        }

        return self::$instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     */
    public function scaleRow(string $spatial): ScaleData|null
    {
        $minMax = null;

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);

        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $typePos = mb_strpos($subPart, '(');
            if ($typePos === false) {
                continue;
            }

            $type = mb_substr($subPart, 0, $typePos);

            $gisObj = GisFactory::factory($type);
            if (! $gisObj) {
                continue;
            }

            $scaleData = $gisObj->scaleRow($subPart);

            $minMax = $minMax === null ? $scaleData : $scaleData?->merge($minMax);
        }

        return $minMax;
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
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $typePos = mb_strpos($subPart, '(');
            if ($typePos === false) {
                continue;
            }

            $type = mb_substr($subPart, 0, $typePos);

            $gisObj = GisFactory::factory($type);
            if (! $gisObj) {
                continue;
            }

            $image = $gisObj->prepareRowAsPng($subPart, $label, $color, $scaleData, $image);
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string  $spatial   GIS GEOMETRYCOLLECTION object
     * @param string  $label     label for the GIS GEOMETRYCOLLECTION object
     * @param int[]   $color     color for the GIS GEOMETRYCOLLECTION object
     * @param mixed[] $scaleData array containing data related to scaling
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(string $spatial, string $label, array $color, array $scaleData, TCPDF $pdf): TCPDF
    {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $typePos = mb_strpos($subPart, '(');
            if ($typePos === false) {
                continue;
            }

            $type = mb_substr($subPart, 0, $typePos);

            $gisObj = GisFactory::factory($type);
            if (! $gisObj) {
                continue;
            }

            $pdf = $gisObj->prepareRowAsPdf($subPart, $label, $color, $scaleData, $pdf);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string  $spatial   GIS GEOMETRYCOLLECTION object
     * @param string  $label     label for the GIS GEOMETRYCOLLECTION object
     * @param int[]   $color     color for the GIS GEOMETRYCOLLECTION object
     * @param mixed[] $scaleData array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, array $scaleData): string
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $typePos = mb_strpos($subPart, '(');
            if ($typePos === false) {
                continue;
            }

            $type = mb_substr($subPart, 0, $typePos);

            $gisObj = GisFactory::factory($type);
            if (! $gisObj) {
                continue;
            }

            $row .= $gisObj->prepareRowAsSvg($subPart, $label, $color, $scaleData);
        }

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial GIS GEOMETRYCOLLECTION object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS GEOMETRYCOLLECTION object
     * @param int[]  $color   color for the GIS GEOMETRYCOLLECTION object
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl(string $spatial, int $srid, string $label, array $color): string
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $typePos = mb_strpos($subPart, '(');
            if ($typePos === false) {
                continue;
            }

            $type = mb_substr($subPart, 0, $typePos);

            $gisObj = GisFactory::factory($type);
            if (! $gisObj) {
                continue;
            }

            $row .= $gisObj->prepareRowAsOl($subPart, $srid, $label, $color);
        }

        return $row;
    }

    /**
     * Splits the GEOMETRYCOLLECTION object and get its constituents.
     *
     * @param string $geomCol geometry collection string
     *
     * @return string[] the constituents of the geometry collection object
     */
    private function explodeGeomCol(string $geomCol): array
    {
        $subParts = [];
        $brCount = 0;
        $start = 0;
        $count = 0;
        foreach (str_split($geomCol) as $char) {
            if ($char === '(') {
                $brCount++;
            } elseif ($char === ')') {
                $brCount--;
                if ($brCount == 0) {
                    $subParts[] = mb_substr($geomCol, $start, $count + 1 - $start);
                    $start = $count + 2;
                }
            }

            $count++;
        }

        return $subParts;
    }

    /**
     * Generates the WKT with the set of parameters passed by the GIS editor.
     *
     * @param mixed[]     $gisData GIS data
     * @param int         $index   index into the parameter object
     * @param string|null $empty   value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gisData, int $index, string|null $empty = ''): string
    {
        $geomCount = $gisData['GEOMETRYCOLLECTION']['geom_count'] ?? 1;
        $wkt = 'GEOMETRYCOLLECTION(';
        for ($i = 0; $i < $geomCount; $i++) {
            if (! isset($gisData[$i]['gis_type'])) {
                continue;
            }

            $type = $gisData[$i]['gis_type'];
            $gisObj = GisFactory::factory($type);
            if (! $gisObj) {
                continue;
            }

            $wkt .= $gisObj->generateWkt($gisData, $i, $empty) . ',';
        }

        if (isset($gisData[0]['gis_type'])) {
            $wkt = mb_substr($wkt, 0, -1);
        }

        return $wkt . ')';
    }

    /** @inheritDoc */
    protected function getCoordinateParams(string $wkt): array
    {
        // GeometryCollection does not have coordinates of its own
        throw new ErrorException('Has no own coordinates');
    }

    /**
     * Generates parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     *
     * @return mixed[] parameters for the GIS editor from the value of the GIS column
     */
    public function generateParams(string $value): array
    {
        $data = $this->parseWktAndSrid($value);
        $wkt = $data['wkt'];

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($wkt, 19, -1);
        $wktGeometries = $this->explodeGeomCol($geomCol);
        $params = ['srid' => $data['srid'], 'GEOMETRYCOLLECTION' => ['geom_count' => count($wktGeometries)]];

        $i = 0;
        foreach ($wktGeometries as $wktGeometry) {
            $typePos = mb_strpos($wktGeometry, '(');
            if ($typePos === false) {
                continue;
            }

            $wktType = strtoupper(mb_substr($wktGeometry, 0, $typePos));
            $gisObj = GisFactory::factory($wktType);
            if (! $gisObj) {
                continue;
            }

            $params[$i++] = ['gis_type' => $wktType, $wktType => $gisObj->getCoordinateParams($wktGeometry)];
        }

        return $params;
    }
}
