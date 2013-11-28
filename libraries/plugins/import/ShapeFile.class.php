<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class extends ShapeFile class to cater the following phpMyAdmin
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
class PMA_ShapeFile extends ShapeFile
{
    /**
     * Returns whether the 'dbase' extension is loaded
     *
     * @return boolean whether the 'dbase' extension is loaded
     */
    function _isDbaseLoaded()
    {
        return extension_loaded('dbase');
    }

    /**
     * Loads ESRI shape data from the imported file
     *
     * @param string $FileName not used, it's here only to match the method
     *                         signature of the method being overidden
     *
     * @return void
     * @see ShapeFile::loadFromFile()
     */
    function loadFromFile($FileName)
    {
        $this->_loadHeaders();
        $this->_loadRecords();
        if ($this->_isDbaseLoaded()) {
            $this->_closeDBFFile();
        }
    }

    /**
     * Loads metadata from the ESRI shape file header
     *
     * @return void
     * @see ShapeFile::_loadHeaders()
     */
    function _loadHeaders()
    {
        ImportShp::readFromBuffer(24);
        $this->fileLength = loadData("N", ImportShp::readFromBuffer(4));

        ImportShp::readFromBuffer(4);
        $this->shapeType = loadData("V", ImportShp::readFromBuffer(4));

        $this->boundingBox = array();
        $this->boundingBox["xmin"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->boundingBox["ymin"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->boundingBox["xmax"] = loadData("d", ImportShp::readFromBuffer(8));
        $this->boundingBox["ymax"] = loadData("d", ImportShp::readFromBuffer(8));

        if ($this->_isDbaseLoaded() && $this->_openDBFFile()) {
            $this->DBFHeader = $this->_loadDBFHeader();
        }
    }

    /**
     * Loads geometry data from the ESRI shape file
     *
     * @return boolean|void
     * @see ShapeFile::_loadRecords()
     */
    function _loadRecords()
    {
        global $eof;
        ImportShp::readFromBuffer(32);
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
?>
