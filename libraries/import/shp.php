<?php
/**
 * ESRI Shape file import plugin for phpMyAdmin
 *
 * @package phpMyAdmin-Import
 * @subpackage ESRI_Shape
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (isset($plugin_list)) {
    $plugin_list['shp'] = array(
        'text' => __('ESRI Shape File'),
        'extension' => 'shp',
        'options' => array(),
        'options_text' => __('Options'),
    );
} else {

    ini_set('memory_limit', '128M');
    set_time_limit(120);

    // Append the bfShapeFiles directory to the include path variable
    set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/libraries/bfShapeFiles/');
    require_once './libraries/bfShapeFiles/ShapeFile.lib.php';

    $GLOBALS['finished'] = false;
    $buffer = '';
    $eof = false;

    // Returns specified number of bytes from the buffer.
    // Buffer automatically fetch next chunk of data when the buffer falls short.
    // Sets $eof when $GLOBALS['finished'] is set and the buffer is falls short.
    function readFromBuffer($length){
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

    /**
     * This class extends from ShapeFile class to cater following phpMyAdmin specific requirements.
     * 1) To loads data from .dbf file only when the dBase extension is availble.
     * 2) To use PMA_importGetNextChunk() functionality to read data, rather than reading directly from a file.
     *    Using readFromBuffer() inplace of fread(). This makes it possible to use compressions.
     */
    class PMA_ShapeFile extends ShapeFile {

        function _isDbaseLoaded()
        {
            return extension_loaded('dbase');
        }

        function loadFromFile($FileName)
        {
            $this->_loadHeaders();
            $this->_loadRecords();
            if ($this->_isDbaseLoaded()) {
                $this->_closeDBFFile();
            }
        }

        function _loadHeaders()
        {
            readFromBuffer(24);
            $this->fileLength = loadData("N", readFromBuffer(4));

            readFromBuffer(4);
            $this->shapeType = loadData("V", readFromBuffer(4));

            $this->boundingBox = array();
            $this->boundingBox["xmin"] = loadData("d", readFromBuffer(8));
            $this->boundingBox["ymin"] = loadData("d", readFromBuffer(8));
            $this->boundingBox["xmax"] = loadData("d", readFromBuffer(8));
            $this->boundingBox["ymax"] = loadData("d", readFromBuffer(8));

            if ($this->_isDbaseLoaded() && $this->_openDBFFile()) {
                $this->DBFHeader = $this->_loadDBFHeader();
            }
        }

        function _loadRecords()
        {
            global $eof;
            readFromBuffer(32);
            while (true) {
                $record = new PMA_ShapeRecord(-1);
                $record->loadFromFile($this->SHPFile, $this->DBFFile);
                if ($record->lastError != "") {
                    return false;
                }
                if ($eof) {
                    break;
                }

                $this->records[] = $record;
            }
        }
    }

    /**
     * This class extends from ShapeRecord class to cater following phpMyAdmin specific requirements.
     * 1) To loads data from .dbf file only when the dBase extension is availble.
     * 2) To use PMA_importGetNextChunk() functionality to read data, rather than reading directly from a file.
     *    Using readFromBuffer() inplace of fread(). This makes it possible to use compressions.
     */
    class PMA_ShapeRecord extends ShapeRecord {

        function loadFromFile(&$SHPFile, &$DBFFile)
        {
            $this->DBFFile = $DBFFile;
            $this->_loadHeaders();

            switch ($this->shapeType) {
                case 0:
                    $this->_loadNullRecord();
                    break;
                case 1:
                    $this->_loadPointRecord();
                    break;
                case 3:
                    $this->_loadPolyLineRecord();
                    break;
                case 5:
                    $this->_loadPolygonRecord();
                    break;
                case 8:
                    $this->_loadMultiPointRecord();
                    break;
                default:
                    $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
                    break;
            }
            if (extension_loaded('dbase') && isset($this->DBFFile)) {
                $this->_loadDBFData();
            }
        }

        function _loadHeaders()
        {
            $this->recordNumber = loadData("N", readFromBuffer(4));
            $tmp = loadData("N", readFromBuffer(4)); //We read the length of the record
            $this->shapeType = loadData("V", readFromBuffer(4));
        }

        function _loadPoint()
        {
            $data = array();

            $data["x"] = loadData("d", readFromBuffer(8));
            $data["y"] = loadData("d", readFromBuffer(8));

            return $data;
        }

        function _loadMultiPointRecord()
        {
            $this->SHPData = array();
            $this->SHPData["xmin"] = loadData("d", readFromBuffer(8));
            $this->SHPData["ymin"] = loadData("d", readFromBuffer(8));
            $this->SHPData["xmax"] = loadData("d", readFromBuffer(8));
            $this->SHPData["ymax"] = loadData("d", readFromBuffer(8));

            $this->SHPData["numpoints"] = loadData("V", readFromBuffer(4));

            for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
                $this->SHPData["points"][] = $this->_loadPoint();
            }
        }

        function _loadPolyLineRecord()
        {
            $this->SHPData = array();
            $this->SHPData["xmin"] = loadData("d", readFromBuffer(8));
            $this->SHPData["ymin"] = loadData("d", readFromBuffer(8));
            $this->SHPData["xmax"] = loadData("d", readFromBuffer(8));
            $this->SHPData["ymax"] = loadData("d", readFromBuffer(8));

            $this->SHPData["numparts"]  = loadData("V", readFromBuffer(4));
            $this->SHPData["numpoints"] = loadData("V", readFromBuffer(4));

            for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
                $this->SHPData["parts"][$i] = loadData("V", readFromBuffer(4));
            }

            $readPoints = 0;
            reset($this->SHPData["parts"]);
            while (list($partIndex, $partData) = each($this->SHPData["parts"])) {
                if (!isset($this->SHPData["parts"][$partIndex]["points"]) || !is_array($this->SHPData["parts"][$partIndex]["points"])) {
                    $this->SHPData["parts"][$partIndex] = array();
                    $this->SHPData["parts"][$partIndex]["points"] = array();
                }
                while (!in_array($readPoints, $this->SHPData["parts"]) && ($readPoints < ($this->SHPData["numpoints"]))) {
                    $this->SHPData["parts"][$partIndex]["points"][] = $this->_loadPoint();
                    $readPoints++;
                }
            }
        }
    }

    $shp = new PMA_ShapeFile(1);
    if (extension_loaded('dbase')) {
        // If file is in UploadDir, use .dbf file in the same UploadDir to load extra data.
        if (! empty($local_import_file) && ! empty($cfg['UploadDir']) && $compression == 'none') {
            // Replace the .shp with .*, so the bsShapeFiles library correctly locates .dbf file.
            $file_name = substr($import_file, 0, strlen($import_file) - 4) . '.*';
            $shp->FileName = $file_name;
        }
    }
    $shp->loadFromFile('');

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

    require_once './libraries/gis/pma_gis_geometry.php';
    switch ($shp->shapeType) {
        // Esri Null Shape
        case 0:
            $gis_obj = null;
            break;
        // Esri Point
        case 1:
            require_once './libraries/gis/pma_gis_point.php';
            $gis_obj = PMA_GIS_Point::singleton();
            break;
        // Esri PolyLine
        case 3:
            require_once './libraries/gis/pma_gis_multilinestring.php';
            $gis_obj = PMA_GIS_Multilinestring::singleton();
            break;
        // Esri Polygon
        case 5:
            require_once './libraries/gis/pma_gis_multipolygon.php';
            $gis_obj = PMA_GIS_Multipolygon::singleton();
            break;
        // Esri MultiPoint
        case 8:
            require_once './libraries/gis/pma_gis_multipoint.php';
            $gis_obj = PMA_GIS_Multipoint::singleton();
            break;
        default:
            $error = true;
            $err = PMA_Message::error(__('MySQL Spatial Extention does not support ESRI type "%s".'));
            $param = isset($esri_types[$shp->shapeType]) ? $esri_types[$shp->shapeType] : __('Unkown Type');
            $err->addParam($param);
            PMA_mysqlDie($err->getMessage(), '', '', $err_url);
    }

    $num_rows = count($shp->records);
    // If .dbf file is loaded, the number of extra data columns
    $num_data_cols = isset($shp->DBFHeader) ? count($shp->DBFHeader) : 0;

    $rows = array();
    $col_names = array();
    if ($num_rows != 0) {
        foreach($shp->records as $record){
            $tempRow = array();
            if ($gis_obj == null) {
                $tempRow[] = null;
            } else {
                $tempRow[] = "GeomFromText('" . $gis_obj->getShape($record->SHPData) . "')";
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

    // Column names for spatial column and the rest of the columns, if they are available
    $col_names[] = 'SPATIAL';
    for ($n = 0; $n < $num_data_cols; $n++) {
        $col_names[] = $shp->DBFHeader[$n][0];
    }

    // Set table name based on the number of tables
    if (strlen($db)) {
        $result = PMA_DBI_fetch_result('SHOW TABLES');
        $table_name = 'TABLE '.(count($result) + 1);
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
    if (strlen($db)) {
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
?>
