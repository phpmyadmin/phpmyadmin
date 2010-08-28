<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2010 PHPExcel
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
 * @package	PHPExcel
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.7.3c, 2010-06-01
 */


/**
 * PHPExcel_ReferenceHelper (Singleton)
 *
 * @category   PHPExcel
 * @package	PHPExcel
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_ReferenceHelper
{
	/**
	 * Instance of this class
	 *
	 * @var PHPExcel_ReferenceHelper
	 */
	private static $_instance;

	/**
	 * Get an instance of this class
	 *
	 * @return PHPExcel_ReferenceHelper
	 */
	public static function getInstance() {
		if (!isset(self::$_instance) || is_null(self::$_instance)) {
			self::$_instance = new PHPExcel_ReferenceHelper();
		}

		return self::$_instance;
	}

	/**
	 * Create a new PHPExcel_ReferenceHelper
	 */
	protected function __construct() {
	}

	/**
	 * Insert a new column, updating all possible related data
	 *
	 * @param	int	$pBefore	Insert before this one
	 * @param	int	$pNumCols	Number of columns to insert
	 * @param	int	$pNumRows	Number of rows to insert
	 * @throws	Exception
	 */
	public function insertNewBefore($pBefore = 'A1', $pNumCols = 0, $pNumRows = 0, PHPExcel_Worksheet $pSheet = null) {
		$aCellCollection = $pSheet->getCellCollection();

		// Get coordinates of $pBefore
		$beforeColumn	= 'A';
		$beforeRow		= 1;
		list($beforeColumn, $beforeRow) = PHPExcel_Cell::coordinateFromString( $pBefore );


		// Clear cells if we are removing columns or rows
		$highestColumn	= $pSheet->getHighestColumn();
		$highestRow	= $pSheet->getHighestRow();

		// 1. Clear column strips if we are removing columns
		if ($pNumCols < 0 && PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 + $pNumCols > 0) {
			for ($i = 1; $i <= $highestRow - 1; ++$i) {
				for ($j = PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1 + $pNumCols; $j <= PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2; ++$j) {
					$coordinate = PHPExcel_Cell::stringFromColumnIndex($j) . $i;
					$pSheet->removeConditionalStyles($coordinate);
					if ($pSheet->cellExists($coordinate)) {
						$pSheet->getCell($coordinate)->setValueExplicit('', PHPExcel_Cell_DataType::TYPE_NULL);
						$pSheet->getCell($coordinate)->setXfIndex(0);
					}
				}
			}
		}

		// 2. Clear row strips if we are removing rows
		if ($pNumRows < 0 && $beforeRow - 1 + $pNumRows > 0) {
			for ($i = PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1; $i <= PHPExcel_Cell::columnIndexFromString($highestColumn) - 1; ++$i) {
				for ($j = $beforeRow + $pNumRows; $j <= $beforeRow - 1; ++$j) {
					$coordinate = PHPExcel_Cell::stringFromColumnIndex($i) . $j;
					$pSheet->removeConditionalStyles($coordinate);
					if ($pSheet->cellExists($coordinate)) {
						$pSheet->getCell($coordinate)->setValueExplicit('', PHPExcel_Cell_DataType::TYPE_NULL);
						$pSheet->getCell($coordinate)->setXfIndex(0);
					}
				}
			}
		}


		// Loop through cells, bottom-up, and change cell coordinates
		while (($cellID = ($pNumCols < 0 || $pNumRows < 0) ? array_shift($aCellCollection) : array_pop($aCellCollection))) {
			$cell = $pSheet->getCell($cellID);

			// New coordinates
			$newCoordinates = PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($cell->getColumn()) - 1 + $pNumCols ) . ($cell->getRow() + $pNumRows);

			// Should the cell be updated? Move value and cellXf index from one cell to another.
			if (
					(PHPExcel_Cell::columnIndexFromString( $cell->getColumn() ) >= PHPExcel_Cell::columnIndexFromString($beforeColumn)) &&
					($cell->getRow() >= $beforeRow)
				 ) {

				// Update cell styles
				$pSheet->getCell($newCoordinates)->setXfIndex($cell->getXfIndex());
				$cell->setXfIndex(0);

				// Insert this cell at its new location
				if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_FORMULA) {
					// Formula should be adjusted
					$pSheet->getCell($newCoordinates)
						->setValue($this->updateFormulaReferences($cell->getValue(), $pBefore, $pNumCols, $pNumRows));
				} else {
					// Formula should not be adjusted
					$pSheet->getCell($newCoordinates)->setValue($cell->getValue());
				}

				// Clear the original cell
				$pSheet->getCell($cell->getCoordinate())->setValue('');
			}
		}


		// Duplicate styles for the newly inserted cells
		$highestColumn	= $pSheet->getHighestColumn();
		$highestRow	= $pSheet->getHighestRow();

		if ($pNumCols > 0 && PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 > 0) {
			for ($i = $beforeRow; $i <= $highestRow - 1; ++$i) {

				// Style
				$coordinate = PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 ) . $i;
				if ($pSheet->cellExists($coordinate)) {
					$xfIndex = $pSheet->getCell($coordinate)->getXfIndex();
					$conditionalStyles = $pSheet->conditionalStylesExists($coordinate) ?
						$pSheet->getConditionalStyles($coordinate) : false;
					for ($j = PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1; $j <= PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 + $pNumCols; ++$j) {
						$pSheet->getCellByColumnAndRow($j, $i)->setXfIndex($xfIndex);
						if ($conditionalStyles) {
							$cloned = array();
							foreach ($conditionalStyles as $conditionalStyle) {
								$cloned[] = clone $conditionalStyle;
							}
							$pSheet->setConditionalStyles(PHPExcel_Cell::stringFromColumnIndex($j) . $i, $cloned);
						}
					}
				}

			}
		}

		if ($pNumRows > 0 && $beforeRow - 1 > 0) {
			for ($i = PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1; $i <= PHPExcel_Cell::columnIndexFromString($highestColumn) - 1; ++$i) {

				// Style
				$coordinate = PHPExcel_Cell::stringFromColumnIndex($i) . ($beforeRow - 1);
				if ($pSheet->cellExists($coordinate)) {
					$xfIndex = $pSheet->getCell($coordinate)->getXfIndex();
					$conditionalStyles = $pSheet->conditionalStylesExists($coordinate) ?
						$pSheet->getConditionalStyles($coordinate) : false;
					for ($j = $beforeRow; $j <= $beforeRow - 1 + $pNumRows; ++$j) {
						$pSheet->getCell(PHPExcel_Cell::stringFromColumnIndex($i) . $j)->setXfIndex($xfIndex);
						if ($conditionalStyles) {
							$cloned = array();
							foreach ($conditionalStyles as $conditionalStyle) {
								$cloned[] = clone $conditionalStyle;
							}
							$pSheet->setConditionalStyles(PHPExcel_Cell::stringFromColumnIndex($i) . $j, $cloned);
						}
					}
				}
			}
		}


		// Update worksheet: column dimensions
		$aColumnDimensions = array_reverse($pSheet->getColumnDimensions(), true);
		if (count($aColumnDimensions) > 0) {
			foreach ($aColumnDimensions as $objColumnDimension) {
				$newReference = $this->updateCellReference($objColumnDimension->getColumnIndex() . '1', $pBefore, $pNumCols, $pNumRows);
				list($newReference) = PHPExcel_Cell::coordinateFromString($newReference);
				if ($objColumnDimension->getColumnIndex() != $newReference) {
					$objColumnDimension->setColumnIndex($newReference);
				}
			}
			$pSheet->refreshColumnDimensions();
		}


		// Update worksheet: row dimensions
		$aRowDimensions = array_reverse($pSheet->getRowDimensions(), true);
		if (count($aRowDimensions) > 0) {
			foreach ($aRowDimensions as $objRowDimension) {
				$newReference = $this->updateCellReference('A' . $objRowDimension->getRowIndex(), $pBefore, $pNumCols, $pNumRows);
				list(, $newReference) = PHPExcel_Cell::coordinateFromString($newReference);
				if ($objRowDimension->getRowIndex() != $newReference) {
					$objRowDimension->setRowIndex($newReference);
				}
			}
			$pSheet->refreshRowDimensions();

			$copyDimension = $pSheet->getRowDimension($beforeRow - 1);
			for ($i = $beforeRow; $i <= $beforeRow - 1 + $pNumRows; ++$i) {
				$newDimension = $pSheet->getRowDimension($i);
				$newDimension->setRowHeight($copyDimension->getRowHeight());
				$newDimension->setVisible($copyDimension->getVisible());
				$newDimension->setOutlineLevel($copyDimension->getOutlineLevel());
				$newDimension->setCollapsed($copyDimension->getCollapsed());
			}
		}


		// Update worksheet: breaks
		$aBreaks = array_reverse($pSheet->getBreaks(), true);
		foreach ($aBreaks as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->setBreak( $newReference, $value );
				$pSheet->setBreak( $key, PHPExcel_Worksheet::BREAK_NONE );
			}
		}


		// Update worksheet: hyperlinks
		$aHyperlinkCollection = array_reverse($pSheet->getHyperlinkCollection(), true);
		foreach ($aHyperlinkCollection as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->setHyperlink( $newReference, $value );
				$pSheet->setHyperlink( $key, null );
			}
		}


		// Update worksheet: data validations
		$aDataValidationCollection = array_reverse($pSheet->getDataValidationCollection(), true);
		foreach ($aDataValidationCollection as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->setDataValidation( $newReference, $value );
				$pSheet->setDataValidation( $key, null );
			}
		}


		// Update worksheet: merge cells
		$aMergeCells = $pSheet->getMergeCells();
		$aNewMergeCells = array(); // the new array of all merge cells
		foreach ($aMergeCells as $key => &$value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			$aNewMergeCells[$newReference] = $newReference;
		}
		$pSheet->setMergeCells($aNewMergeCells); // replace the merge cells array


		// Update worksheet: protected cells
		$aProtectedCells = array_reverse($pSheet->getProtectedCells(), true);
		foreach ($aProtectedCells as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->protectCells( $newReference, $value, true );
				$pSheet->unprotectCells( $key );
			}
		}


		// Update worksheet: autofilter
		if ($pSheet->getAutoFilter() != '') {
			$pSheet->setAutoFilter( $this->updateCellReference($pSheet->getAutoFilter(), $pBefore, $pNumCols, $pNumRows) );
		}


		// Update worksheet: freeze pane
		if ($pSheet->getFreezePane() != '') {
			$pSheet->freezePane( $this->updateCellReference($pSheet->getFreezePane(), $pBefore, $pNumCols, $pNumRows) );
		}


		// Page setup
		if ($pSheet->getPageSetup()->isPrintAreaSet()) {
			$pSheet->getPageSetup()->setPrintArea( $this->updateCellReference($pSheet->getPageSetup()->getPrintArea(), $pBefore, $pNumCols, $pNumRows) );
		}


		// Update worksheet: drawings
		$aDrawings = $pSheet->getDrawingCollection();
		foreach ($aDrawings as $objDrawing) {
			$newReference = $this->updateCellReference($objDrawing->getCoordinates(), $pBefore, $pNumCols, $pNumRows);
			if ($objDrawing->getCoordinates() != $newReference) {
				$objDrawing->setCoordinates($newReference);
			}
		}


		// Update workbook: named ranges
		if (count($pSheet->getParent()->getNamedRanges()) > 0) {
			foreach ($pSheet->getParent()->getNamedRanges() as $namedRange) {
				if ($namedRange->getWorksheet()->getHashCode() == $pSheet->getHashCode()) {
					$namedRange->setRange(
						$this->updateCellReference($namedRange->getRange(), $pBefore, $pNumCols, $pNumRows)
					);
				}
			}
		}

		// Garbage collect
		$pSheet->garbageCollect();
	}

	/**
	 * Update references within formulas
	 *
	 * @param	string	$pFormula	Formula to update
	 * @param	int		$pBefore	Insert before this one
	 * @param	int		$pNumCols	Number of columns to insert
	 * @param	int		$pNumRows	Number of rows to insert
	 * @return	string	Updated formula
	 * @throws	Exception
	 */
	public function updateFormulaReferences($pFormula = '', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
		// Parse formula into a tree of tokens
		$tokenisedFormula = PHPExcel_Calculation::getInstance()->parseFormula($pFormula);

		$newCellTokens = $cellTokens = array();
		$adjustCount = 0;
		//	Build the translation table of cell tokens
		foreach($tokenisedFormula as $token) {
			$token = $token['value'];
			if (preg_match('/^'.PHPExcel_Calculation::CALCULATION_REGEXP_CELLREF.'$/i', $token, $matches)) {
				list($column,$row) = PHPExcel_Cell::coordinateFromString($token);
				//	Max worksheet size is 1,048,576 rows by 16,384 columns in Excel 2007, so our adjustments need to be at least one digit more
				$column = PHPExcel_Cell::columnIndexFromString($column) + 100000;
				$row += 10000000;
				$cellIndex = $column.$row;
				if (!isset($cellTokens[$cellIndex])) {
					$newReference = $this->updateCellReference($token, $pBefore, $pNumCols, $pNumRows);
					if ($newReference !== $token) {
						$newCellTokens[$cellIndex] = preg_quote($newReference);
						$cellTokens[$cellIndex] = '/(?<![A-Z])'.preg_quote($token).'(?!\d)/i';
						++$adjustCount;
					}
				}
			}
		}
		if ($adjustCount == 0) {
			return $pFormula;
		}
		krsort($cellTokens);
		krsort($newCellTokens);

		//	Update cell references in the formula
		$formulaBlocks = explode('"',$pFormula);
		foreach($formulaBlocks as $i => &$formulaBlock) {
			//	Only count/replace in alternate array entries
			if (($i % 2) == 0) {
				$formulaBlock = preg_replace($cellTokens,$newCellTokens,$formulaBlock);
			}
		}
		unset($formulaBlock);

		//	Then rebuild the formula string
		return implode('"',$formulaBlocks);
	}

	/**
	 * Update cell reference
	 *
	 * @param	string	$pCellRange			Cell range
	 * @param	int		$pBefore			Insert before this one
	 * @param	int		$pNumCols			Number of columns to increment
	 * @param	int		$pNumRows			Number of rows to increment
	 * @return	string	Updated cell range
	 * @throws	Exception
	 */
	public function updateCellReference($pCellRange = 'A1', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
		// Is it in another worksheet? Will not have to update anything.
		if (strpos($pCellRange, "!") !== false) {
			return $pCellRange;
		// Is it a range or a single cell?
		} elseif (strpos($pCellRange, ':') === false && strpos($pCellRange, ',') === false) {
			// Single cell
			return $this->_updateSingleCellReference($pCellRange, $pBefore, $pNumCols, $pNumRows);
		} elseif (strpos($pCellRange, ':') !== false || strpos($pCellRange, ',') !== false) {
			// Range
			return $this->_updateCellRange($pCellRange, $pBefore, $pNumCols, $pNumRows);
		} else {
			// Return original
			return $pCellRange;
		}
	}

	/**
	 * Update named formulas (i.e. containing worksheet references / named ranges)
	 *
	 * @param PHPExcel $pPhpExcel	Object to update
	 * @param string $oldName		Old name (name to replace)
	 * @param string $newName		New name
	 */
	public function updateNamedFormulas(PHPExcel $pPhpExcel, $oldName = '', $newName = '') {
		if ($oldName == '') {
			return;
		}

		foreach ($pPhpExcel->getWorksheetIterator() as $sheet) {
			foreach ($sheet->getCellCollection(false) as $cellID) {
				$cell = $sheet->getCell($cellID);
				if (!is_null($cell) && $cell->getDataType() == PHPExcel_Cell_DataType::TYPE_FORMULA) {
					$formula = $cell->getValue();
					if (strpos($formula, $oldName) !== false) {
						$formula = str_replace("'" . $oldName . "'!", "'" . $newName . "'!", $formula);
						$formula = str_replace($oldName . "!", $newName . "!", $formula);
						$cell->setValueExplicit($formula, PHPExcel_Cell_DataType::TYPE_FORMULA);
					}
				}
			}
		}
	}

	/**
	 * Update cell range
	 *
	 * @param	string	$pCellRange			Cell range
	 * @param	int		$pBefore			Insert before this one
	 * @param	int		$pNumCols			Number of columns to increment
	 * @param	int		$pNumRows			Number of rows to increment
	 * @return	string	Updated cell range
	 * @throws	Exception
	 */
	private function _updateCellRange($pCellRange = 'A1:A1', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
		if (strpos($pCellRange,':') !== false || strpos($pCellRange, ',') !== false) {
			// Update range
			$range = PHPExcel_Cell::splitRange($pCellRange);
			for ($i = 0; $i < count($range); ++$i) {
				for ($j = 0; $j < count($range[$i]); ++$j) {
					$range[$i][$j] = $this->_updateSingleCellReference($range[$i][$j], $pBefore, $pNumCols, $pNumRows);
				}
			}

			// Recreate range string
			return PHPExcel_Cell::buildRange($range);
		} else {
			throw new Exception("Only cell ranges may be passed to this method.");
		}
	}

	/**
	 * Update single cell reference
	 *
	 * @param	string	$pCellReference		Single cell reference
	 * @param	int		$pBefore			Insert before this one
	 * @param	int		$pNumCols			Number of columns to increment
	 * @param	int		$pNumRows			Number of rows to increment
	 * @return	string	Updated cell reference
	 * @throws	Exception
	 */
	private function _updateSingleCellReference($pCellReference = 'A1', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
		if (strpos($pCellReference, ':') === false && strpos($pCellReference, ',') === false) {
			// Get coordinates of $pBefore
			$beforeColumn	= 'A';
			$beforeRow		= 1;
			list($beforeColumn, $beforeRow) = PHPExcel_Cell::coordinateFromString( $pBefore );

			// Get coordinates
			$newColumn	= 'A';
			$newRow	= 1;
			list($newColumn, $newRow) = PHPExcel_Cell::coordinateFromString( $pCellReference );

			// Make sure the reference can be used
			if ($newColumn == '' && $newRow == '')
			{
				return $pCellReference;
			}

			// Verify which parts should be updated
			$updateColumn = (PHPExcel_Cell::columnIndexFromString($newColumn) >= PHPExcel_Cell::columnIndexFromString($beforeColumn))
							&& (strpos($newColumn, '$') === false)
							&& (strpos($beforeColumn, '$') === false);

			$updateRow = ($newRow >= $beforeRow)
							&& (strpos($newRow, '$') === false)
							&& (strpos($beforeRow, '$') === false);

			// Create new column reference
			if ($updateColumn) {
				$newColumn	= PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($newColumn) - 1 + $pNumCols );
			}

			// Create new row reference
			if ($updateRow) {
				$newRow	= $newRow + $pNumRows;
			}

			// Return new reference
			return $newColumn . $newRow;
		} else {
			throw new Exception("Only single cell references may be passed to this method.");
		}
	}

	/**
	 * __clone implementation. Cloning should not be allowed in a Singleton!
	 *
	 * @throws	Exception
	 */
	public final function __clone() {
		throw new Exception("Cloning a Singleton is not allowed!");
	}
}
