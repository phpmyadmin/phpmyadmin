<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2009 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.0, 2009-08-10
 */


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/');
}

/** PHPExcel_Cell */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell.php';

/** PHPExcel_DocumentProperties */
require_once PHPEXCEL_ROOT . 'PHPExcel/DocumentProperties.php';

/** PHPExcel_DocumentSecurity */
require_once PHPEXCEL_ROOT . 'PHPExcel/DocumentSecurity.php';

/** PHPExcel_Worksheet */
require_once PHPEXCEL_ROOT . 'PHPExcel/Worksheet.php';

/** PHPExcel_Shared_ZipStreamWrapper */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/ZipStreamWrapper.php';

/** PHPExcel_NamedRange */
require_once PHPEXCEL_ROOT . 'PHPExcel/NamedRange.php';

/** PHPExcel_WorksheetIterator */
require_once PHPEXCEL_ROOT . 'PHPExcel/WorksheetIterator.php';


/**
 * PHPExcel
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel
{
	/**
	 * Document properties
	 *
	 * @var PHPExcel_DocumentProperties
	 */
	private $_properties;

	/**
	 * Document security
	 *
	 * @var PHPExcel_DocumentSecurity
	 */
	private $_security;

	/**
	 * Collection of Worksheet objects
	 *
	 * @var PHPExcel_Worksheet[]
	 */
	private $_workSheetCollection = array();

	/**
	 * Active sheet index
	 *
	 * @var int
	 */
	private $_activeSheetIndex = 0;

	/**
	 * Named ranges
	 *
	 * @var PHPExcel_NamedRange[]
	 */
	private $_namedRanges = array();

	/**
	 * CellXf supervisor
	 *
	 * @var PHPExcel_Style
	 */
	private $_cellXfSupervisor;

	/**
	 * CellXf collection
	 *
	 * @var PHPExcel_Style[]
	 */
	private $_cellXfCollection = array();

	/**
	 * CellStyleXf collection
	 *
	 * @var PHPExcel_Style[]
	 */
	private $_cellStyleXfCollection = array();

	/**
	 * Create a new PHPExcel with one Worksheet
	 */
	public function __construct()
	{
		// Initialise worksheet collection and add one worksheet
		$this->_workSheetCollection = array();
		$this->_workSheetCollection[] = new PHPExcel_Worksheet($this);
		$this->_activeSheetIndex = 0;

		// Create document properties
		$this->_properties = new PHPExcel_DocumentProperties();

		// Create document security
		$this->_security = new PHPExcel_DocumentSecurity();

		// Set named ranges
		$this->_namedRanges = array();

		// Create the cellXf supervisor
		$this->_cellXfSupervisor = new PHPExcel_Style(true);
		$this->_cellXfSupervisor->bindParent($this);

		// Create the default style
		$this->addCellXf(new PHPExcel_Style);
		$this->addCellStyleXf(new PHPExcel_Style);
	}

	/**
	 * Get properties
	 *
	 * @return PHPExcel_DocumentProperties
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * Set properties
	 *
	 * @param PHPExcel_DocumentProperties	$pValue
	 */
	public function setProperties(PHPExcel_DocumentProperties $pValue)
	{
		$this->_properties = $pValue;
	}

	/**
	 * Get security
	 *
	 * @return PHPExcel_DocumentSecurity
	 */
	public function getSecurity()
	{
		return $this->_security;
	}

	/**
	 * Set security
	 *
	 * @param PHPExcel_DocumentSecurity	$pValue
	 */
	public function setSecurity(PHPExcel_DocumentSecurity $pValue)
	{
		$this->_security = $pValue;
	}

	/**
	 * Get active sheet
	 *
	 * @return PHPExcel_Worksheet
	 */
	public function getActiveSheet()
	{
		return $this->_workSheetCollection[$this->_activeSheetIndex];
	}

    /**
     * Create sheet and add it to this workbook
     *
     * @return PHPExcel_Worksheet
     */
    public function createSheet($iSheetIndex = null)
    {
        $newSheet = new PHPExcel_Worksheet($this);
        $this->addSheet($newSheet, $iSheetIndex);
        return $newSheet;
    }

    /**
     * Add sheet
     *
     * @param PHPExcel_Worksheet $pSheet
     * @throws Exception
     */
    public function addSheet(PHPExcel_Worksheet $pSheet = null, $iSheetIndex = null)
    {
        if(is_null($iSheetIndex))
        {
            $this->_workSheetCollection[] = $pSheet;
        }
        else
        {
            // Insert the sheet at the requested index
            array_splice(
                $this->_workSheetCollection,
                $iSheetIndex,
                0,
                array($pSheet)
                );
        }
    }

	/**
	 * Remove sheet by index
	 *
	 * @param int $pIndex Active sheet index
	 * @throws Exception
	 */
	public function removeSheetByIndex($pIndex = 0)
	{
		if ($pIndex > count($this->_workSheetCollection) - 1) {
			throw new Exception("Sheet index is out of bounds.");
		} else {
			array_splice($this->_workSheetCollection, $pIndex, 1);
		}
	}

	/**
	 * Get sheet by index
	 *
	 * @param int $pIndex Sheet index
	 * @return PHPExcel_Worksheet
	 * @throws Exception
	 */
	public function getSheet($pIndex = 0)
	{
		if ($pIndex > count($this->_workSheetCollection) - 1) {
			throw new Exception("Sheet index is out of bounds.");
		} else {
			return $this->_workSheetCollection[$pIndex];
		}
	}

	/**
	 * Get all sheets
	 *
	 * @return PHPExcel_Worksheet[]
	 */
	public function getAllSheets()
	{
		return $this->_workSheetCollection;
	}

	/**
	 * Get sheet by name
	 *
	 * @param string $pName Sheet name
	 * @return PHPExcel_Worksheet
	 * @throws Exception
	 */
	public function getSheetByName($pName = '')
	{
		$worksheetCount = count($this->_workSheetCollection);
		for ($i = 0; $i < $worksheetCount; ++$i) {
			if ($this->_workSheetCollection[$i]->getTitle() == $pName) {
				return $this->_workSheetCollection[$i];
			}
		}

		return null;
	}

	/**
	 * Get index for sheet
	 *
	 * @param PHPExcel_Worksheet $pSheet
	 * @return Sheet index
	 * @throws Exception
	 */
	public function getIndex(PHPExcel_Worksheet $pSheet)
	{
		foreach ($this->_workSheetCollection as $key => $value) {
			if ($value->getHashCode() == $pSheet->getHashCode()) {
				return $key;
			}
		}
	}

    /**
	 * Set index for sheet by sheet name.
	 *
	 * @param string $sheetName Sheet name to modify index for
	 * @param int $newIndex New index for the sheet
	 * @return New sheet index
	 * @throws Exception
	 */
    public function setIndexByName($sheetName, $newIndex)
    {
        $oldIndex = $this->getIndex($this->getSheetByName($sheetName));
        $pSheet = array_splice(
            $this->_workSheetCollection,
            $oldIndex,
            1
            );
        array_splice(
            $this->_workSheetCollection,
            $newIndex,
            0,
            $pSheet
            );
        return $newIndex;
    }

	/**
	 * Get sheet count
	 *
	 * @return int
	 */
	public function getSheetCount()
	{
		return count($this->_workSheetCollection);
	}

	/**
	 * Get active sheet index
	 *
	 * @return int Active sheet index
	 */
	public function getActiveSheetIndex()
	{
		return $this->_activeSheetIndex;
	}

	/**
	 * Set active sheet index
	 *
	 * @param int $pIndex Active sheet index
	 * @throws Exception
	 * @return PHPExcel_Worksheet
	 */
	public function setActiveSheetIndex($pIndex = 0)
	{
		if ($pIndex > count($this->_workSheetCollection) - 1) {
			throw new Exception("Active sheet index is out of bounds.");
		} else {
			$this->_activeSheetIndex = $pIndex;
		}
		return $this->getActiveSheet();
	}

	/**
	 * Get sheet names
	 *
	 * @return string[]
	 */
	public function getSheetNames()
	{
		$returnValue = array();
		$worksheetCount = $this->getSheetCount();
		for ($i = 0; $i < $worksheetCount; ++$i) {
			array_push($returnValue, $this->getSheet($i)->getTitle());
		}

		return $returnValue;
	}

	/**
	 * Add external sheet
	 *
	 * @param PHPExcel_Worksheet $pSheet External sheet to add
	 * @throws Exception
	 * @return PHPExcel_Worksheet
	 */
	public function addExternalSheet(PHPExcel_Worksheet $pSheet) {
		if (!is_null($this->getSheetByName($pSheet->getTitle()))) {
			throw new Exception("Workbook already contains a worksheet named '{$pSheet->getTitle()}'. Rename the external sheet first.");
		}

		// count how many cellXfs there are in this workbook currently, we will need this below
		$countCellXfs = count($this->_cellXfCollection);

		// copy all the shared cellXfs from the external workbook and append them to the current
		foreach ($pSheet->getParent()->getCellXfCollection() as $cellXf) {
			$this->addCellXf(clone $cellXf);
		}

		// move sheet to this workbook
		$pSheet->rebindParent($this);

		// update the cellXfs
		foreach ($pSheet->getCellCollection(false) as $cell) {
			$cell->setXfIndex( $cell->getXfIndex() + $countCellXfs );
		}

		return $this->addSheet($pSheet);
	}

	/**
	 * Get named ranges
	 *
	 * @return PHPExcel_NamedRange[]
	 */
	public function getNamedRanges() {
		return $this->_namedRanges;
	}

	/**
	 * Add named range
	 *
	 * @param PHPExcel_NamedRange $namedRange
	 * @return PHPExcel
	 */
	public function addNamedRange(PHPExcel_NamedRange $namedRange) {
		$this->_namedRanges[$namedRange->getWorksheet()->getTitle().'!'.$namedRange->getName()] = $namedRange;
		return true;
	}

	/**
	 * Get named range
	 *
	 * @param string $namedRange
	 */
	public function getNamedRange($namedRange, PHPExcel_Worksheet $pSheet = null) {
		if ($namedRange != '' && !is_null($namedRange)) {
			if (!is_null($pSheet)) {
				$key = $pSheet->getTitle().'!'.$namedRange;
				if (isset($this->_namedRanges[$key])) {
					return $this->_namedRanges[$key];
				}
			}
			$returnCount = 0;
			foreach($this->_namedRanges as $_namedRange) {
				if ($_namedRange->getName() == $namedRange) {
					if ((!is_null($pSheet)) && ($_namedRange->getWorksheet()->getTitle() == $pSheet->getTitle())) {
						return $_namedRange;
					} else {
						$returnCount++;
						$returnValue = $_namedRange;
					}
				}
			}
			if ($returnCount == 1) {
				return $returnValue;
			}
		}

		return null;
	}

	/**
	 * Remove named range
	 *
	 * @param string $namedRange
	 * @return PHPExcel
	 */
	public function removeNamedRange($namedRange, PHPExcel_Worksheet $pSheet = null) {
		if ($namedRange != '' && !is_null($namedRange)) {
			if (!is_null($pSheet)) {
				$key = $pSheet->getTitle().'!'.$namedRange;
				if (isset($this->_namedRanges[$key])) {
					unset($this->_namedRanges[$key]);
				}
			}
			foreach($this->_namedRanges as $_namedRange) {
				if ($_namedRange->getName() == $namedRange) {
					if ((!is_null($pSheet)) && ($_namedRange->getWorksheet()->getTitle() == $pSheet->getTitle())) {
						$key = $pSheet->getTitle().'!'.$namedRange;
						if (isset($this->_namedRanges[$key])) {
							unset($this->_namedRanges[$key]);
						}
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Get worksheet iterator
	 *
	 * @return PHPExcel_WorksheetIterator
	 */
	public function getWorksheetIterator() {
		return new PHPExcel_WorksheetIterator($this);
	}

	/**
	 * Copy workbook (!= clone!)
	 *
	 * @return PHPExcel
	 */
	public function copy() {
		$copied = clone $this;

		$worksheetCount = count($this->_workSheetCollection);
		for ($i = 0; $i < $worksheetCount; ++$i) {
			$this->_workSheetCollection[$i] = $this->_workSheetCollection[$i]->copy();
			$this->_workSheetCollection[$i]->rebindParent($this);
		}

		return $copied;
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		foreach($this as $key => $val) {
			if (is_object($val) || (is_array($val))) {
				$this->{$key} = unserialize(serialize($val));
			}
		}
	}

	/**
	 * Get the workbook collection of cellXfs
	 *
	 * @return PHPExcel_Style[]
	 */
	public function getCellXfCollection()
	{
		return $this->_cellXfCollection;
	}

	/**
	 * Get cellXf by index
	 *
	 * @param int $index
	 * @return PHPExcel_Style
	 */
	public function getCellXfByIndex($pIndex = 0)
	{
		return $this->_cellXfCollection[$pIndex];
	}

	/**
	 * Get cellXf by hash code
	 *
	 * @param string $pValue
	 * @return PHPExcel_Style|false
	 */
	public function getCellXfByHashCode($pValue = '')
	{
		foreach ($this->_cellXfCollection as $cellXf) {
			if ($cellXf->getHashCode() == $pValue) {
				return $cellXf;
			}
		}
		return false;
	}

	/**
	 * Get default style
	 *
	 * @return PHPExcel_Style
	 * @throws Exception
	 */
	public function getDefaultStyle()
	{
		if (isset($this->_cellXfCollection[0])) {
			return $this->_cellXfCollection[0];
		}
		throw new Exception('No default style found for this workbook');
	}

	/**
	 * Add a cellXf to the workbook
	 *
	 * @param PHPExcel_Style
	 */
	public function addCellXf(PHPExcel_Style $style)
	{
		$this->_cellXfCollection[] = $style;
		$style->setIndex(count($this->_cellXfCollection) - 1);
	}

	/**
	 * Remove cellXf by index. It is ensured that all cells get their xf index updated.
	 *
	 * @param int $pIndex Index to cellXf
	 * @throws Exception
	 */
	public function removeCellXfByIndex($pIndex = 0)
	{
		if ($pIndex > count($this->_cellXfCollection) - 1) {
			throw new Exception("CellXf index is out of bounds.");
		} else {
			// first remove the cellXf
			array_splice($this->_cellXfCollection, $pIndex, 1);

			// then update cellXf indexes for cells
			foreach ($this->_workSheetCollection as $worksheet) {
				foreach ($worksheet->getCellCollection(false) as $cell) {
					$xfIndex = $cell->getXfIndex();
					if ($xfIndex > $pIndex ) {
						// decrease xf index by 1
						$cell->setXfIndex($xfIndex - 1);
					} else if ($xfIndex == $pIndex) {
						// set to default xf index 0
						$cell->setXfIndex(0);
					}
				}
			}
		}
	}

	/**
	 * Get the cellXf supervisor
	 *
	 * @return PHPExcel_Style
	 */
	public function getCellXfSupervisor()
	{
		return $this->_cellXfSupervisor;
	}

	/**
	 * Get the workbook collection of cellStyleXfs
	 *
	 * @return PHPExcel_Style[]
	 */
	public function getCellStyleXfCollection()
	{
		return $this->_cellStyleXfCollection;
	}

	/**
	 * Get cellStyleXf by index
	 *
	 * @param int $pIndex
	 * @return PHPExcel_Style
	 */
	public function getCellStyleXfByIndex($pIndex = 0)
	{
		return $this->_cellStyleXfCollection[$pIndex];
	}

	/**
	 * Get cellStyleXf by hash code
	 *
	 * @param string $pValue
	 * @return PHPExcel_Style|false
	 */
	public function getCellStyleXfByHashCode($pValue = '')
	{
		foreach ($this->_cellXfStyleCollection as $cellStyleXf) {
			if ($cellStyleXf->getHashCode() == $pValue) {
				return $cellStyleXf;
			}
		}
		return false;
	}

	/**
	 * Add a cellStyleXf to the workbook
	 *
	 * @param PHPExcel_Style $pStyle
	 */
	public function addCellStyleXf(PHPExcel_Style $pStyle)
	{
		$this->_cellStyleXfCollection[] = $pStyle;
		$pStyle->setIndex(count($this->_cellStyleXfCollection) - 1);
	}

	/**
	 * Remove cellStyleXf by index
	 *
	 * @param int $pIndex
	 * @throws Exception
	 */
	public function removeCellStyleXfByIndex($pIndex = 0)
	{
		if ($pIndex > count($this->_cellStyleXfCollection) - 1) {
			throw new Exception("CellStyleXf index is out of bounds.");
		} else {
			array_splice($this->_cellStyleXfCollection, $pIndex, 1);
		}
	}

	/**
	 * Eliminate all unneeded cellXf and afterwards update the xfIndex for all cells in the workbook
	 */
	public function garbageCollect()
	{
    	// how many references are there to each cellXf ?
		$countReferencesCellXf = array();
		foreach ($this->_cellXfCollection as $index => $cellXf) {
			$countReferencesCellXf[$index] = 0;
		}

		foreach ($this->getWorksheetIterator() as $sheet) {
			foreach ($sheet->getCellCollection(false) as $cell) {
				++$countReferencesCellXf[$cell->getXfIndex()];
			}
		}

		// remove those cellXfs that have zero references and create mapping so we can update xfIndex for all cells
		$countNeededCellXfs = 0;
		foreach ($this->_cellXfCollection as $index => $cellXf) {
			if ($countReferencesCellXf[$index] > 0 || $index == 0) { // we must never remove the first cellXf
				++$countNeededCellXfs;
			} else {
				unset($this->_cellXfCollection[$index]);
			}
			$map[$index] = $countNeededCellXfs - 1;
		}
		$this->_cellXfCollection = array_values($this->_cellXfCollection);

		// if we removed the first style by accident, recreate it
		if (count($this->_cellXfCollection) == 0) {
			$this->_cellXfCollection[] = new PHPExcel_Style();
		}

		// update the xfIndex for all cells
		foreach ($this->getWorksheetIterator() as $sheet) {
			foreach ($sheet->getCellCollection(false) as $cell) {
				$cell->setXfIndex( $map[$cell->getXfIndex()] );
			}
		}

		// also do garbage collection for all the sheets
		foreach ($this->getWorksheetIterator() as $sheet) {
			$sheet->garbageCollect();
		}
	}

}
