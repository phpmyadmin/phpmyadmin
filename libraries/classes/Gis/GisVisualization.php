<?php
/**
 * Handles visualization of GIS data
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Core;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;
use TCPDF;
use Webmozart\Assert\Assert;

use function count;
use function is_string;
use function mb_strlen;
use function mb_strpos;
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

    /** @var mixed[][]   Raw data for the visualization */
    private array $data;

    /** The width of the GIS visualization.*/
    private int $width;
    /** The height of the GIS visualization. */
    private int $height;

    private string $spatialColumn;

    private string|null $labelColumn;

    /** Number of rows */
    private int $rows;
    /** Start position */
    private int $pos;

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
     * @param string                        $sql_query SQL to fetch raw data for visualization
     * @param array<string,string|int|null> $options   Users specified options
     * @param int                           $rows      number of rows
     * @param int                           $pos       start position
     * @psalm-param array{
     *   spatialColumn: non-empty-string,
     *   labelColumn?: non-empty-string|null,
     *   width: int,
     *   height: int,
     * } $options
     */
    public static function get(string $sql_query, array $options, int $rows, int $pos): GisVisualization
    {
        return new GisVisualization($sql_query, $options, $rows, $pos);
    }

    /**
     * Get visualization
     *
     * @param mixed[][]                     $data    Raw data, if set, parameters other
     *                                               than $options will be ignored
     * @param array<string,string|int|null> $options Users specified options
     * @psalm-param array{
     *     spatialColumn: non-empty-string,
     *     labelColumn?: non-empty-string|null,
     *     width: int,
     *     height: int,
     * } $options
     */
    public static function getByData(array $data, array $options): GisVisualization
    {
        return new GisVisualization($data, $options);
    }

    /**
     * Check if data has SRID
     */
    public function hasSrid(): bool
    {
        foreach ($this->data as $row) {
            if ($row['srid'] != 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stores user specified options.
     *
     * @param mixed[][]|string    $sqlOrData SQL to fetch raw data for visualization
     *                                       or an array with data.
     *                                       If it is an array row and pos are ignored
     * @param array<string,mixed> $options   Users specified options
     * @param int                 $rows      number of rows
     * @param int                 $pos       start position
     */
    private function __construct(array|string $sqlOrData, array $options, int $rows = 0, int $pos = 0)
    {
        $width = $options['width'] ?? null;
        Assert::positiveInteger($width);
        $this->width = $width;

        $height = $options['height'] ?? null;
        Assert::positiveInteger($height);
        $this->height = $height;

        $spatialColumn = $options['spatialColumn'] ?? null;
        Assert::stringNotEmpty($spatialColumn);
        $this->spatialColumn = $spatialColumn;

        $labelColumn = $options['labelColumn'] ?? null;
        Assert::nullOrStringNotEmpty($labelColumn);
        $this->labelColumn = $labelColumn;

        $this->pos = $pos;
        $this->rows = $rows;

        $this->data = is_string($sqlOrData)
            ? $this->modifyQueryAndFetch($sqlOrData)
            : $sqlOrData;
    }

    /** @return mixed[][] raw data */
    private function modifyQueryAndFetch(string $sqlQuery): array
    {
        $modifiedSql = $this->modifySqlQuery($sqlQuery);

        return $this->fetchRawData($modifiedSql);
    }

    /**
     * Returns sql for fetching raw data
     *
     * @param string $sql_query The SQL to modify.
     *
     * @return string the modified sql query.
     */
    private function modifySqlQuery(string $sql_query): string
    {
        $modified_query = 'SELECT ';
        $spatialAsText = 'ASTEXT';
        $spatialSrid = 'SRID';
        $axisOrder = '';

        $mysqlVersion = $GLOBALS['dbi']->getVersion();
        $isMariaDB = $GLOBALS['dbi']->isMariaDB();

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
            $modified_query .= Util::backquote($this->labelColumn)
            . ', ';
        }

        // Wrap the spatial column with 'ST_ASTEXT()' function and add it
        $modified_query .= $spatialAsText . '('
            . Util::backquote($this->spatialColumn)
            . $axisOrder . ') AS ' . Util::backquote($this->spatialColumn)
            . ', ';

        // Get the SRID
        $modified_query .= $spatialSrid . '('
            . Util::backquote($this->spatialColumn)
            . ') AS ' . Util::backquote('srid') . ' ';

        // Append the original query as the inner query
        $modified_query .= 'FROM (' . rtrim($sql_query, ';') . ') AS '
            . Util::backquote('temp_gis');

        // LIMIT clause
        if ($this->rows > 0) {
            $modified_query .= ' LIMIT ' . ($this->pos > 0 ? $this->pos . ', ' : '') . $this->rows;
        }

        return $modified_query;
    }

    /**
     * Returns raw data for GIS visualization.
     *
     * @return mixed[][] the raw data.
     */
    private function fetchRawData(string $modifiedSql): array
    {
        $modified_result = $GLOBALS['dbi']->tryQuery($modifiedSql);

        if ($modified_result === false) {
            return [];
        }

        return $modified_result->fetchAllAssoc();
    }

    /**
     * Sanitizes the file name.
     *
     * @param string $file_name file name
     * @param string $ext       extension of the file
     *
     * @return string the sanitized file name
     */
    private function sanitizeName(string $file_name, string $ext): string
    {
        $file_name = Sanitize::sanitizeFilename($file_name);

        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $required_extension = '.' . $ext;
        $extension_length = mb_strlen($required_extension);
        $user_extension = mb_substr($file_name, -$extension_length);
        if (mb_strtolower($user_extension) !== $required_extension) {
            $file_name .= $required_extension;
        }

        return $file_name;
    }

    /**
     * Handles common tasks of writing the visualization to file for various formats.
     *
     * @param string $file_name file name
     * @param string $type      mime type
     * @param string $ext       extension of the file
     */
    private function writeToFile(string $file_name, string $type, string $ext): void
    {
        $file_name = $this->sanitizeName($file_name, $ext);
        Core::downloadHeader($file_name, $type);
    }

    /**
     * Generate the visualization in SVG format.
     *
     * @return string the generated image resource
     */
    private function svg(): string
    {
        $scale_data = $this->scaleDataSet($this->data);
        /** @var string $svg */
        $svg = $this->prepareDataSet($this->data, $scale_data, 'svg');

        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'
            . "\n"
            . '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg"'
            . ' xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . $this->width . '"'
            . ' height="' . $this->height . '">'
            . '<g id="groupPanel">' . $svg . '</g>'
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
     * @param string $file_name File name
     */
    public function toFileAsSvg(string $file_name): void
    {
        $img = $this->svg();
        $this->writeToFile($file_name, 'image/svg+xml', 'svg');
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

        $scale_data = $this->scaleDataSet($this->data);
        /** @var ImageWrapper $image */
        $image = $this->prepareDataSet($this->data, $scale_data, 'png', $image);

        return $image;
    }

    /**
     * Saves as a PNG image to a file.
     *
     * @param string $file_name File name
     */
    public function toFileAsPng(string $file_name): void
    {
        $image = $this->png();
        if ($image === null) {
            return;
        }

        $this->writeToFile($file_name, 'image/png', 'png');
        $image->png(null, 9, PNG_ALL_FILTERS);
    }

    /**
     * Get the code for visualization with OpenLayers.
     *
     * @return string the code for visualization with OpenLayers
     *
     * @todo Should return JSON to avoid eval() in gis_data_editor.js
     */
    public function asOl(): string
    {
        $scale_data = $this->scaleDataSet($this->data);
        /** @var string $olCode */
        $olCode = $this->prepareDataSet($this->data, $scale_data, 'ol');

        return 'function drawOpenLayers() {'
            . 'if (typeof ol === "undefined") { return undefined; }'
            . 'var olCss = "js/vendor/openlayers/theme/ol.css";'
            . '$(\'head\').append(\'<link rel="stylesheet" type="text/css" href=\'+olCss+\'>\');'
            . 'var vectorSource = new ol.source.Vector({});'
            . 'var map = new ol.Map({'
            . 'target: \'openlayersmap\','
            . 'layers: ['
            . 'new ol.layer.Tile({'
            . 'source: new ol.source.OSM()'
            . '}),'
            . 'new ol.layer.Vector({'
            . 'source: vectorSource'
            . '})'
            . '],'
            . 'view: new ol.View({'
            . 'center: [0, 0],'
            . 'zoom: 4'
            . '}),'
            . 'controls: [new ol.control.MousePosition({'
            . 'coordinateFormat: ol.coordinate.createStringXY(4),'
            . 'projection: \'EPSG:4326\'}),'
            . 'new ol.control.Zoom,'
            . 'new ol.control.Attribution]'
            . '});'
            . $olCode
            . 'var extent = vectorSource.getExtent();'
            . 'if (!ol.extent.isEmpty(extent)) {'
            . 'map.getView().fit(extent, {padding: [20, 20, 20, 20]});'
            . '}'
            . 'return map;'
            . '}';
    }

    /**
     * Saves as a PDF to a file.
     *
     * @param string $file_name File name
     */
    public function toFileAsPdf(string $file_name): void
    {
        // create pdf
        $pdf = new TCPDF('', 'pt', $GLOBALS['cfg']['PDFDefaultPageSize'], true, 'UTF-8', false);

        // disable header and footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        //set auto page breaks
        $pdf->setAutoPageBreak(false);

        // add a page
        $pdf->AddPage();

        $scale_data = $this->scaleDataSet($this->data);
        $pdf = $this->prepareDataSet($this->data, $scale_data, 'pdf', $pdf);

        // sanitize file name
        $file_name = $this->sanitizeName($file_name, 'pdf');
        $pdf->Output($file_name, 'D');
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
     *
     * @return array an array containing the scale, x and y offsets
     */
    private function scaleDataSet(array $data): array
    {
        $min_max = null;
        $border = 15;
        // effective width and height of the plot
        $plot_width = $this->width - 2 * $border;
        $plot_height = $this->height - 2 * $border;

        foreach ($data as $row) {
            // Figure out the data type
            $ref_data = $row[$this->spatialColumn];
            if (! is_string($ref_data)) {
                continue;
            }

            $type_pos = mb_strpos($ref_data, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($ref_data, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $scaleData = $gis_obj->scaleRow($ref_data);

            // Update minimum/maximum values for x and y coordinates.
            $min_max = $min_max === null ? $scaleData : $scaleData?->merge($min_max);
        }

        $min_max ??= new ScaleData(0, 0, 0, 0);

        // scale the visualization
        $x_ratio = ($min_max->maxX - $min_max->minX) / $plot_width;
        $y_ratio = ($min_max->maxY - $min_max->minY) / $plot_height;
        $ratio = $x_ratio > $y_ratio ? $x_ratio : $y_ratio;

        $scale = $ratio != 0 ? 1 / $ratio : 1;

        // Center plot
        $x = $ratio == 0 || $x_ratio < $y_ratio
            ? ($min_max->maxX + $min_max->minX - $this->width / $scale) / 2
            : $min_max->minX - ($border / $scale);
        $y = $ratio == 0 || $x_ratio >= $y_ratio
            ? ($min_max->maxY + $min_max->minY - $this->height / $scale) / 2
            : $min_max->minY - ($border / $scale);

        return [
            'scale' => $scale,
            'x' => $x,
            'y' => $y,
            'height' => $this->height,
        ];
    }

    /**
     * Prepares and return the dataset as needed by the visualization.
     *
     * @param mixed[][]                 $data       Raw data
     * @param array                     $scale_data Data related to scaling
     * @param string                    $format     Format of the visualization
     * @param ImageWrapper|TCPDF|string $results    Image object in the case of png
     *                                              TCPDF object in the case of pdf
     *
     * @return mixed the formatted array of data
     */
    private function prepareDataSet(
        array $data,
        array $scale_data,
        string $format,
        ImageWrapper|TCPDF|string $results = '',
    ): mixed {
        $color_index = 0;

        // loop through the rows
        foreach ($data as $row) {
            // Figure out the data type
            $ref_data = $row[$this->spatialColumn];
            if (! is_string($ref_data)) {
                continue;
            }

            $type_pos = mb_strpos($ref_data, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($ref_data, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $color = self::COLORS[$color_index];
            $label = trim((string) ($row[$this->labelColumn] ?? ''));

            if ($format === 'svg') {
                $results .= $gis_obj->prepareRowAsSvg(
                    $ref_data,
                    $label,
                    $color,
                    $scale_data,
                );
            } elseif ($format === 'png') {
                $results = $gis_obj->prepareRowAsPng(
                    $ref_data,
                    $label,
                    $color,
                    $scale_data,
                    $results,
                );
            } elseif ($format === 'pdf' && $results instanceof TCPDF) {
                $results = $gis_obj->prepareRowAsPdf(
                    $ref_data,
                    $label,
                    $color,
                    $scale_data,
                    $results,
                );
            } elseif ($format === 'ol') {
                $results .= $gis_obj->prepareRowAsOl(
                    $ref_data,
                    (int) $row['srid'],
                    $label,
                    $color,
                );
            }

            $color_index = ($color_index + 1) % count(self::COLORS);
        }

        return $results;
    }
}
