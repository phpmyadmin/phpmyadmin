<?php
/**
 * Handles actions related to GIS GEOMETRYCOLLECTION objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use ErrorException;
use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function implode;
use function mb_substr;
use function str_split;

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
     * Get coordinate extent for this wkt.
     *
     * @param string $wkt Well Known Text represenatation of the geometry
     *
     * @return Extent the min, max values for x and y coordinates
     */
    public function getExtent(string $wkt): Extent
    {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($wkt, 19, -1);

        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        $extent = Extent::empty();
        foreach ($subParts as $subPart) {
            $gisObj = GisFactory::fromWkt($subPart);
            if ($gisObj === null) {
                continue;
            }

            $extent = $extent->merge($gisObj->getExtent($subPart));
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
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $gisObj = GisFactory::fromWkt($subPart);
            if ($gisObj === null) {
                continue;
            }

            $gisObj->prepareRowAsPng($subPart, $label, $color, $scaleData, $image);
        }
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string    $spatial   GIS GEOMETRYCOLLECTION object
     * @param string    $label     label for the GIS GEOMETRYCOLLECTION object
     * @param int[]     $color     color for the GIS GEOMETRYCOLLECTION object
     * @param ScaleData $scaleData array containing data related to scaling
     */
    public function prepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        TCPDF $pdf,
    ): void {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $gisObj = GisFactory::fromWkt($subPart);
            if ($gisObj === null) {
                continue;
            }

            $gisObj->prepareRowAsPdf($subPart, $label, $color, $scaleData, $pdf);
        }
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string    $spatial   GIS GEOMETRYCOLLECTION object
     * @param string    $label     label for the GIS GEOMETRYCOLLECTION object
     * @param int[]     $color     color for the GIS GEOMETRYCOLLECTION object
     * @param ScaleData $scaleData array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg(string $spatial, string $label, array $color, ScaleData $scaleData): string
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $geomCol = mb_substr($spatial, 19, -1);
        // Split the geometry collection object to get its constituents.
        $subParts = $this->explodeGeomCol($geomCol);

        foreach ($subParts as $subPart) {
            $gisObj = GisFactory::fromWkt($subPart);
            if ($gisObj === null) {
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
            $gisObj = GisFactory::fromWkt($subPart);
            if ($gisObj === null) {
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
                if ($brCount === 0) {
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
     * @param mixed[] $gisData GIS data
     * @param int     $index   index into the parameter object
     * @param string  $empty   value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gisData, int $index, string $empty = ''): string
    {
        $geomCount = $gisData['GEOMETRYCOLLECTION']['data_length'] ?? 1;
        $wktGeoms = [];
        /** @infection-ignore-all */
        for ($i = 0; $i < $geomCount; $i++) {
            if (! isset($gisData[$i]['gis_type'])) {
                continue;
            }

            $type = $gisData[$i]['gis_type'];
            $gisObj = GisFactory::fromType($type);
            if ($gisObj === null) {
                continue;
            }

            $wktGeoms[] = $gisObj->generateWkt($gisData, $i, $empty);
        }

        return 'GEOMETRYCOLLECTION(' . implode(',', $wktGeoms) . ')';
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
        $params = ['srid' => $data['srid'], 'GEOMETRYCOLLECTION' => ['data_length' => count($wktGeometries)]];

        foreach ($wktGeometries as $wktGeometry) {
            $gisObj = GisFactory::fromWkt($wktGeometry);
            if ($gisObj === null) {
                continue;
            }

            $wktType = $gisObj->getType();
            $params[] = ['gis_type' => $wktType, $wktType => $gisObj->getCoordinateParams($wktGeometry)];
        }

        return $params;
    }

    protected function getType(): string
    {
        return 'GEOMETRYCOLLECTION';
    }
}
