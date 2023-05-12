<?php
/**
 * ESRI Shape file import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Gis\GisPoint;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Import;
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
use function strcmp;
use function strlen;
use function substr;
use function trim;
use function unlink;

use const LOCK_EX;

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
        $GLOBALS['import_file'] ??= null;
        $GLOBALS['local_import_file'] ??= null;
        $GLOBALS['message'] ??= null;
        $GLOBALS['finished'] = false;

        if ($importHandle === null || $this->zipExtension === null) {
            return [];
        }

        /** @see ImportShp::readFromBuffer() */
        $GLOBALS['importHandle'] = $importHandle;

        $compression = $importHandle->getCompression();

        $shp = new ShapeFileImport(1);
        // If the zip archive has more than one file,
        // get the correct content to the buffer from .shp file.
        if ($compression === 'application/zip' && $this->zipExtension->getNumberOfFiles($GLOBALS['import_file']) > 1) {
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
            $temp = $GLOBALS['config']->getTempDir('shp');
            // If we can extract the zip archive to 'TempDir'
            // and use the files in it for import
            if ($compression === 'application/zip' && $temp !== null) {
                $dbfFileName = $this->zipExtension->findFile($GLOBALS['import_file'], '/^.*\.dbf$/i');
                // If the corresponding .dbf file is in the zip archive
                if ($dbfFileName) {
                    // Extract the .dbf file and point to it.
                    $extracted = $this->zipExtension->extract($GLOBALS['import_file'], $dbfFileName);
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
                ! empty($GLOBALS['local_import_file'])
                && ! empty($GLOBALS['cfg']['UploadDir'])
                && $compression === 'none'
            ) {
                // If file is in UploadDir, use .dbf file in the same UploadDir
                // to load extra data.
                // Replace the .shp with .*,
                // so the bsShapeFiles library correctly locates .dbf file.
                $shp->fileName = mb_substr($GLOBALS['import_file'], 0, -4) . '.*';
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
            $gisObj = GisFactory::factory($gisType);
        } else {
            $gisObj = null;
        }

        $numRows = count($shp->records);
        // If .dbf file is loaded, the number of extra data columns
        $numDataCols = $shp->getDBFHeader() !== null ? count($shp->getDBFHeader()) : 0;

        $rows = [];
        $colNames = [];
        if ($numRows != 0) {
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

                        if (! strcmp($cell, '')) {
                            $cell = 'NULL';
                        }

                        $tempRow[] = $cell;
                    }
                }

                $rows[] = $tempRow;
            }
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
        if (strlen((string) $GLOBALS['db']) > 0) {
            $result = $GLOBALS['dbi']->fetchResult('SHOW TABLES');
            $tableName = 'TABLE ' . (count($result) + 1);
        } else {
            $tableName = 'TBL_NAME';
        }

        $tables = [[$tableName, $colNames, $rows]];

        // Use data from shape file to chose best-fit MySQL types for each column
        $analyses = [];
        $analyses[] = $this->import->analyzeTable($tables[0]);

        $tableNo = 0;
        $spatialCol = 0;
        $analyses[$tableNo][Import::TYPES][$spatialCol] = Import::GEOMETRY;
        $analyses[$tableNo][Import::FORMATTEDSQL][$spatialCol] = true;

        // Set database name to the currently selected one, if applicable
        $dbName = $GLOBALS['db'] !== '' ? $GLOBALS['db'] : 'SHP_DB';
        $createDb = $GLOBALS['db'] === '';

        // Created and execute necessary SQL statements from data
        $sqlStatements = [];
        $this->import->buildSql($dbName, $tables, $analyses, createDb:$createDb, sqlData:$sqlStatements);

        $GLOBALS['finished'] = true;
        $GLOBALS['error'] = false;

        // Commit any possible data in buffers
        $this->import->runQuery('', $sqlStatements);

        return $sqlStatements;
    }

    /**
     * Returns specified number of bytes from the buffer.
     * Buffer automatically fetches next chunk of data when the buffer
     * falls short.
     * Sets $eof when $GLOBALS['finished'] is set and the buffer falls short.
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
            if ($GLOBALS['finished']) {
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
