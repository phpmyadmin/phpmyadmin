<?php
/**
 * ESRI Shape file import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Gis\GisPoint;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Import\ColumnType;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Import\ImportTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\ZipExtension;
use ZipArchive;

use function __;
use function count;
use function extension_loaded;
use function file_exists;
use function file_put_contents;
use function mb_substr;
use function method_exists;
use function pathinfo;
use function strlen;
use function substr;
use function trim;
use function unlink;

use const LOCK_EX;
use const PATHINFO_FILENAME;

/**
 * Handles the import for ESRI Shape files
 */
class ImportShp extends ImportPlugin
{
    private ZipExtension|null $zipExtension = null;

    protected function init(): void
    {
        if (! extension_loaded('zip')) {
            return;
        }

        $this->zipExtension = new ZipExtension(new ZipArchive());
    }

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'shp';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('ESRI Shape File'));
        $importPluginProperties->setExtension('shp');
        $importPluginProperties->setOptionsText(__('Options'));

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @return string[]
     */
    public function doImport(File|null $importHandle = null): array
    {
        $GLOBALS['error'] ??= null;
        $GLOBALS['message'] ??= null;
        ImportSettings::$finished = false;

        if ($importHandle === null || $this->zipExtension === null) {
            return [];
        }

        /** @see ImportShp::readFromBuffer() */
        $GLOBALS['importHandle'] = $importHandle;

        $compression = $importHandle->getCompression();

        $shp = new ShapeFileImport(1);
        // If the zip archive has more than one file,
        // get the correct content to the buffer from .shp file.
        if (
            $compression === 'application/zip'
            && $this->zipExtension->getNumberOfFiles(ImportSettings::$importFile) > 1
        ) {
            if ($importHandle->openZip('/^.*\.shp$/i') === false) {
                $GLOBALS['message'] = Message::error(
                    __('There was an error importing the ESRI shape file: "%s".'),
                );
                $GLOBALS['message']->addParam($importHandle->getError());

                return [];
            }
        }

        $tempDbfFile = false;
        // We need dbase extension to handle .dbf file
        if (extension_loaded('dbase')) {
            $config = Config::getInstance();
            $temp = $config->getTempDir('shp');
            // If we can extract the zip archive to 'TempDir'
            // and use the files in it for import
            if ($compression === 'application/zip' && $temp !== null) {
                $dbfFileName = $this->zipExtension->findFile(ImportSettings::$importFile, '/^.*\.dbf$/i');
                // If the corresponding .dbf file is in the zip archive
                if ($dbfFileName !== false) {
                    // Extract the .dbf file and point to it.
                    $extracted = $this->zipExtension->extract(ImportSettings::$importFile, $dbfFileName);
                    if ($extracted !== false) {
                        // remove filename extension, e.g.
                        // dresden_osm.shp/gis.osm_transport_a_v06.dbf
                        // to
                        // dresden_osm.shp/gis.osm_transport_a_v06
                        $pathParts = pathinfo($dbfFileName);
                        $dbfFileName = $pathParts['dirname'] . '/' . $pathParts['filename'];

                        // sanitize filename
                        $dbfFileName = Sanitize::sanitizeFilename($dbfFileName, true);

                        // concat correct filename and extension
                        $dbfFilePath = $temp . '/' . $dbfFileName . '.dbf';

                        if (file_put_contents($dbfFilePath, $extracted, LOCK_EX) !== false) {
                            $tempDbfFile = true;

                            // Replace the .dbf with .*, as required by the bsShapeFiles library.
                            $shp->fileName = substr($dbfFilePath, 0, -4) . '.*';
                        }
                    }
                }
            } elseif (
                ImportSettings::$localImportFile !== ''
                && ! empty($config->settings['UploadDir'])
                && $compression === 'none'
            ) {
                // If file is in UploadDir, use .dbf file in the same UploadDir
                // to load extra data.
                // Replace the .shp with .*,
                // so the bsShapeFiles library correctly locates .dbf file.
                $shp->fileName = mb_substr(ImportSettings::$importFile, 0, -4) . '.*';
            }
        }

        // It should load data before file being deleted
        $shp->loadFromFile('');

        // Delete the .dbf file extracted to 'TempDir'
        if ($tempDbfFile && isset($dbfFilePath) && @file_exists($dbfFilePath)) {
            unlink($dbfFilePath);
        }

        if ($shp->lastError != '') {
            $GLOBALS['error'] = true;
            $GLOBALS['message'] = Message::error(
                __('There was an error importing the ESRI shape file: "%s".'),
            );
            $GLOBALS['message']->addParam($shp->lastError);

            return [];
        }

        switch ($shp->shapeType) {
            // ESRI Null Shape
            case 0:
                break;
            // ESRI Point
            case 1:
                $gisType = 'point';
                break;
            // ESRI PolyLine
            case 3:
                $gisType = 'multilinestring';
                break;
            // ESRI Polygon
            case 5:
                $gisType = 'multipolygon';
                break;
            // ESRI MultiPoint
            case 8:
                $gisType = 'multipoint';
                break;
            default:
                $GLOBALS['error'] = true;
                $GLOBALS['message'] = Message::error(
                    __('MySQL Spatial Extension does not support ESRI type "%s".'),
                );
                $GLOBALS['message']->addParam($shp->getShapeName());

                return [];
        }

        if (isset($gisType)) {
            /** @var GisMultiLineString|GisMultiPoint|GisPoint|GisPolygon $gisObj */
            $gisObj = GisFactory::fromType($gisType);
        } else {
            $gisObj = null;
        }

        // If .dbf file is loaded, the number of extra data columns
        $numDataCols = $shp->getDBFHeader() !== null ? count($shp->getDBFHeader()) : 0;

        $rows = [];
        $colNames = [];
        foreach ($shp->records as $record) {
            $tempRow = [];
            if ($gisObj == null || ! method_exists($gisObj, 'getShape')) {
                $tempRow[] = null;
            } else {
                $tempRow[] = "GeomFromText('"
                    . $gisObj->getShape($record->shpData) . "')";
            }

            if ($shp->getDBFHeader() !== null) {
                foreach ($shp->getDBFHeader() as $c) {
                    $cell = trim((string) $record->dbfData[$c[0]]);

                    if ($cell === '') {
                        $cell = 'NULL';
                    }

                    $tempRow[] = $cell;
                }
            }

            $rows[] = $tempRow;
        }

        if ($rows === []) {
            $GLOBALS['error'] = true;
            $GLOBALS['message'] = Message::error(
                __('The imported file does not contain any data!'),
            );

            return [];
        }

        // Column names for spatial column and the rest of the columns,
        // if they are available
        $colNames[] = 'SPATIAL';
        $dbfHeader = $shp->getDBFHeader();
        for ($n = 0; $n < $numDataCols; $n++) {
            if ($dbfHeader === null) {
                continue;
            }

            $colNames[] = $dbfHeader[$n][0];
        }

        // Set table name based on the number of tables
        if (Current::$database !== '') {
            $tableName = $this->import->getNextAvailableTableName(
                Current::$database,
                pathinfo(ImportSettings::$importFileName, PATHINFO_FILENAME),
            );
        } else {
            $tableName = 'TBL_NAME';
        }

        $table = new ImportTable($tableName, $colNames, $rows);

        // Use data from shape file to chose best-fit MySQL types for each column
        $analysis = $this->import->analyzeTable($table);

        $analysis[Import::TYPES][0] = ColumnType::Geometry;
        $analysis[Import::FORMATTEDSQL][0] = true;

        // Set database name to the currently selected one, if applicable
        $dbName = Current::$database !== '' ? Current::$database : 'SHP_DB';
        $createDb = Current::$database === '';

        // Created and execute necessary SQL statements from data
        $sqlStatements = [];
        if ($createDb) {
            $sqlStatements = $this->import->createDatabase($dbName, 'utf8', 'utf8_general_ci', []);
        }

        $this->import->buildSql($dbName, [$table], [$analysis], sqlData: $sqlStatements);

        ImportSettings::$finished = true;
        $GLOBALS['error'] = false;

        // Commit any possible data in buffers
        $this->import->runQuery('', $sqlStatements);

        return $sqlStatements;
    }

    /**
     * Returns specified number of bytes from the buffer.
     * Buffer automatically fetches next chunk of data when the buffer
     * falls short.
     * Sets $eof when ImportSettings::$finished is set and the buffer falls short.
     *
     * @param int $length number of bytes
     */
    public static function readFromBuffer(int $length): string
    {
        $GLOBALS['buffer'] ??= null;
        $GLOBALS['eof'] ??= null;
        $GLOBALS['importHandle'] ??= null;

        $import = new Import();

        if (strlen((string) $GLOBALS['buffer']) < $length) {
            if (ImportSettings::$finished) {
                $GLOBALS['eof'] = true;
            } else {
                $GLOBALS['buffer'] .= $import->getNextChunk($GLOBALS['importHandle']);
            }
        }

        $result = substr($GLOBALS['buffer'], 0, $length);
        $GLOBALS['buffer'] = substr($GLOBALS['buffer'], $length);

        return $result;
    }
}
