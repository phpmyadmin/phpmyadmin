<?php
/**
 * Handles visualization of GIS data
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;
use TCPDF;

use function assert;
use function count;
use function htmlspecialchars;
use function is_string;
use function max;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function rtrim;
use function trim;

use const PNG_ALL_FILTERS;

/**
 * Handles visualization of GIS data
 */
class GisVisualization
{
    /** Array of colors to be used for GIS visualizations.*/
    private const COLORS = [
        [176, 46, 224],
        [224, 100, 46],
        [224, 214, 46],
        [46, 151, 224],
        [188, 224, 46],
        [224, 46, 117],
        [92, 224, 46],
        [224, 176, 46],
        [0, 34, 224],
        [114, 108, 177],
        [72, 26, 54],
        [186, 198, 88],
        [18, 114, 36],
        [130, 81, 25],
        [35, 140, 116],
        [76, 72, 155],
        [135, 201, 191],
    ];

    /** @var array<int, array<int|string|null>> Raw data for the visualization */
    private array $data;

    /** The width of the GIS visualization.*/
    private int $width;
    /** The height of the GIS visualization. */
    private int $height;

    private string $spatialColumn;

    private string|null $labelColumn;

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getPos(): int
    {
        return $this->pos;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getSpatialColumn(): string
    {
        return $this->spatialColumn;
    }

    public function getLabelColumn(): string|null
    {
        return $this->labelColumn;
    }

    /**
     * Factory
     *
     * @param string $sqlQuery SQL to fetch raw data for visualization
     * @param int    $rows     number of rows
     * @param int    $pos      start position
     */
    public static function get(
        string $sqlQuery,
        GisVisualizationSettings $options,
        int $rows,
        int $pos,
    ): GisVisualization {
        return new GisVisualization($sqlQuery, $options, $rows, $pos);
    }

    /**
     * Get visualization
     *
     * @param array<int, array<int|string|null>> $data Raw data, if set, parameters other than $options will be ignored
     */
    public static function getByData(array $data, GisVisualizationSettings $options): GisVisualization
    {
        return new GisVisualization($data, $options);
    }

    /**
     * Check if data has SRID
     */
    public function hasSrid(): bool
    {
        foreach ($this->data as $row) {
            if ((int) $row['srid'] !== 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stores user specified options.
     *
     * @param array<int, array<int|string|null>>|string $sqlOrData SQL to fetch raw data for visualization or an array
     *                                                             with data. If it is an array row and pos are ignored
     * @param int                                       $rows      Number of rows
     * @param int                                       $pos       Start position
     */
    private function __construct(
        array|string $sqlOrData,
        GisVisualizationSettings $options,
        private int $rows = 0,
        private int $pos = 0,
    ) {
        $this->width = $options->width;

        $this->height = $options->height;

        $this->spatialColumn = $options->spatialColumn;

        $this->labelColumn = $options->labelColumn;

        $this->data = is_string($sqlOrData)
            ? $this->modifyQueryAndFetch($sqlOrData)
            : $sqlOrData;
    }

    /** @return array<int, array<string|null>> */
    private function modifyQueryAndFetch(string $sqlQuery): array
    {
        $modifiedSql = $this->modifySqlQuery($sqlQuery);

        return $this->fetchRawData($modifiedSql);
    }

    /**
     * Returns sql for fetching raw data
     *
     * @param string $sqlQuery The SQL to modify.
     *
     * @return string the modified sql query.
     */
    private function modifySqlQuery(string $sqlQuery): string
    {
        $modifiedQuery = 'SELECT ';
        $spatialAsText = 'ASTEXT';
        $spatialSrid = 'SRID';
        $axisOrder = '';

        $dbi = DatabaseInterface::getInstance();
        $mysqlVersion = $dbi->getVersion();
        $isMariaDB = $dbi->isMariaDB();

        if ($mysqlVersion >= 50600) {
            $spatialAsText = 'ST_ASTEXT';
            $spatialSrid = 'ST_SRID';
        }

        // If MYSQL version >= 8.0.1 override default axis order
        if ($mysqlVersion >= 80001 && ! $isMariaDB) {
            $axisOrder = ', \'axis-order=long-lat\'';
        }

        // If label column is chosen add it to the query
        if ($this->labelColumn !== null) {
            $modifiedQuery .= Util::backquote($this->labelColumn)
            . ', ';
        }

        // Wrap the spatial column with 'ST_ASTEXT()' function and add it
        $modifiedQuery .= $spatialAsText . '('
            . Util::backquote($this->spatialColumn)
            . $axisOrder . ') AS ' . Util::backquote($this->spatialColumn)
            . ', ';

        // Get the SRID
        $modifiedQuery .= $spatialSrid . '('
            . Util::backquote($this->spatialColumn)
            . ') AS ' . Util::backquote('srid') . ' ';

        // Append the original query as the inner query
        $modifiedQuery .= 'FROM (' . rtrim($sqlQuery, ';') . ') AS '
            . Util::backquote('temp_gis');

        // LIMIT clause
        if ($this->rows > 0) {
            $modifiedQuery .= ' LIMIT ' . ($this->pos > 0 ? $this->pos . ', ' : '') . $this->rows;
        }

        return $modifiedQuery;
    }

    /**
     * Returns raw data for GIS visualization.
     *
     * @return array<int, array<string|null>>
     */
    private function fetchRawData(string $modifiedSql): array
    {
        $modifiedResult = DatabaseInterface::getInstance()->tryQuery($modifiedSql);

        if ($modifiedResult === false) {
            return [];
        }

        return $modifiedResult->fetchAllAssoc();
    }

    /**
     * Sanitizes the file name.
     *
     * @param string $fileName file name
     * @param string $ext      extension of the file
     *
     * @return string the sanitized file name
     */
    private function sanitizeName(string $fileName, string $ext): string
    {
        $fileName = Sanitize::sanitizeFilename($fileName);

        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $requiredExtension = '.' . $ext;
        $extensionLength = mb_strlen($requiredExtension);
        $userExtension = mb_substr($fileName, -$extensionLength);
        if (mb_strtolower($userExtension) !== $requiredExtension) {
            $fileName .= $requiredExtension;
        }

        return $fileName;
    }

    /**
     * Handles common tasks of writing the visualization to file for various formats.
     *
     * @param string $fileName file name
     * @param string $type     mime type
     * @param string $ext      extension of the file
     */
    private function writeToFile(string $fileName, string $type, string $ext): void
    {
        $fileName = $this->sanitizeName($fileName, $ext);
        Core::downloadHeader($fileName, $type);
    }

    /**
     * Generate the visualization in SVG format.
     *
     * @return string the generated image resource
     */
    private function svg(): string
    {
        $svg = $this->prepareDataSet($this->data, 'svg');

        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'
            . "\n"
            . '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg"'
            . ' xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . $this->width . '"'
            . ' height="' . $this->height . '">'
            . '<g>' . $svg . '</g>'
            . '</svg>';
    }

    /**
     * Get the visualization as a SVG.
     *
     * @return string the visualization as a SVG
     */
    public function asSVG(): string
    {
        return $this->svg();
    }

    /**
     * Saves as a SVG image to a file.
     *
     * @param string $fileName File name
     */
    public function toFileAsSvg(string $fileName): void
    {
        $img = $this->svg();
        $this->writeToFile($fileName, 'image/svg+xml', 'svg');
        echo $img;
    }

    /**
     * Generate the visualization in PNG format.
     *
     * @return ImageWrapper|null the generated image resource
     */
    private function png(): ImageWrapper|null
    {
        $image = ImageWrapper::create(
            $this->width,
            $this->height,
            ['red' => 229, 'green' => 229, 'blue' => 229],
        );
        if ($image === null) {
            return null;
        }

        $this->prepareDataSet($this->data, 'png', $image);

        return $image;
    }

    /**
     * Saves as a PNG image to a file.
     *
     * @param string $fileName File name
     */
    public function toFileAsPng(string $fileName): void
    {
        $image = $this->png();
        if ($image === null) {
            return;
        }

        $this->writeToFile($fileName, 'image/png', 'png');
        $image->png(null, 9, PNG_ALL_FILTERS);
    }

    /**
     * Get the data for visualization with OpenLayers.
     *
     * @psalm-return list<mixed[]>
     */
    public function asOl(): array
    {
        return $this->prepareDataSet($this->data, 'ol');
    }

    /**
     * Saves as a PDF to a file.
     *
     * @param string $fileName File name
     */
    public function toFileAsPdf(string $fileName): void
    {
        // create pdf
        $pdf = new TCPDF('', 'pt', Config::getInstance()->settings['PDFDefaultPageSize'], true, 'UTF-8', false);

        // disable header and footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        //set auto page breaks
        $pdf->setAutoPageBreak(false);

        // add a page
        $pdf->AddPage();

        $this->prepareDataSet($this->data, 'pdf', $pdf);

        // sanitize file name
        $fileName = $this->sanitizeName($fileName, 'pdf');
        $pdf->Output($fileName, 'D');
    }

    /**
     * Convert file to given format
     *
     * @param string $filename Filename
     * @param string $format   Output format
     */
    public function toFile(string $filename, string $format): void
    {
        if ($format === 'svg') {
            $this->toFileAsSvg($filename);
        } elseif ($format === 'png') {
            $this->toFileAsPng($filename);
        } elseif ($format === 'pdf') {
            $this->toFileAsPdf($filename);
        }
    }

    /**
     * Calculates the scale, horizontal and vertical offset that should be used.
     *
     * @param mixed[][] $data Row data
     */
    private function scaleDataSet(array $data): ScaleData|null
    {
        $extent = Extent::empty();

        foreach ($data as $row) {
            // Figure out the data type
            $wkt = $row[$this->spatialColumn];
            if (! is_string($wkt)) {
                continue;
            }

            $gisObj = GisFactory::fromWkt($wkt);
            if ($gisObj === null) {
                continue;
            }

            // Update minimum/maximum values for x and y coordinates.
            $extent = $extent->merge($gisObj->getExtent($wkt));
        }

        if ($extent->isEmpty()) {
            return null;
        }

        $border = 15;

        // effective width and height of the plot
        $plotWidth = $this->width - 2 * $border;
        $plotHeight = $this->height - 2 * $border;

        // scale the visualization
        $xRatio = ($extent->maxX - $extent->minX) / $plotWidth;
        $yRatio = ($extent->maxY - $extent->minY) / $plotHeight;
        $ratio = max($xRatio, $yRatio);

        $scale = $ratio === 0.0 ? 1.0 : 1.0 / $ratio;

        // Center plot
        $x = $ratio === 0.0 || $xRatio < $yRatio
            ? ($extent->maxX + $extent->minX - $this->width / $scale) / 2
            : $extent->minX - ($border / $scale);
        $y = $ratio === 0.0 || $xRatio >= $yRatio
            ? ($extent->maxY + $extent->minY - $this->height / $scale) / 2
            : $extent->minY - ($border / $scale);

        return new ScaleData(scale: $scale, offsetX: $x, offsetY: $y, height: $this->height);
    }

    /**
     * Prepares and return the dataset as needed by the visualization.
     *
     * @param mixed[][]               $data     Raw data
     * @param string                  $format   Format of the visualization
     * @param ImageWrapper|TCPDF|null $renderer Image object in the case of png, TCPDF object in the case of pdf
     * @psalm-param T $format
     *
     * @psalm-return (T is 'svg' ? string : (T is 'ol' ? list<mixed[]> : null)) The exported data
     *
     * @template T of 'ol'|'pdf'|'png'|'svg'
     */
    private function prepareDataSet(
        array $data,
        string $format,
        ImageWrapper|TCPDF|null $renderer = null,
    ): array|string|null {
        $svg = '';
        $olDataset = [];
        $scaleData = $this->scaleDataSet($this->data);
        if ($scaleData !== null) {
            $colorIndex = 0;

            foreach ($data as $row) {
                // Figure out the data type
                $wkt = $row[$this->spatialColumn];
                if (! is_string($wkt)) {
                    continue;
                }

                $gisObj = GisFactory::fromWkt($wkt);
                if ($gisObj === null) {
                    continue;
                }

                $color = self::COLORS[$colorIndex];
                $label = trim((string) ($row[$this->labelColumn] ?? ''));

                if ($format === 'svg') {
                    $svg .= $gisObj->prepareRowAsSvg($wkt, htmlspecialchars($label), $color, $scaleData);
                } elseif ($format === 'png') {
                    assert($renderer instanceof ImageWrapper);
                    $gisObj->prepareRowAsPng($wkt, $label, $color, $scaleData, $renderer);
                } elseif ($format === 'pdf') {
                    assert($renderer instanceof TCPDF);
                    $gisObj->prepareRowAsPdf($wkt, $label, $color, $scaleData, $renderer);
                } elseif ($format === 'ol') {
                    $olDataset[] = $gisObj->prepareRowAsOl($wkt, (int) $row['srid'], $label, $color);
                }

                $colorIndex = ($colorIndex + 1) % count(self::COLORS);
            }
        }

        return $format === 'svg' ? $svg : ($format === 'ol' ? $olDataset : null);
    }
}
