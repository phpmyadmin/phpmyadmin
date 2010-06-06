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
 * @package    PHPExcel_Writer_Excel5
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.7.3c, 2010-06-01
 */


/**
 * PHPExcel_Writer_Excel5
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer_Excel5
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_Excel5 implements PHPExcel_Writer_IWriter
{
	/**
	 * Pre-calculate formulas
	 *
	 * @var boolean
	 */
	private $_preCalculateFormulas;

	/**
	 * PHPExcel object
	 *
	 * @var PHPExcel
	 */
	private $_phpExcel;

	/**
	 * The BIFF version of the written Excel file, BIFF5 = 0x0500, BIFF8 = 0x0600
	 *
	 * @var integer
	 */
	private $_BIFF_version;

	/**
	 * Total number of shared strings in workbook
	 *
	 * @var int
	 */
	private $_str_total;

	/**
	 * Number of unique shared strings in workbook
	 *
	 * @var int
	 */
	private $_str_unique;

	/**
	 * Array of unique shared strings in workbook
	 *
	 * @var array
	 */
	private $_str_table;

	/**
	 * Color cache. Mapping between RGB value and color index.
	 *
	 * @var array
	 */
	private $_colors;

	/**
	 * Formula parser
	 *
	 * @var PHPExcel_Writer_Excel5_Parser
	 */
	private $_parser;


	/**
	 * Create a new PHPExcel_Writer_Excel5
	 *
	 * @param	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {
		$this->_preCalculateFormulas = true;
		$this->_phpExcel		= $phpExcel;
		$this->_BIFF_version	= 0x0600;

		$this->_str_total       = 0;
		$this->_str_unique      = 0;
		$this->_str_table       = array();
		$this->_parser          = new PHPExcel_Writer_Excel5_Parser($this->_BIFF_version);

	}

	/**
	 * Save PHPExcel to file
	 *
	 * @param	string		$pFileName
	 * @throws	Exception
	 */
	public function save($pFilename = null) {

		// garbage collect
		$this->_phpExcel->garbageCollect();

		$saveDateReturnType = PHPExcel_Calculation_Functions::getReturnDateType();
		PHPExcel_Calculation_Functions::setReturnDateType(PHPExcel_Calculation_Functions::RETURNDATE_EXCEL);

		// initialize colors array
		$this->_colors          = array();

		// Initialise workbook writer
		$this->_writerWorkbook = new PHPExcel_Writer_Excel5_Workbook($this->_phpExcel, $this->_BIFF_version,
					$this->_str_total, $this->_str_unique, $this->_str_table, $this->_colors, $this->_parser);

		// add 15 identical cell style Xfs
		// for now, we use the first cellXf instead of cellStyleXf
		$cellXfCollection = $this->_phpExcel->getCellXfCollection();
		for ($i = 0; $i < 15; ++$i) {
			$this->_writerWorkbook->addXfWriter($cellXfCollection[0], true);
		}

		// add all the cell Xfs
		foreach ($this->_phpExcel->getCellXfCollection() as $style) {
			$this->_writerWorkbook->addXfWriter($style, false);
		}

		// initialize OLE file
		$workbookStreamName = ($this->_BIFF_version == 0x0600) ? 'Workbook' : 'Book';
		$OLE = new PHPExcel_Shared_OLE_PPS_File(PHPExcel_Shared_OLE::Asc2Ucs($workbookStreamName));

		// Initialise worksheet writers
		$countSheets = $this->_phpExcel->getSheetCount();
		// Write the worksheet streams before the global workbook stream,
		// because the byte sizes of these are needed in the global workbook stream
		$worksheetSizes = array();
		for ($i = 0; $i < $countSheets; ++$i) {
			$this->_writerWorksheets[$i] = new PHPExcel_Writer_Excel5_Worksheet($this->_BIFF_version,
									   $this->_str_total, $this->_str_unique,
									   $this->_str_table, $this->_colors,
									   $this->_parser,
									   $this->_preCalculateFormulas,
									   $this->_phpExcel->getSheet($i));

			$this->_writerWorksheets[$i]->close();
			$worksheetSizes[] = $this->_writerWorksheets[$i]->_datasize;
		}

		// add binary data for global workbook stream
		$OLE->append( $this->_writerWorkbook->writeWorkbook($worksheetSizes) );

		// add binary data for sheet streams
		for ($i = 0; $i < $countSheets; ++$i) {
			$OLE->append($this->_writerWorksheets[$i]->getData());
		}

		$root = new PHPExcel_Shared_OLE_PPS_Root(time(), time(), array($OLE));
		// save the OLE file
		$res = $root->save($pFilename);

		PHPExcel_Calculation_Functions::setReturnDateType($saveDateReturnType);
	}

	/**
	 * Set temporary storage directory
	 *
	 * @deprecated
	 * @param	string	$pValue		Temporary storage directory
	 * @throws	Exception	Exception when directory does not exist
	 * @return PHPExcel_Writer_Excel5
	 */
	public function setTempDir($pValue = '') {
		return $this;
	}

	/**
	 * Get Pre-Calculate Formulas
	 *
	 * @return boolean
	 */
	public function getPreCalculateFormulas() {
		return $this->_preCalculateFormulas;
	}

	/**
	 * Set Pre-Calculate Formulas
	 *
	 * @param boolean $pValue	Pre-Calculate Formulas?
	 */
	public function setPreCalculateFormulas($pValue = true) {
		$this->_preCalculateFormulas = $pValue;
	}

}
