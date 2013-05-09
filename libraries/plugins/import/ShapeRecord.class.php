<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class extends ShapeRecord class to cater the following phpMyAdmin
 * specific requirements.
 *
 * @package    PhpMyAdmin-Import
 * @subpackage ESRI_Shape
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * 1) To load data from .dbf file only when the dBase extension is available.
 * 2) To use PMA_importGetNextChunk() functionality to read data, rather than
 *    reading directly from a file. Using ImportShp::readFromBuffer() in place
 *    of fread(). This makes it possible to use compressions.
 *
 * @package    PhpMyAdmin-Import
 * @subpackage ESRI_Shape
 */
class PMA_ShapeRecord extends ShapeRecord
{
    /**
     * Loads a geometry data record from the file
     *
     * @param object &$SHPFile .shp file
     * @param object &$DBFFile .dbf file
     *
     * @return void
     * @see ShapeRecord::loadFromFile()
     */
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
            $this->setError(
                sprintf(
                    __("Geometry type '%s' is not supported by MySQL."),
                    $this->shapeType
                )
            );
            break;
        }
        if (extension_loaded('dbase') && isset($this->DBFFile)) {
            $this->_loadDBFData();
        }
    }

    /**
     * Loads metadata from the ESRI shape record header
     *
     * @return void
     * @see ShapeRecord::_loadHeaders()
     */
    function _loadHeaders()
    {
        $this->recordNumber = loadData("N", ImportShp::readFromBuffer(4));
        ImportShp::readFromBuffer(4);
        $this->shapeType = loadData("V", ImportShp::readFromBuffer(4));
    }

    /**
     * Loads data from a point record
     *
     * @return void
     * @see ShapeRecord::_loadPoint()
     */
    function _loadPoint()
    {
        $data = array();

        $data["x"] = loadData("d", ImportShp::readFromBuffer(8));
        $data["y"] = loadData("d", ImportShp::readFromBuffer(8));

        return $data;
    }

    /**
     * Loads data from a multipoint record
     *
     * @return void
     * @see ShapeRecord::_loadMultiPointRecord()
     */
    function _loadMultiPointRecord()
    {
        $this->SHPData = array();
        $this->SHPData["xmin"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->SHPData["ymin"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->SHPData["xmax"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->SHPData["ymax"] = loadData("d", ImportShp::readFromBuffer(8));

        $this->SHPData["numpoints"] = loadData("V", ImportShp::readFromBuffer(4));

        for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
            $this->SHPData["points"][] = $this->_loadPoint();
        }
    }

    /**
     * Loads data from a polyline record
     *
     * @return void
     * @see ShapeRecord::_loadPolyLineRecord()
     */
    function _loadPolyLineRecord()
    {
        $this->SHPData = array();
        $this->SHPData["xmin"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->SHPData["ymin"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->SHPData["xmax"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->SHPData["ymax"] = loadData("d", ImportShp::readFromBuffer(8));

        $this->SHPData["numparts"]  = loadData("V", ImportShp::readFromBuffer(4));
        $this->SHPData["numpoints"] = loadData("V", ImportShp::readFromBuffer(4));

        for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
            $this->SHPData["parts"][$i] = loadData(
                "V", ImportShp::readFromBuffer(4)
            );
        }

        $readPoints = 0;
        reset($this->SHPData["parts"]);
        while (list($partIndex, $partData) = each($this->SHPData["parts"])) {
            if (! isset($this->SHPData["parts"][$partIndex]["points"])
                || !is_array($this->SHPData["parts"][$partIndex]["points"])
            ) {
                $this->SHPData["parts"][$partIndex] = array();
                $this->SHPData["parts"][$partIndex]["points"] = array();
            }
            while (! in_array($readPoints, $this->SHPData["parts"])
            && ($readPoints < ($this->SHPData["numpoints"]))
            ) {
                $this->SHPData["parts"][$partIndex]["points"][]
                    = $this->_loadPoint();
                $readPoints++;
            }
        }
    }
}
?>
