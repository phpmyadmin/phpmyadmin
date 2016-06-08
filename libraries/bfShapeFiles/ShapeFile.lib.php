<?php
/**
 * BytesFall ShapeFiles library
 *
 * The library implements the 2D variants of the ShapeFile format as defined in
 * http://www.esri.com/library/whitepapers/pdfs/shapefile.pdf.
 * The library currently supports reading and editing of ShapeFiles and the
 * Associated information (DBF file).
 *
 * @package bfShapeFiles
 * @version 0.0.2
 * @link http://bfshapefiles.sourceforge.net/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2-or-later
 *
 * Copyright 2006-2007 Ovidio <ovidio AT users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, you can download one from
 * http://www.gnu.org/copyleft/gpl.html.
 *
 */
  function loadData($type, $data) {
    if (!$data) return $data;
    $tmp = unpack($type, $data);
    return current($tmp);
  }

  function swap($binValue) {
    $result = $binValue{strlen($binValue) - 1};
    for($i = strlen($binValue) - 2; $i >= 0 ; $i--) {
      $result .= $binValue{$i};
    }

    return $result;
  }

  function packDouble($value, $mode = 'LE') {
    $value = (double)$value;
    $bin = pack("d", $value);

    //We test if the conversion of an integer (1) is done as LE or BE by default
    switch (pack ('L', 1)) {
      case pack ('V', 1): //Little Endian
        $result = ($mode == 'LE') ? $bin : swap($bin);
      break;
      case pack ('N', 1): //Big Endian
        $result = ($mode == 'BE') ? $bin : swap($bin);
      break;
      default: //Some other thing, we just return false
        $result = FALSE;
    }

    return $result;
  }

