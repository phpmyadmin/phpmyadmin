<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * ESRI Shape file import plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage ESRI_Shape
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// Drizzle does not support GIS data types
if (PMA_DRIZZLE) {
    $GLOBALS['skip_import'] = true;
    return;
}

/* Get the import interface*/
require_once 'libraries/plugins/ImportPlugin.class.php';
/* Get the ShapeFile class */
require_once 'libraries/bfShapeFiles/ShapeFile.lib.php';
require_once 'libraries/plugins/import/ShapeFile.class.php';
require_once 'libraries/plugins/import/ShapeRecord.class.php';

/**
 * Handles the import for ESRI Shape files
 *
 * @package    PhpMyAdmin-Import
 * @subpackage ESRI_Shape
 */
class ImportShp extends ImportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ImportPluginProperties.class.php";

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('ESRI Shape File'));
        $importPluginProperties->setExtension('shp');
        $importPluginProperties->setOptions(array());
        $importPluginProperties->setOptionsText(__('Options'));

        $this->properties = $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    public function doImport()
    {
        global $db, $error, $finished, $compression,
            $import_file, $local_import_file, $message;

        $GLOBALS['finished'] = false;

        $shp = new PMA_ShapeFile(1);
        // If the zip archive has more than one file,
        // get the correct content to the buffer from .shp file.
        if ($compression == 'application/zip'
            && PMA_getNoOfFilesInZip($import_file) > 1
        ) {
            $zip_content =  PMA_getZipContents($import_file, '/^.*\.shp$/i');
            $GLOBALS['import_text'] = $zip_content['data'];
        }

        $temp_dbf_file = false;
        // We need dbase extension to handle .dbf file
        if (extension_loaded('dbase')) {
            // If we can extract the zip archive to 'TempDir'
            // and use the files in it for import
            if ($compression == 'application/zip'
                && ! empty($GLOBALS['cfg']['TempDir'])
                && is_writable($GLOBALS['cfg']['TempDir'])
            ) {
                $dbf_file_name = PMA_findFileFromZipArchive(
                    '/^.*\.dbf$/i', $import_file
                );
                // If the corresponding .dbf file is in the zip archive
                if ($dbf_file_name) {
                    // Extract the .dbf file and point to it.
                    $extracted =  PMA_zipExtract(
                        $import_file,
                        realpath($GLOBALS['cfg']['TempDir']),
                        array($dbf_file_name)
                    );
                    if ($extracted) {
                        $dbf_file_path = realpath($GLOBALS['cfg']['TempDir'])
                            . (PMA_IS_WINDOWS ? '\\' : '/') . $dbf_file_name;
                        $temp_dbf_file = true;
                        // Replace the .dbf with .*, as required
                        // by the bsShapeFiles library.
                        $file_name = /*overload*/mb_substr(
                            $dbf_file_path,
                            0,
                            /*overload*/mb_strlen($dbf_file_path) - 4
                        ) . '.*';
                        $shp->FileName = $file_name;
                    }
                }
            } elseif (! empty($local_import_file)
                && ! empty($GLOBALS['cfg']['UploadDir'])
                && $compression == 'none'
            ) {
                // If file is in UploadDir, use .dbf file in the same UploadDir
                // to load extra data.
                // Replace the .shp with .*,
                // so the bsShapeFiles library correctly locates .dbf file.
                $file_name = /*overload*/mb_substr(
                    $import_file,
                    0,
                    /*overload*/mb_strlen($import_file) - 4
                ) . '.*';
                $shp->FileName = $file_name;
            }
        }

        // Load data
        $shp->loadFromFile('');
        if ($shp->lastError != "") {
            $error = true;
            $message = PMA_Message::error(
                __('There was an error importing the ESRI shape file: "%s".')
            );
            $message->addParam($shp->lastError);
            return;
        }

        // Delete the .dbf file extracted to 'TempDir'
        if ($temp_dbf_file
            && isset($dbf_file_path)
            && file_exists($dbf_file_path)
        ) {
            unlink($dbf_file_path);
        }

        $esri_types = array(
            0  => 'Null Shape',
            1  => 'Point',
            3  => 'PolyLine',
            5  => 'Polygon',
            8  => 'MultiPoint',
            11 => 'PointZ',
            13 => 'PolyLineZ',
            15 => 'PolygonZ',
            18 => 'MultiPointZ',
            21 => 'PointM',
            23 => 'PolyLineM',
            25 => 'PolygonM',
            28 => 'MultiPointM',
            31 => 'MultiPatch',
        );

        switch ($shp->shapeType) {
        // ESRI Null Shape
        case 0:
            break;
        // ESRI Point
        case 1:
            $gis_type = 'point';
            break;
        // ESRI PolyLine
        case 3:
            $gis_type = 'multilinestring';
            break;
        // ESRI Polygon
        case 5:
            $gis_type = 'multipolygon';
            break;
        // ESRI MultiPoint
        case 8:
            $gis_type = 'multipoint';
            break;
        default:
            $error = true;
            if (! isset($esri_types[$shp->shapeType])) {
                $message = PMA_Message::error(
                    __(
                        'You tried to import an invalid file or the imported file'
                        . ' contains invalid data!'
                    )
                );
            } else {
                $message = PMA_Message::error(
                    __('MySQL Spatial Extension does not support ESRI type "%s".')
                );
                $message->addParam($esri_types[$shp->shapeType]);
            }
            return;
        }

        if (isset($gis_type)) {
            include_once './libraries/gis/GIS_Factory.class.php';
            $gis_obj =  PMA_GIS_Factory::factory($gis_type);
        } else {
            $gis_obj = null;
        }

        $num_rows = count($shp->records);
        // If .dbf file is loaded, the number of extra data columns
        $num_data_cols = isset($shp->DBFHeader) ? count($shp->DBFHeader) : 0;

        $rows = array();
        $col_names = array();
        if ($num_rows != 0) {
            foreach ($shp->records as $record) {
                $tempRow = array();
                if ($gis_obj == null) {
                    $tempRow[] = null;
                } else {
                    $tempRow[] = "GeomFromText('"
                        . $gis_obj->getShape($record->SHPData) . "')";
                }

                if (isset($shp->DBFHeader)) {
                    foreach ($shp->DBFHeader as $c) {
                        $cell = trim($record->DBFData[$c[0]]);

                        if (! strcmp($cell, '')) {
                            $cell = 'NULL';
                        }

                        $tempRow[] = $cell;
                    }
                }
                $rows[] = $tempRow;
            }
        }

        if (count($rows) == 0) {
            $error = true;
            $message = PMA_Message::error(
                __('The imported file does not contain any data!')
            );
            return;
        }

        // Column names for spatial column and the rest of the columns,
        // if they are available
        $col_names[] = 'SPATIAL';
        for ($n = 0; $n < $num_data_cols; $n++) {
            $col_names[] = $shp->DBFHeader[$n][0];
        }

        // Set table name based on the number of tables
        if (/*overload*/mb_strlen($db)) {
            $result = $GLOBALS['dbi']->fetchResult('SHOW TABLES');
            $table_name = 'TABLE ' . (count($result) + 1);
        } else {
            $table_name = 'TBL_NAME';
        }
        $tables = array(array($table_name, $col_names, $rows));

        // Use data from shape file to chose best-fit MySQL types for each column
        $analyses = array();
        $analyses[] = PMA_analyzeTable($tables[0]);

        $table_no = 0; $spatial_col = 0;
        $analyses[$table_no][TYPES][$spatial_col] = GEOMETRY;
        $analyses[$table_no][FORMATTEDSQL][$spatial_col] = true;

        // Set database name to the currently selected one, if applicable
        if (/*overload*/mb_strlen($db)) {
            $db_name = $db;
            $options = array('create_db' => false);
        } else {
            $db_name = 'SHP_DB';
            $options = null;
        }

        // Created and execute necessary SQL statements from data
        $null_param = null;
        PMA_buildSQL($db_name, $tables, $analyses, $null_param, $options);

        unset($tables);
        unset($analyses);

        $finished = true;
        $error = false;

        // Commit any possible data in buffers
        PMA_importRunQuery();
    }

    /**
     * Returns specified number of bytes from the buffer.
     * Buffer automatically fetches next chunk of data when the buffer
     * falls short.
     * Sets $eof when $GLOBALS['finished'] is set and the buffer falls short.
     *
     * @param int $length number of bytes
     *
     * @return string
     */
    public static function readFromBuffer($length)
    {
        global $buffer, $eof;

        if (strlen($buffer) < $length) {
            if ($GLOBALS['finished']) {
                $eof = true;
            } else {
                $buffer .= PMA_importGetNextChunk();
            }
        }
        $result = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length);
        return $result;
    }
}
