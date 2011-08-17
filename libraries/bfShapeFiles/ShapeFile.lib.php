<?php
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

    function ShapeFile($shapeType, $boundingBox = array("xmin" => 0.0, "ymin" => 0.0, "xmax" => 0.0, "ymax" => 0.0), $FileName = NULL) {
      $this->shapeType = $shapeType;
      $this->boundingBox = $boundingBox;
      $this->FileName = $FileName;
      $this->fileLength = 50;
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
      fwrite($this->SHPFile, pack("dddd", 0, 0, 0, 0));

      fwrite($this->SHXFile, pack("NNNNNN", 9994, 0, 0, 0, 0, 0));
      fwrite($this->SHXFile, pack("N", 50 + 4*count($this->records)));
      fwrite($this->SHXFile, pack("V", 1000));
      fwrite($this->SHXFile, pack("V", $this->shapeType));
      fwrite($this->SHXFile, packDouble($this->boundingBox['xmin']));
      fwrite($this->SHXFile, packDouble($this->boundingBox['ymin']));
      fwrite($this->SHXFile, packDouble($this->boundingBox['xmax']));
      fwrite($this->SHXFile, packDouble($this->boundingBox['ymax']));
      fwrite($this->SHXFile, pack("dddd", 0, 0, 0, 0));
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

    function ShapeRecord($shapeType) {
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
        case 3:
          $this->_savePolyLineRecord();
        break;
        case 5:
          $this->_savePolygonRecord();
        break;
        case 8:
          $this->_saveMultiPointRecord();
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

    function _savePoint($data) {
      fwrite($this->SHPFile, packDouble($data["x"]));
      fwrite($this->SHPFile, packDouble($data["y"]));
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

    function _savePointRecord() {
      $this->_savePoint($this->SHPData);
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

    function _saveMultiPointRecord() {
      fwrite($this->SHPFile, pack("dddd", $this->SHPData["xmin"], $this->SHPData["ymin"], $this->SHPData["xmax"], $this->SHPData["ymax"]));

      fwrite($this->SHPFile, pack("V", $this->SHPData["numpoints"]));

      for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
        $this->_savePoint($this->SHPData["points"][$i]);
      }
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

    function _savePolyLineRecord() {
      fwrite($this->SHPFile, pack("dddd", $this->SHPData["xmin"], $this->SHPData["ymin"], $this->SHPData["xmax"], $this->SHPData["ymax"]));

      fwrite($this->SHPFile, pack("VV", $this->SHPData["numparts"], $this->SHPData["numpoints"]));

      for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
        fwrite($this->SHPFile, pack("V", count($this->SHPData["parts"][$i])));
      }

      reset($this->SHPData["parts"]);
      foreach ($this->SHPData["parts"] as $partData){
        reset($partData["points"]);
        while (list($pointIndex, $pointData) = each($partData["points"])) {
          $this->_savePoint($pointData);
        }
      }
    }

    function _loadPolygonRecord() {
      $this->_loadPolyLineRecord();
    }

    function _savePolygonRecord() {
      $this->_savePolyLineRecord();
    }

    function addPoint($point, $partIndex = 0) {
      switch ($this->shapeType) {
        case 0:
          //Don't add anything
        break;
        case 1:
          //Substitutes the value of the current point
          $this->SHPData = $point;
        break;
        case 3:
        case 5:
          //Adds a new point to the selected part
          if (!isset($this->SHPData["xmin"]) || ($this->SHPData["xmin"] > $point["x"])) $this->SHPData["xmin"] = $point["x"];
          if (!isset($this->SHPData["ymin"]) || ($this->SHPData["ymin"] > $point["y"])) $this->SHPData["ymin"] = $point["y"];
          if (!isset($this->SHPData["xmax"]) || ($this->SHPData["xmax"] < $point["x"])) $this->SHPData["xmax"] = $point["x"];
          if (!isset($this->SHPData["ymax"]) || ($this->SHPData["ymax"] < $point["y"])) $this->SHPData["ymax"] = $point["y"];

          $this->SHPData["parts"][$partIndex]["points"][] = $point;

          $this->SHPData["numparts"] = count($this->SHPData["parts"]);
          $this->SHPData["numpoints"]++;
        break;
        case 8:
          //Adds a new point
          if (!isset($this->SHPData["xmin"]) || ($this->SHPData["xmin"] > $point["x"])) $this->SHPData["xmin"] = $point["x"];
          if (!isset($this->SHPData["ymin"]) || ($this->SHPData["ymin"] > $point["y"])) $this->SHPData["ymin"] = $point["y"];
          if (!isset($this->SHPData["xmax"]) || ($this->SHPData["xmax"] < $point["x"])) $this->SHPData["xmax"] = $point["x"];
          if (!isset($this->SHPData["ymax"]) || ($this->SHPData["ymax"] < $point["y"])) $this->SHPData["ymax"] = $point["y"];

          $this->SHPData["points"][] = $point;
          $this->SHPData["numpoints"]++;
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
          //Sets the value of the point to zero
          $this->SHPData["x"] = 0.0;
          $this->SHPData["y"] = 0.0;
        break;
        case 3:
        case 5:
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
      switch ($this->shapeType) {
        case 0:
          $result = 0;
        break;
        case 1:
          $result = 10;
        break;
        case 3:
        case 5:
          $result = 22 + 2*count($this->SHPData["parts"]);
          for ($i = 0; $i < count($this->SHPData["parts"]); $i++) {
            $result += 8*count($this->SHPData["parts"][$i]["points"]);
          }
        break;
        case 8:
          $result = 20 + 8*count($this->SHPData["points"]);
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

?>