/**
 * ShapeFile class
 *
 * @package bfShapeFiles
 */
  class ShapeFile {
    var $FileName;

    var $SHPFile;
    var $SHXFile;
    var $DBFFile;

    var $DBFHeader;

    var $lastError = "";

    var $boundingBox = array("xmin" => 0.0, "ymin" => 0.0, "xmax" => 0.0, "ymax" => 0.0);
    var $fileLength = 0;
    var $shapeType = 0;

    var $records;

    public function __construct($shapeType, $boundingBox = array("xmin" => 0.0, "ymin" => 0.0, "xmax" => 0.0, "ymax" => 0.0), $FileName = NULL) {
      $this->shapeType = $shapeType;
      $this->boundingBox = $boundingBox;
      $this->FileName = $FileName;
      $this->fileLength = 50; // The value for file length is the total length of the file in 16-bit words (including the fifty 16-bit words that make up the header).
    }

    function loadFromFile($FileName) {
      $this->FileName = $FileName;

      if (($this->_openSHPFile()) && ($this->_openDBFFile())) {
        $this->_loadHeaders();
        $this->_loadRecords();
        $this->_closeSHPFile();
        $this->_closeDBFFile();
      } else {
        return false;
      }
    }

    function saveToFile($FileName = NULL) {
      if ($FileName != NULL) $this->FileName = $FileName;

      if (($this->_openSHPFile(TRUE)) && ($this->_openSHXFile(TRUE)) && ($this->_openDBFFile(TRUE))) {
        $this->_saveHeaders();
        $this->_saveRecords();
        $this->_closeSHPFile();
        $this->_closeSHXFile();
        $this->_closeDBFFile();
      } else {
        return false;
      }
    }

    function addRecord($record) {
      if ((isset($this->DBFHeader)) && (is_array($this->DBFHeader))) {
        $record->updateDBFInfo($this->DBFHeader);
      }

      $this->fileLength += ($record->getContentLength() + 4);
      $this->records[] = $record;
      $this->records[count($this->records) - 1]->recordNumber = count($this->records);

      if ($this->boundingBox["xmin"]==0.0 || ($this->boundingBox["xmin"]>$record->SHPData["xmin"])) $this->boundingBox["xmin"] = $record->SHPData["xmin"];
      if ($this->boundingBox["xmax"]==0.0 || ($this->boundingBox["xmax"]<$record->SHPData["xmax"])) $this->boundingBox["xmax"] = $record->SHPData["xmax"];

      if ($this->boundingBox["ymin"]==0.0 || ($this->boundingBox["ymin"]>$record->SHPData["ymin"])) $this->boundingBox["ymin"] = $record->SHPData["ymin"];
      if ($this->boundingBox["ymax"]==0.0 || ($this->boundingBox["ymax"]<$record->SHPData["ymax"])) $this->boundingBox["ymax"] = $record->SHPData["ymax"];

      if (in_array($this->shapeType,array(11,13,15,18,21,23,25,28))) {
        if (!isset($this->boundingBox["mmin"]) || $this->boundingBox["mmin"]==0.0 || ($this->boundingBox["mmin"]>$record->SHPData["mmin"])) $this->boundingBox["mmin"] = $record->SHPData["mmin"];
        if (!isset($this->boundingBox["mmax"]) || $this->boundingBox["mmax"]==0.0 || ($this->boundingBox["mmax"]<$record->SHPData["mmax"])) $this->boundingBox["mmax"] = $record->SHPData["mmax"];
      }

      if (in_array($this->shapeType,array(11,13,15,18))) {
        if (!isset($this->boundingBox["zmin"]) || $this->boundingBox["zmin"]==0.0 || ($this->boundingBox["zmin"]>$record->SHPData["zmin"])) $this->boundingBox["zmin"] = $record->SHPData["zmin"];
        if (!isset($this->boundingBox["zmax"]) || $this->boundingBox["zmax"]==0.0 || ($this->boundingBox["zmax"]<$record->SHPData["zmax"])) $this->boundingBox["zmax"] = $record->SHPData["zmax"];
      }

      return (count($this->records) - 1);
    }

    function deleteRecord($index) {
      if (isset($this->records[$index])) {
        $this->fileLength -= ($this->records[$index]->getContentLength() + 4);
        for ($i = $index; $i < (count($this->records) - 1); $i++) {
          $this->records[$i] = $this->records[$i + 1];
        }
        unset($this->records[count($this->records) - 1]);
        $this->_deleteRecordFromDBF($index);
      }
    }

    function getDBFHeader() {
      return $this->DBFHeader;
    }

    function setDBFHeader($header) {
      $this->DBFHeader = $header;

      for ($i = 0; $i < count($this->records); $i++) {
        $this->records[$i]->updateDBFInfo($header);
      }
    }

    function getIndexFromDBFData($field, $value) {
      $result = -1;
      for ($i = 0; $i < (count($this->records) - 1); $i++) {
        if (isset($this->records[$i]->DBFData[$field]) && (strtoupper($this->records[$i]->DBFData[$field]) == strtoupper($value))) {
          $result = $i;
        }
      }

      return $result;
    }

    function _loadDBFHeader() {
      $DBFFile = fopen(str_replace('.*', '.dbf', $this->FileName), 'r');

      $result = array();
      $buff32 = array();
      $i = 1;
      $inHeader = true;

      while ($inHeader) {
        if (!feof($DBFFile)) {
          $buff32 = fread($DBFFile, 32);
          if ($i > 1) {
            if (substr($buff32, 0, 1) == chr(13)) {
              $inHeader = false;
            } else {
              $pos = strpos(substr($buff32, 0, 10), chr(0));
              $pos = ($pos == 0 ? 10 : $pos);

              $fieldName = substr($buff32, 0, $pos);
              $fieldType = substr($buff32, 11, 1);
              $fieldLen = ord(substr($buff32, 16, 1));
              $fieldDec = ord(substr($buff32, 17, 1));

              array_push($result, array($fieldName, $fieldType, $fieldLen, $fieldDec));
            }
          }
          $i++;
        } else {
          $inHeader = false;
        }
      }

      fclose($DBFFile);
      return($result);
    }

    function _deleteRecordFromDBF($index) {
      if (@dbase_delete_record($this->DBFFile, $index)) {
        @dbase_pack($this->DBFFile);
      }
    }

    function _loadHeaders() {
      fseek($this->SHPFile, 24, SEEK_SET);
      $this->fileLength = loadData("N", fread($this->SHPFile, 4));

      fseek($this->SHPFile, 32, SEEK_SET);
      $this->shapeType = loadData("V", fread($this->SHPFile, 4));

      $this->boundingBox = array();
      $this->boundingBox["xmin"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["ymin"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["xmax"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["ymax"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["zmin"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["zmax"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["mmin"] = loadData("d", fread($this->SHPFile, 8));
      $this->boundingBox["mmax"] = loadData("d", fread($this->SHPFile, 8));

      $this->DBFHeader = $this->_loadDBFHeader();
    }

    function _saveHeaders() {
      fwrite($this->SHPFile, pack("NNNNNN", 9994, 0, 0, 0, 0, 0));
      fwrite($this->SHPFile, pack("N", $this->fileLength));
      fwrite($this->SHPFile, pack("V", 1000));
      fwrite($this->SHPFile, pack("V", $this->shapeType));
      fwrite($this->SHPFile, packDouble($this->boundingBox['xmin']));
      fwrite($this->SHPFile, packDouble($this->boundingBox['ymin']));
      fwrite($this->SHPFile, packDouble($this->boundingBox['xmax']));
      fwrite($this->SHPFile, packDouble($this->boundingBox['ymax']));
      fwrite($this->SHPFile, packDouble(isset($this->boundingBox['zmin'])?$this->boundingBox['zmin']:0));
      fwrite($this->SHPFile, packDouble(isset($this->boundingBox['zmax'])?$this->boundingBox['zmax']:0));
      fwrite($this->SHPFile, packDouble(isset($this->boundingBox['mmin'])?$this->boundingBox['mmin']:0));
      fwrite($this->SHPFile, packDouble(isset($this->boundingBox['mmax'])?$this->boundingBox['mmax']:0));

      fwrite($this->SHXFile, pack("NNNNNN", 9994, 0, 0, 0, 0, 0));
      fwrite($this->SHXFile, pack("N", 50 + 4*count($this->records)));
      fwrite($this->SHXFile, pack("V", 1000));
      fwrite($this->SHXFile, pack("V", $this->shapeType));
      fwrite($this->SHXFile, packDouble($this->boundingBox['xmin']));
      fwrite($this->SHXFile, packDouble($this->boundingBox['ymin']));
      fwrite($this->SHXFile, packDouble($this->boundingBox['xmax']));
      fwrite($this->SHXFile, packDouble($this->boundingBox['ymax']));
      fwrite($this->SHXFile, packDouble(isset($this->boundingBox['zmin'])?$this->boundingBox['zmin']:0));
      fwrite($this->SHXFile, packDouble(isset($this->boundingBox['zmax'])?$this->boundingBox['zmax']:0));
      fwrite($this->SHXFile, packDouble(isset($this->boundingBox['mmin'])?$this->boundingBox['mmin']:0));
      fwrite($this->SHXFile, packDouble(isset($this->boundingBox['mmax'])?$this->boundingBox['mmax']:0));
    }

    function _loadRecords() {
      fseek($this->SHPFile, 100);
      while (!feof($this->SHPFile)) {
        $bByte = ftell($this->SHPFile);
        $record = new ShapeRecord(-1);
        $record->loadFromFile($this->SHPFile, $this->DBFFile);
        $eByte = ftell($this->SHPFile);
        if (($eByte <= $bByte) || ($record->lastError != "")) {
          return false;
        }

        $this->records[] = $record;
      }
    }

    function _saveRecords() {
      if (file_exists(str_replace('.*', '.dbf', $this->FileName))) {
        @unlink(str_replace('.*', '.dbf', $this->FileName));
      }
      if (!($this->DBFFile = @dbase_create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader))) {
        return $this->setError(sprintf("It wasn't possible to create the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
      }

      $offset = 50;
      if (is_array($this->records) && (count($this->records) > 0)) {
        reset($this->records);
        while (list($index, $record) = each($this->records)) {
          //Save the record to the .shp file
          $record->saveToFile($this->SHPFile, $this->DBFFile, $index + 1);

          //Save the record to the .shx file
          fwrite($this->SHXFile, pack("N", $offset));
          fwrite($this->SHXFile, pack("N", $record->getContentLength()));
          $offset += (4 + $record->getContentLength());
        }
      }
      @dbase_pack($this->DBFFile);
    }

    function _openSHPFile($toWrite = false) {
      $this->SHPFile = @fopen(str_replace('.*', '.shp', $this->FileName), ($toWrite ? "wb+" : "rb"));
      if (!$this->SHPFile) {
        return $this->setError(sprintf("It wasn't possible to open the Shape file '%s'", str_replace('.*', '.shp', $this->FileName)));
      }

      return TRUE;
    }

    function _closeSHPFile() {
      if ($this->SHPFile) {
        fclose($this->SHPFile);
        $this->SHPFile = NULL;
      }
    }

    function _openSHXFile($toWrite = false) {
      $this->SHXFile = @fopen(str_replace('.*', '.shx', $this->FileName), ($toWrite ? "wb+" : "rb"));
      if (!$this->SHXFile) {
        return $this->setError(sprintf("It wasn't possible to open the Index file '%s'", str_replace('.*', '.shx', $this->FileName)));
      }

      return TRUE;
    }

    function _closeSHXFile() {
      if ($this->SHXFile) {
        fclose($this->SHXFile);
        $this->SHXFile = NULL;
      }
    }

    function _openDBFFile($toWrite = false) {
      $checkFunction = $toWrite ? "is_writable" : "is_readable";
      if (($toWrite) && (!file_exists(str_replace('.*', '.dbf', $this->FileName)))) {
        if (!@dbase_create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader)) {
          return $this->setError(sprintf("It wasn't possible to create the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
        }
      }
      if ($checkFunction(str_replace('.*', '.dbf', $this->FileName))) {
        $this->DBFFile = dbase_open(str_replace('.*', '.dbf', $this->FileName), ($toWrite ? 2 : 0));
        if (!$this->DBFFile) {
          return $this->setError(sprintf("It wasn't possible to open the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
        }
      } else {
        return $this->setError(sprintf("It wasn't possible to find the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
      }
      return TRUE;
    }

    function _closeDBFFile() {
      if ($this->DBFFile) {
        dbase_close($this->DBFFile);
        $this->DBFFile = NULL;
      }
    }

    function setError($error) {
      $this->lastError = $error;
      return false;
    }
  }

  class ShapeRecord {
    var $SHPFile = NULL;
    var $DBFFile = NULL;

    var $recordNumber = NULL;
    var $shapeType = NULL;

    var $lastError = "";

    var $SHPData = array();
    var $DBFData = array();

    public function __construct($shapeType) {
      $this->shapeType = $shapeType;
    }

    function loadFromFile(&$SHPFile, &$DBFFile) {
      $this->SHPFile = $SHPFile;
      $this->DBFFile = $DBFFile;
      $this->_loadHeaders();

      switch ($this->shapeType) {
        case 0:
          $this->_loadNullRecord();
        break;
        case 1:
          $this->_loadPointRecord();
        break;
        case 21:
          $this->_loadPointMRecord();
        break;
        case 11:
          $this->_loadPointZRecord();
        break;
        case 3:
          $this->_loadPolyLineRecord();
        break;
        case 23:
          $this->_loadPolyLineMRecord();
        break;
        case 13:
          $this->_loadPolyLineZRecord();
        break;
        case 5:
          $this->_loadPolygonRecord();
        break;
        case 25:
          $this->_loadPolygonMRecord();
        break;
        case 15:
          $this->_loadPolygonZRecord();
        break;
        case 8:
          $this->_loadMultiPointRecord();
        break;
        case 28:
          $this->_loadMultiPointMRecord();
        break;
        case 18:
          $this->_loadMultiPointZRecord();
        break;
        default:
          $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
        break;
      }
      $this->_loadDBFData();
    }

    function saveToFile(&$SHPFile, &$DBFFile, $recordNumber) {
      $this->SHPFile = $SHPFile;
      $this->DBFFile = $DBFFile;
      $this->recordNumber = $recordNumber;
      $this->_saveHeaders();

      switch ($this->shapeType) {
        case 0:
          $this->_saveNullRecord();
        break;
        case 1:
          $this->_savePointRecord();
        break;
        case 21:
          $this->_savePointMRecord();
        break;
        case 11:
          $this->_savePointZRecord();
        break;
        case 3:
          $this->_savePolyLineRecord();
        break;
        case 23:
          $this->_savePolyLineMRecord();
        break;
        case 13:
          $this->_savePolyLineZRecord();
        break;
        case 5:
          $this->_savePolygonRecord();
        break;
        case 25:
          $this->_savePolygonMRecord();
        break;
        case 15:
          $this->_savePolygonZRecord();
        break;
        case 8:
          $this->_saveMultiPointRecord();
        break;
        case 28:
          $this->_saveMultiPointMRecord();
        break;
        case 18:
          $this->_saveMultiPointZRecord();
        break;
        default:
          $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
        break;
      }
      $this->_saveDBFData();
    }

    function updateDBFInfo($header) {
      $tmp = $this->DBFData;
      unset($this->DBFData);
      $this->DBFData = array();
      reset($header);
      while (list($key, $value) = each($header)) {
        $this->DBFData[$value[0]] = (isset($tmp[$value[0]])) ? $tmp[$value[0]] : "";
      }
    }

    function _loadHeaders() {
      $this->recordNumber = loadData("N", fread($this->SHPFile, 4));
      $tmp = loadData("N", fread($this->SHPFile, 4)); //We read the length of the record
      $this->shapeType = loadData("V", fread($this->SHPFile, 4));
    }

    function _saveHeaders() {
      fwrite($this->SHPFile, pack("N", $this->recordNumber));
      fwrite($this->SHPFile, pack("N", $this->getContentLength()));
      fwrite($this->SHPFile, pack("V", $this->shapeType));
    }

    function _loadPoint() {
      $data = array();

      $data["x"] = loadData("d", fread($this->SHPFile, 8));
      $data["y"] = loadData("d", fread($this->SHPFile, 8));

      return $data;
    }

    function _loadPointM() {
      $data = array();

      $data["x"] = loadData("d", fread($this->SHPFile, 8));
      $data["y"] = loadData("d", fread($this->SHPFile, 8));
      $data["m"] = loadData("d", fread($this->SHPFile, 8));

      return $data;
    }

    function _loadPointZ() {
      $data = array();

      $data["x"] = loadData("d", fread($this->SHPFile, 8));
      $data["y"] = loadData("d", fread($this->SHPFile, 8));
      $data["z"] = loadData("d", fread($this->SHPFile, 8));
      $data["m"] = loadData("d", fread($this->SHPFile, 8));

      return $data;
    }

    function _savePoint($data) {
      fwrite($this->SHPFile, packDouble($data["x"]));
      fwrite($this->SHPFile, packDouble($data["y"]));
    }

    function _savePointM($data) {
      fwrite($this->SHPFile, packDouble($data["x"]));
      fwrite($this->SHPFile, packDouble($data["y"]));
      fwrite($this->SHPFile, packDouble($data["m"]));
    }

    function _savePointZ($data) {
      fwrite($this->SHPFile, packDouble($data["x"]));
      fwrite($this->SHPFile, packDouble($data["y"]));
      fwrite($this->SHPFile, packDouble($data["z"]));
      fwrite($this->SHPFile, packDouble($data["m"]));
    }

    function _saveMeasure($data) {
      fwrite($this->SHPFile, packDouble($data["m"]));
    }

    function _saveZCoordinate($data) {
      fwrite($this->SHPFile, packDouble($data["z"]));
    }

    function _loadNullRecord() {
      $this->SHPData = array();
    }

    function _saveNullRecord() {
      //Don't save anything
    }

    function _loadPointRecord() {
      $this->SHPData = $this->_loadPoint();
    }

    function _loadPointMRecord() {
      $this->SHPData = $this->_loadPointM();
    }

    function _loadPointZRecord() {
      $this->SHPData = $this->_loadPointZ();
    }

    function _savePointRecord() {
      $this->_savePoint($this->SHPData);
    }

    function _savePointMRecord() {
      $this->_savePointM($this->SHPData);
    }

    function _savePointZRecord() {
      $this->_savePointZ($this->SHPData);
    }

    function _loadMultiPointRecord() {
      $this->SHPData = array();
      $this->SHPData["xmin"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData["ymin"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData["xmax"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData["ymax"] = loadData("d", fread($this->SHPFile, 8));

      $this->SHPData["numpoints"] = loadData("V", fread($this->SHPFile, 4));

      for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
        $this->SHPData["points"][] = $this->_loadPoint();
      }
    }

    function _loadMultiPointMZRecord( $type ) {

      $this->SHPData[$type."min"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData[$type."max"] = loadData("d", fread($this->SHPFile, 8));

      for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
        $this->SHPData["points"][$i][$type] = loadData("d", fread($this->SHPFile, 8));
      }
    }

    function _loadMultiPointMRecord() {
      $this->_loadMultiPointRecord();

      $this->_loadMultiPointMZRecord("m");
    }

    function _loadMultiPointZRecord() {
      $this->_loadMultiPointRecord();

      $this->_loadMultiPointMZRecord("z");
      $this->_loadMultiPointMZRecord("m");
    }

    function _saveMultiPointRecord() {
      fwrite($this->SHPFile, pack("dddd", $this->SHPData["xmin"], $this->SHPData["ymin"], $this->SHPData["xmax"], $this->SHPData["ymax"]));

      fwrite($this->SHPFile, pack("V", $this->SHPData["numpoints"]));

      for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
        $this->_savePoint($this->SHPData["points"][$i]);
      }
    }

    function _saveMultiPointMZRecord( $type ) {

      fwrite($this->SHPFile, pack("dd", $this->SHPData[$type."min"], $this->SHPData[$type."max"]));

      for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
        fwrite($this->SHPFile, packDouble($this->SHPData["points"][$type]));
      }
    }

    function _saveMultiPointMRecord() {
      $this->_saveMultiPointRecord();

      $this->_saveMultiPointMZRecord("m");
    }

    function _saveMultiPointZRecord() {
      $this->_saveMultiPointRecord();

      $this->_saveMultiPointMZRecord("z");
      $this->_saveMultiPointMZRecord("m");
    }

    function _loadPolyLineRecord() {
      $this->SHPData = array();
      $this->SHPData["xmin"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData["ymin"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData["xmax"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData["ymax"] = loadData("d", fread($this->SHPFile, 8));

      $this->SHPData["numparts"]  = loadData("V", fread($this->SHPFile, 4));
      $this->SHPData["numpoints"] = loadData("V", fread($this->SHPFile, 4));

      for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
        $this->SHPData["parts"][$i] = loadData("V", fread($this->SHPFile, 4));
      }

      $firstIndex = ftell($this->SHPFile);
      $readPoints = 0;
      reset($this->SHPData["parts"]);
      while (list($partIndex, $partData) = each($this->SHPData["parts"])) {
        if (!isset($this->SHPData["parts"][$partIndex]["points"]) || !is_array($this->SHPData["parts"][$partIndex]["points"])) {
          $this->SHPData["parts"][$partIndex] = array();
          $this->SHPData["parts"][$partIndex]["points"] = array();
        }
        while (!in_array($readPoints, $this->SHPData["parts"]) && ($readPoints < ($this->SHPData["numpoints"])) && !feof($this->SHPFile)) {
          $this->SHPData["parts"][$partIndex]["points"][] = $this->_loadPoint();
          $readPoints++;
        }
      }

      fseek($this->SHPFile, $firstIndex + ($readPoints*16));
    }

    function _loadPolyLineMZRecord( $type ) {

      $this->SHPData[$type."min"] = loadData("d", fread($this->SHPFile, 8));
      $this->SHPData[$type."max"] = loadData("d", fread($this->SHPFile, 8));

      $firstIndex = ftell($this->SHPFile);
      $readPoints = 0;
      reset($this->SHPData["parts"]);
      while (list($partIndex, $partData) = each($this->SHPData["parts"])) {
        while (!in_array($readPoints, $this->SHPData["parts"]) && ($readPoints < ($this->SHPData["numpoints"])) && !feof($this->SHPFile)) {
          $this->SHPData["parts"][$partIndex]["points"][$readPoints][$type] = loadData("d", fread($this->SHPFile, 8));
          $readPoints++;
        }
      }

      fseek($this->SHPFile, $firstIndex + ($readPoints*24));
    }

    function _loadPolyLineMRecord() {
      $this->_loadPolyLineRecord();

      $this->_loadPolyLineMZRecord("m");
    }

    function _loadPolyLineZRecord() {
      $this->_loadPolyLineRecord();

      $this->_loadPolyLineMZRecord("z");
      $this->_loadPolyLineMZRecord("m");
    }

    function _savePolyLineRecord() {
      fwrite($this->SHPFile, pack("dddd", $this->SHPData["xmin"], $this->SHPData["ymin"], $this->SHPData["xmax"], $this->SHPData["ymax"]));

      fwrite($this->SHPFile, pack("VV", $this->SHPData["numparts"], $this->SHPData["numpoints"]));

      for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
        fwrite($this->SHPFile, pack("V", count($this->SHPData["parts"][$i])-1));
      }

      foreach ($this->SHPData["parts"] as $partData){
        reset($partData["points"]);
        while (list($pointIndex, $pointData) = each($partData["points"])) {
          $this->_savePoint($pointData);
        }
      }
    }

    function _savePolyLineMZRecord( $type ) {
      fwrite($this->SHPFile, pack("dd", $this->SHPData[$type."min"], $this->SHPData[$type."max"]));

      foreach ($this->SHPData["parts"] as $partData){
        reset($partData["points"]);
        while (list($pointIndex, $pointData) = each($partData["points"])) {
          fwrite($this->SHPFile, packDouble($pointData[$type]));
        }
      }
    }

    function _savePolyLineMRecord() {
      $this->_savePolyLineRecord();

      $this->_savePolyLineMZRecord("m");
    }

    function _savePolyLineZRecord() {
      $this->_savePolyLineRecord();

      $this->_savePolyLineMZRecord("z");
      $this->_savePolyLineMZRecord("m");
    }

    function _loadPolygonRecord() {
      $this->_loadPolyLineRecord();
    }

    function _loadPolygonMRecord() {
      $this->_loadPolyLineMRecord();
    }

    function _loadPolygonZRecord() {
      $this->_loadPolyLineZRecord();
    }

    function _savePolygonRecord() {
      $this->_savePolyLineRecord();
    }

    function _savePolygonMRecord() {
      $this->_savePolyLineMRecord();
    }

    function _savePolygonZRecord() {
      $this->_savePolyLineZRecord();
    }

    function addPoint($point, $partIndex = 0) {
      switch ($this->shapeType) {
        case 0:
          //Don't add anything
        break;
        case 1:
        case 11:
        case 21:
          if (in_array($this->shapeType,array(11,21)) && !isset($point["m"])) $point["m"] = 0.0; // no_value
          if (in_array($this->shapeType,array(11)) && !isset($point["z"])) $point["z"] = 0.0; // no_value
          //Substitutes the value of the current point
          $this->SHPData = $point;
        break;
        case 3:
        case 5:
        case 13:
        case 15:
        case 23:
        case 25:
          if (in_array($this->shapeType,array(13,15,23,25)) && !isset($point["m"])) $point["m"] = 0.0; // no_value
          if (in_array($this->shapeType,array(13,15)) && !isset($point["z"])) $point["z"] = 0.0; // no_value

          //Adds a new point to the selected part
          if (!isset($this->SHPData["xmin"]) || ($this->SHPData["xmin"] > $point["x"])) $this->SHPData["xmin"] = $point["x"];
          if (!isset($this->SHPData["ymin"]) || ($this->SHPData["ymin"] > $point["y"])) $this->SHPData["ymin"] = $point["y"];
          if (isset($point["m"]) && (!isset($this->SHPData["mmin"]) || ($this->SHPData["mmin"] > $point["m"]))) $this->SHPData["mmin"] = $point["m"];
          if (isset($point["z"]) && (!isset($this->SHPData["zmin"]) || ($this->SHPData["zmin"] > $point["z"]))) $this->SHPData["zmin"] = $point["z"];
          if (!isset($this->SHPData["xmax"]) || ($this->SHPData["xmax"] < $point["x"])) $this->SHPData["xmax"] = $point["x"];
          if (!isset($this->SHPData["ymax"]) || ($this->SHPData["ymax"] < $point["y"])) $this->SHPData["ymax"] = $point["y"];
          if (isset($point["m"]) && (!isset($this->SHPData["mmax"]) || ($this->SHPData["mmax"] < $point["m"]))) $this->SHPData["mmax"] = $point["m"];
          if (isset($point["z"]) && (!isset($this->SHPData["zmax"]) || ($this->SHPData["zmax"] < $point["z"]))) $this->SHPData["zmax"] = $point["z"];

          $this->SHPData["parts"][$partIndex]["points"][] = $point;

          $this->SHPData["numparts"] = count($this->SHPData["parts"]);
          $this->SHPData["numpoints"] = 1 + (isset($this->SHPData["numpoints"])?$this->SHPData["numpoints"]:0);
        break;
        case 8:
        case 18:
        case 28:
          if (in_array($this->shapeType,array(18,28)) && !isset($point["m"])) $point["m"] = 0.0; // no_value
          if (in_array($this->shapeType,array(18)) && !isset($point["z"])) $point["z"] = 0.0; // no_value

          //Adds a new point
          if (!isset($this->SHPData["xmin"]) || ($this->SHPData["xmin"] > $point["x"])) $this->SHPData["xmin"] = $point["x"];
          if (!isset($this->SHPData["ymin"]) || ($this->SHPData["ymin"] > $point["y"])) $this->SHPData["ymin"] = $point["y"];
          if (isset($point["m"]) && (!isset($this->SHPData["mmin"]) || ($this->SHPData["mmin"] > $point["m"]))) $this->SHPData["mmin"] = $point["m"];
          if (isset($point["z"]) && (!isset($this->SHPData["zmin"]) || ($this->SHPData["zmin"] > $point["z"]))) $this->SHPData["zmin"] = $point["z"];
          if (!isset($this->SHPData["xmax"]) || ($this->SHPData["xmax"] < $point["x"])) $this->SHPData["xmax"] = $point["x"];
          if (!isset($this->SHPData["ymax"]) || ($this->SHPData["ymax"] < $point["y"])) $this->SHPData["ymax"] = $point["y"];
          if (isset($point["m"]) && (!isset($this->SHPData["mmax"]) || ($this->SHPData["mmax"] < $point["m"]))) $this->SHPData["mmax"] = $point["m"];
          if (isset($point["z"]) && (!isset($this->SHPData["zmax"]) || ($this->SHPData["zmax"] < $point["z"]))) $this->SHPData["zmax"] = $point["z"];

          $this->SHPData["points"][] = $point;
          $this->SHPData["numpoints"] = 1 + (isset($this->SHPData["numpoints"])?$this->SHPData["numpoints"]:0);
        break;
        default:
          $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
        break;
      }
    }

    function deletePoint($pointIndex = 0, $partIndex = 0) {
      switch ($this->shapeType) {
        case 0:
          //Don't delete anything
        break;
        case 1:
        case 11:
        case 21:
          //Sets the value of the point to zero
          $this->SHPData["x"] = 0.0;
          $this->SHPData["y"] = 0.0;
          if (in_array($this->shapeType,array(11,21))) $this->SHPData["m"] = 0.0;
          if (in_array($this->shapeType,array(11))) $this->SHPData["z"] = 0.0;
        break;
        case 3:
        case 5:
        case 13:
        case 15:
        case 23:
        case 25:
          //Deletes the point from the selected part, if exists
          if (isset($this->SHPData["parts"][$partIndex]) && isset($this->SHPData["parts"][$partIndex]["points"][$pointIndex])) {
            for ($i = $pointIndex; $i < (count($this->SHPData["parts"][$partIndex]["points"]) - 1); $i++) {
              $this->SHPData["parts"][$partIndex]["points"][$i] = $this->SHPData["parts"][$partIndex]["points"][$i + 1];
            }
            unset($this->SHPData["parts"][$partIndex]["points"][count($this->SHPData["parts"][$partIndex]["points"]) - 1]);

            $this->SHPData["numparts"] = count($this->SHPData["parts"]);
            $this->SHPData["numpoints"]--;
          }
        break;
        case 8:
        case 18:
        case 28:
          //Deletes the point, if exists
          if (isset($this->SHPData["points"][$pointIndex])) {
            for ($i = $pointIndex; $i < (count($this->SHPData["points"]) - 1); $i++) {
              $this->SHPData["points"][$i] = $this->SHPData["points"][$i + 1];
            }
            unset($this->SHPData["points"][count($this->SHPData["points"]) - 1]);

            $this->SHPData["numpoints"]--;
          }
        break;
        default:
          $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
        break;
      }
    }

    function getContentLength() {
      // The content length for a record is the length of the record contents section measured in 16-bit words.
      // one coordinate makes 4 16-bit words (64 bit double)
      switch ($this->shapeType) {
        case 0:
          $result = 0;
        break;
        case 1:
          $result = 10;
        break;
        case 21:
          $result = 10 + 4;
        break;
        case 11:
          $result = 10 + 8;
        break;
        case 3:
        case 5:
          $result = 22 + 2*count($this->SHPData["parts"]);
          for ($i = 0; $i < count($this->SHPData["parts"]); $i++) {
            $result += 8*count($this->SHPData["parts"][$i]["points"]);
          }
        break;
        case 23:
        case 25:
          $result = 22 + (2*4) + 2*count($this->SHPData["parts"]);
          for ($i = 0; $i < count($this->SHPData["parts"]); $i++) {
            $result += (8+4)*count($this->SHPData["parts"][$i]["points"]);
          }
        break;
        case 13:
        case 15:
          $result = 22 + (4*4) + 2*count($this->SHPData["parts"]);
          for ($i = 0; $i < count($this->SHPData["parts"]); $i++) {
            $result += (8+8)*count($this->SHPData["parts"][$i]["points"]);
          }
        break;
        case 8:
          $result = 20 + 8*count($this->SHPData["points"]);
        break;
        case 28:
          $result = 20 + (2*4) + (8+4)*count($this->SHPData["points"]);
        break;
        case 18:
          $result = 20 + (4*4) + (8+8)*count($this->SHPData["points"]);
        break;
        default:
          $result = false;
          $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
        break;
      }
      return $result;
    }

    function _loadDBFData() {
      $this->DBFData = @dbase_get_record_with_names($this->DBFFile, $this->recordNumber);
      unset($this->DBFData["deleted"]);
    }

    function _saveDBFData() {
      unset($this->DBFData["deleted"]);
      if ($this->recordNumber <= dbase_numrecords($this->DBFFile)) {
        if (!dbase_replace_record($this->DBFFile, array_values($this->DBFData), $this->recordNumber)) {
          $this->setError("I wasn't possible to update the information in the DBF file.");
        }
      } else {
        if (!dbase_add_record($this->DBFFile, array_values($this->DBFData))) {
          $this->setError("I wasn't possible to add the information to the DBF file.");
        }
      }
    }

    function setError($error) {
      $this->lastError = $error;
      return false;
    }
  }
