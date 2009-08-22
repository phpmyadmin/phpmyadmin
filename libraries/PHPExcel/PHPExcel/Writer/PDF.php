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
 * @package    PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.0, 2009-08-10
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

/** PHPExcel_Writer_HTML */
require_once PHPEXCEL_ROOT . 'PHPExcel/Writer/HTML.php';

/** PHPExcel_Cell */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell.php';

/** PHPExcel_RichText */
require_once PHPEXCEL_ROOT . 'PHPExcel/RichText.php';

/** PHPExcel_Shared_Drawing */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/Drawing.php';

/** PHPExcel_HashTable */
require_once PHPEXCEL_ROOT . 'PHPExcel/HashTable.php';

/** PHPExcel_Shared_PDF */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/PDF.php';


/**
 * PHPExcel_Writer_PDF
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_PDF extends PHPExcel_Writer_HTML implements PHPExcel_Writer_IWriter {
	/**
	 * Temporary storage directory
	 *
	 * @var string
	 */
	private $_tempDir = '';

	/**
	 * Create a new PHPExcel_Writer_PDF
	 *
	 * @param 	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {
		parent::__construct($phpExcel);
		$this->setUseInlineCss(true);
		$this->_tempDir = sys_get_temp_dir();
	}

	/**
	 * Save PHPExcel to file
	 *
	 * @param 	string 		$pFileName
	 * @throws 	Exception
	 */
	public function save($pFilename = null) {
		// garbage collect
		$this->_phpExcel->garbageCollect();

		$saveArrayReturnType = PHPExcel_Calculation::getArrayReturnType();
		PHPExcel_Calculation::setArrayReturnType(PHPExcel_Calculation::RETURN_ARRAY_AS_VALUE);

		// Open file
		$fileHandle = fopen($pFilename, 'w');
		if ($fileHandle === false) {
			throw new Exception("Could not open file $pFilename for writing.");
		}
		
		// Set PDF
		$this->_isPdf = true;

		// Build CSS
		$this->buildCSS(true);

		// Generate HTML
		$html = '';
		//$html .= $this->generateHTMLHeader(false);
		$html .= $this->generateSheetData();
		//$html .= $this->generateHTMLFooter();

    	// Default PDF paper size
    	$paperSize = 'A4';
    	$orientation = 'P';
    	    	
    	// Check for overrides
		if (is_null($this->getSheetIndex())) {
			$orientation = $this->_phpExcel->getSheet(0)->getPageSetup()->getOrientation() == 'landscape' ? 'L' : 'P';
		} else {
			$orientation = $this->_phpExcel->getSheet($this->getSheetIndex())->getPageSetup()->getOrientation() == 'landscape' ? 'L' : 'P';
		}

		// Create PDF
		$pdf = new TCPDF($orientation, 'pt', $paperSize);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->AddPage();
		$pdf->SetFont('freesans');
		$pdf->writeHTML($html);

		// Document info
		$pdf->SetTitle($this->_phpExcel->getProperties()->getTitle());
		$pdf->SetAuthor($this->_phpExcel->getProperties()->getCreator());
		$pdf->SetSubject($this->_phpExcel->getProperties()->getSubject());
		$pdf->SetKeywords($this->_phpExcel->getProperties()->getKeywords());
		$pdf->SetCreator($this->_phpExcel->getProperties()->getCreator());

		// Write to file
		fwrite($fileHandle, $pdf->output($pFilename, 'S'));

		// Close file
		fclose($fileHandle);

		PHPExcel_Calculation::setArrayReturnType($saveArrayReturnType);
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
	 * @param 	string	$pValue		Temporary storage directory
	 * @throws 	Exception	Exception when directory does not exist
	 * @return PHPExcel_Writer_PDF
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
