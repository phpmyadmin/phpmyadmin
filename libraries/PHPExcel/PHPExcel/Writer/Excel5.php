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
 * @package    PHPExcel_Writer_Excel5
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.7.0, 2009-08-10
 */


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	/**
	 * @ignore
	 */
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../../');
}

/** PHPExcel_IWriter */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/IWriter.php';

/** PHPExcel_Cell */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell.php';

/** PHPExcel_HashTable */
require_once PHPEXCEL_ROOT . 'PHPExcel/HashTable.php';

/** PHPExcel_Shared_OLE_PPS_Root */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/OLE/OLE_Root.php';

/** PHPExcel_Shared_OLE_PPS_File */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/OLE/OLE_File.php';

/** PHPExcel_Writer_Excel5_Parser */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/Excel5/Parser.php';

/** PHPExcel_Writer_Excel5_Workbook */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/Excel5/Workbook.php';


/**
 * PHPExcel_Writer_Excel5
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer_Excel5
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_Excel5 implements PHPExcel_Writer_IWriter
{
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
	 * Temporary storage directory
	 *
	 * @var string
	 */
	private $_tempDir = '';

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
		$this->_phpExcel		= $phpExcel;
		$this->_BIFF_version	= 0x0600;
		$this->_tempDir			= '';
		
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

		// check mbstring.func_overload
		if (ini_get('mbstring.func_overload') != 0) {
			throw new Exception('Multibyte string function overloading in PHP must be disabled.');
		}

		// garbage collect
		$this->_phpExcel->garbageCollect();

		$saveDateReturnType = PHPExcel_Calculation_Functions::getReturnDateType();
		PHPExcel_Calculation_Functions::setReturnDateType(PHPExcel_Calculation_Functions::RETURNDATE_EXCEL);

		// Initialise workbook writer
		$this->_writerWorkbook = new PHPExcel_Writer_Excel5_Workbook($this->_phpExcel, $this->_BIFF_version,
					$this->_str_total, $this->_str_unique, $this->_str_table, $this->_parser, $this->_tempDir);

		// Initialise worksheet writers
		$countSheets = count($this->_phpExcel->getAllSheets());
		for ($i = 0; $i < $countSheets; ++$i) {
			$phpSheet  = $this->_phpExcel->getSheet($i);
			
			$writerWorksheet = new PHPExcel_Writer_Excel5_Worksheet($this->_BIFF_version,
									   $this->_str_total, $this->_str_unique,
									   $this->_str_table,
									   $this->_parser, $this->_tempDir,
									   $phpSheet);
			$this->_writerWorksheets[$i] = $writerWorksheet;
		}

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

		if ($this->_tempDir != '') {
			$OLE->setTempDir($this->_tempDir);
		}
		$res = $OLE->init();

		// Write the worksheet streams before the global workbook stream,
		// because the byte sizes of these are needed in the global workbook stream
		$worksheetSizes = array();
		for ($i = 0; $i < $countSheets; ++$i) {
			$this->_writerWorksheets[$i]->close();
			$worksheetSizes[] = $this->_writerWorksheets[$i]->_datasize;
		}

		// add binary data for global workbook stream
		$OLE->append( $this->_writerWorkbook->writeWorkbook($worksheetSizes) );

		// add binary data for sheet streams
		for ($i = 0; $i < $countSheets; ++$i) {
			while ( ($tmp = $this->_writerWorksheets[$i]->getData()) !== false ) {
				$OLE->append($tmp);
			}
		}

		$root = new PHPExcel_Shared_OLE_PPS_Root(time(), time(), array($OLE));
		if ($this->_tempDir != '') {
			$root->setTempDir($this->_tempDir);
		}

		// save the OLE file
		$res = $root->save($pFilename);

		PHPExcel_Calculation_Functions::setReturnDateType($saveDateReturnType);

		// clean up
		foreach ($this->_writerWorksheets as $sheet) {
			$sheet->cleanup();
		}
	}

	/**
	 * Get temporary storage directory
	 *
	 * @return string
	 */
	public function getTempDir() {
		return $this->_tempDir;
	}

	/**
	 * Set temporary storage directory
	 *
	 * @param	string	$pValue		Temporary storage directory
	 * @throws	Exception	Exception when directory does not exist
	 * @return PHPExcel_Writer_Excel5
	 */
	public function setTempDir($pValue = '') {
		if (is_dir($pValue)) {
			$this->_tempDir = $pValue;
		} else {
			throw new Exception("Directory does not exist: $pValue");
		}
		return $this;
	}

}
