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
 * @package    PHPExcel_Style
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

/** PHPExcel_IComparable */
require_once PHPEXCEL_ROOT . 'PHPExcel/IComparable.php';

/** PHPExcel_Shared_Date */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/Date.php';

/** PHPExcel_Calculation_Functions */
require_once PHPEXCEL_ROOT . 'PHPExcel/Calculation/Functions.php';


/**
 * PHPExcel_Style_NumberFormat
 *
 * @category   PHPExcel
 * @package    PHPExcel_Style
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Style_NumberFormat implements PHPExcel_IComparable
{
	/* Pre-defined formats */
	const FORMAT_GENERAL					= 'General';

	const FORMAT_TEXT						= '@';

	const FORMAT_NUMBER						= '0';
	const FORMAT_NUMBER_00					= '0.00';
	const FORMAT_NUMBER_COMMA_SEPARATED1	= '#,##0.00';
	const FORMAT_NUMBER_COMMA_SEPARATED2	= '#,##0.00_-';

	const FORMAT_PERCENTAGE					= '0%';
	const FORMAT_PERCENTAGE_00				= '0.00%';

	const FORMAT_DATE_YYYYMMDD2				= 'yyyy-mm-dd';
	const FORMAT_DATE_YYYYMMDD				= 'yy-mm-dd';
	const FORMAT_DATE_DDMMYYYY				= 'dd/mm/yy';
	const FORMAT_DATE_DMYSLASH				= 'd/m/y';
	const FORMAT_DATE_DMYMINUS				= 'd-m-y';
	const FORMAT_DATE_DMMINUS				= 'd-m';
	const FORMAT_DATE_MYMINUS				= 'm-y';
	const FORMAT_DATE_XLSX14				= 'mm-dd-yy';
	const FORMAT_DATE_XLSX15				= 'd-mmm-yy';
	const FORMAT_DATE_XLSX16				= 'd-mmm';
	const FORMAT_DATE_XLSX17				= 'mmm-yy';
	const FORMAT_DATE_XLSX22				= 'm/d/yy h:mm';
	const FORMAT_DATE_DATETIME				= 'd/m/y h:mm';
	const FORMAT_DATE_TIME1					= 'h:mm AM/PM';
	const FORMAT_DATE_TIME2					= 'h:mm:ss AM/PM';
	const FORMAT_DATE_TIME3					= 'h:mm';
	const FORMAT_DATE_TIME4					= 'h:mm:ss';
	const FORMAT_DATE_TIME5					= 'mm:ss';
	const FORMAT_DATE_TIME6					= 'h:mm:ss';
	const FORMAT_DATE_TIME7					= 'i:s.S';
	const FORMAT_DATE_TIME8					= 'h:mm:ss;@';
	const FORMAT_DATE_YYYYMMDDSLASH			= 'yy/mm/dd;@';

	const FORMAT_CURRENCY_USD_SIMPLE		= '"$"#,##0.00_-';
	const FORMAT_CURRENCY_USD				= '$#,##0_-';
	const FORMAT_CURRENCY_EUR_SIMPLE		= '[$EUR ]#,##0.00_-';

	/**
	 * Excel built-in number formats
	 *
	 * @var array
	 */
	private static $_builtInFormats;

	/**
	 * Excel built-in number formats (flipped, for faster lookups)
	 *
	 * @var array
	 */
	private static $_flippedBuiltInFormats;

	/**
	 * Format Code
	 *
	 * @var string
	 */
	private $_formatCode;

	/**
	 * Built-in format Code
	 *
	 * @var string
	 */
	private $_builtInFormatCode;

	/**
	 * Parent Borders
	 *
	 * @var _parentPropertyName string
	 */
	private $_parentPropertyName;

	/**
	 * Supervisor?
	 *
	 * @var boolean
	 */
	private $_isSupervisor;

	/**
	 * Parent. Only used for supervisor
	 *
	 * @var PHPExcel_Style
	 */
	private $_parent;

	/**
     * Create a new PHPExcel_Style_NumberFormat
     */
    public function __construct($isSupervisor = false)
    {
    	// Supervisor?
		$this->_isSupervisor = $isSupervisor;

    	// Initialise values
    	$this->_formatCode			= PHPExcel_Style_NumberFormat::FORMAT_GENERAL;
    	$this->_builtInFormatCode	= 0;
    }

	/**
	 * Bind parent. Only used for supervisor
	 *
	 * @param PHPExcel_Style $parent
	 * @return PHPExcel_Style_NumberFormat
	 */
	public function bindParent($parent)
	{
		$this->_parent = $parent;
	}

	/**
	 * Is this a supervisor or a real style component?
	 *
	 * @return boolean
	 */
	public function getIsSupervisor()
	{
		return $this->_isSupervisor;
	}

	/**
	 * Get the shared style component for the currently active cell in currently active sheet.
	 * Only used for style supervisor
	 *
	 * @return PHPExcel_Style_NumberFormat
	 */
	public function getSharedComponent()
	{
		return $this->_parent->getSharedComponent()->getNumberFormat();
	}

	/**
	 * Get the currently active sheet. Only used for supervisor
	 *
	 * @return PHPExcel_Worksheet
	 */
	public function getActiveSheet()
	{
		return $this->_parent->getActiveSheet();
	}

	/**
	 * Get the currently active cell coordinate in currently active sheet.
	 * Only used for supervisor
	 *
	 * @return string E.g. 'A1'
	 */
	public function getXSelectedCells()
	{
		return $this->getActiveSheet()->getXSelectedCells();
	}

	/**
	 * Get the currently active cell coordinate in currently active sheet.
	 * Only used for supervisor
	 *
	 * @return string E.g. 'A1'
	 */
	public function getXActiveCell()
	{
		return $this->getActiveSheet()->getXActiveCell();
	}

	/**
	 * Build style array from subcomponents
	 *
	 * @param array $array
	 * @return array
	 */
	public function getStyleArray($array)
	{
		return array('numberformat' => $array);
	}

    /**
     * Apply styles from array
     *
     * <code>
     * $objPHPExcel->getActiveSheet()->getStyle('B2')->getNumberFormat()->applyFromArray(
     * 		array(
     * 			'code' => PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
     * 		)
     * );
     * </code>
     *
     * @param	array	$pStyles	Array containing style information
     * @throws	Exception
     * @return PHPExcel_Style_NumberFormat
     */
	public function applyFromArray($pStyles = null) {
		if (is_array($pStyles)) {
			if ($this->_isSupervisor) {
				$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($this->getStyleArray($pStyles));
			} else {
				if (array_key_exists('code', $pStyles)) {
					$this->setFormatCode($pStyles['code']);
				}
			}
		} else {
			throw new Exception("Invalid style array passed.");
		}
		return $this;
	}

    /**
     * Get Format Code
     *
     * @return string
     */
    public function getFormatCode() {
		if ($this->_isSupervisor) {
			return $this->getSharedComponent()->getFormatCode();
		}
    	if ($this->_builtInFormatCode !== false)
    	{
    		return self::builtInFormatCode($this->_builtInFormatCode);
    	}
    	return $this->_formatCode;
    }

    /**
     * Set Format Code
     *
     * @param string $pValue
     * @return PHPExcel_Style_NumberFormat
     */
    public function setFormatCode($pValue = PHPExcel_Style_NumberFormat::FORMAT_GENERAL) {
        if ($pValue == '') {
    		$pValue = PHPExcel_Style_NumberFormat::FORMAT_GENERAL;
    	}
		if ($this->_isSupervisor) {
			$styleArray = $this->getStyleArray(array('code' => $pValue));
			$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($styleArray);
		} else {
			$this->_formatCode = $pValue;
			$this->_builtInFormatCode = self::builtInFormatCodeIndex($pValue);
		}
		return $this;
    }

	/**
     * Get Built-In Format Code
     *
     * @return int
     */
    public function getBuiltInFormatCode() {
		if ($this->_isSupervisor) {
			return $this->getSharedComponent()->getBuiltInFormatCode();
		}
    	return $this->_builtInFormatCode;
    }

    /**
     * Set Built-In Format Code
     *
     * @param int $pValue
     * @return PHPExcel_Style_NumberFormat
     */
    public function setBuiltInFormatCode($pValue = 0) {

		if ($this->_isSupervisor) {
			$styleArray = $this->getStyleArray(array('code' => self::builtInFormatCode($pValue)));
			$this->getActiveSheet()->getStyle($this->getXSelectedCells())->applyFromArray($styleArray);
		} else {
			$this->_builtInFormatCode = $pValue;
			$this->_formatCode = self::builtInFormatCode($pValue);
		}
		return $this;
    }

    /**
     * Fill built-in format codes
     */
    private static function fillBuiltInFormatCodes()
    {
    	// Built-in format codes
    	if (is_null(self::$_builtInFormats)) {
			self::$_builtInFormats = array();

			// General
			self::$_builtInFormats[0] = 'General';
			self::$_builtInFormats[1] = '0';
			self::$_builtInFormats[2] = '0.00';
			self::$_builtInFormats[3] = '#,##0';
			self::$_builtInFormats[4] = '#,##0.00';

			self::$_builtInFormats[9] = '0%';
			self::$_builtInFormats[10] = '0.00%';
			self::$_builtInFormats[11] = '0.00E+00';
			self::$_builtInFormats[12] = '# ?/?';
			self::$_builtInFormats[13] = '# ??/??';
			self::$_builtInFormats[14] = 'mm-dd-yy';
			self::$_builtInFormats[15] = 'd-mmm-yy';
			self::$_builtInFormats[16] = 'd-mmm';
			self::$_builtInFormats[17] = 'mmm-yy';
			self::$_builtInFormats[18] = 'h:mm AM/PM';
			self::$_builtInFormats[19] = 'h:mm:ss AM/PM';
			self::$_builtInFormats[20] = 'h:mm';
			self::$_builtInFormats[21] = 'h:mm:ss';
			self::$_builtInFormats[22] = 'm/d/yy h:mm';

			self::$_builtInFormats[37] = '#,##0 ;(#,##0)';
			self::$_builtInFormats[38] = '#,##0 ;[Red](#,##0)';
			self::$_builtInFormats[39] = '#,##0.00;(#,##0.00)';
			self::$_builtInFormats[40] = '#,##0.00;[Red](#,##0.00)';

			self::$_builtInFormats[44] = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
			self::$_builtInFormats[45] = 'mm:ss';
			self::$_builtInFormats[46] = '[h]:mm:ss';
			self::$_builtInFormats[47] = 'mmss.0';
			self::$_builtInFormats[48] = '##0.0E+0';
			self::$_builtInFormats[49] = '@';

			// CHT
			self::$_builtInFormats[27] = '[$-404]e/m/d';
			self::$_builtInFormats[30] = 'm/d/yy';
			self::$_builtInFormats[36] = '[$-404]e/m/d';
			self::$_builtInFormats[50] = '[$-404]e/m/d';
			self::$_builtInFormats[57] = '[$-404]e/m/d';

			// THA
			self::$_builtInFormats[59] = 't0';
			self::$_builtInFormats[60] = 't0.00';
			self::$_builtInFormats[61] = 't#,##0';
			self::$_builtInFormats[62] = 't#,##0.00';
			self::$_builtInFormats[67] = 't0%';
			self::$_builtInFormats[68] = 't0.00%';
			self::$_builtInFormats[69] = 't# ?/?';
			self::$_builtInFormats[70] = 't# ??/??';

			// Flip array (for faster lookups)
			self::$_flippedBuiltInFormats = array_flip(self::$_builtInFormats);
    	}
    }

    /**
     * Get built-in format code
     *
     * @param	int		$pIndex
     * @return	string
     */
    public static function builtInFormatCode($pIndex) {
    	// Clean parameter
		$pIndex = intval($pIndex);

		// Ensure built-in format codes are available
    	self::fillBuiltInFormatCodes();

		// Lookup format code
		if (array_key_exists($pIndex, self::$_builtInFormats)) {
			return self::$_builtInFormats[$pIndex];
		}

    	return '';
    }

    /**
     * Get built-in format code index
     *
     * @param	string		$formatCode
     * @return	int|boolean
     */
    public static function builtInFormatCodeIndex($formatCode) {
    	// Ensure built-in format codes are available
    	self::fillBuiltInFormatCodes();

		// Lookup format code
		if (array_key_exists($formatCode, self::$_flippedBuiltInFormats)) {
			return self::$_flippedBuiltInFormats[$formatCode];
		}

    	return false;
    }

	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */
	public function getHashCode() {
		if ($this->_isSupervisor) {
			return $this->getSharedComponent()->getHashCode();
		}
    	return md5(
    		  $this->_formatCode
    		. $this->_builtInFormatCode
    		. __CLASS__
    	);
    }

    /**
     * Hash index
     *
     * @var string
     */
    private $_hashIndex;

	/**
	 * Get hash index
	 *
	 * Note that this index may vary during script execution! Only reliable moment is
	 * while doing a write of a workbook and when changes are not allowed.
	 *
	 * @return string	Hash index
	 */
	public function getHashIndex() {
		return $this->_hashIndex;
	}

	/**
	 * Set hash index
	 *
	 * Note that this index may vary during script execution! Only reliable moment is
	 * while doing a write of a workbook and when changes are not allowed.
	 *
	 * @param string	$value	Hash index
	 */
	public function setHashIndex($value) {
		$this->_hashIndex = $value;
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Convert a value in a pre-defined format to a PHP string
	 *
	 * @param mixed 	$value		Value to format
	 * @param string 	$format		Format code
	 * @return string	Formatted string
	 */
	public static function toFormattedString($value = '', $format = '') {
		// For now we do not treat strings although part 4 of a format code affects strings
		if (!is_numeric($value)) return $value;

		// For 'General' format code, we just pass the value although this is not entirely the way Excel does it,
		// it seems to round numbers to a total of 10 digits.
		if ($format === 'General') {
			return $value;
		}

		// Get the parts, there can be up to four parts
		$parts = explode(';', $format);

		// We should really fetch the relevant part depending on whether we have a positive number,
		// negative number, zero, or text. But for now we just use first part
		$format = $parts[0];

		if (preg_match("/^[hmsdy]/i", $format)) { // custom datetime format
			// dvc: convert Excel formats to PHP date formats
			// first remove escapes related to non-format characters

			// OpenOffice.org uses upper-case number formats, e.g. 'YYYY', convert to lower-case
			$format = strtolower($format);

			$format = str_replace('\\', '', $format);

			//	4-digit year
			$format = str_replace('yyyy', 'Y', $format);
			//	2-digit year
			$format = str_replace('yy', 'y', $format);
			//	first letter of month - no php equivalent
			$format = str_replace('mmmmm', 'M', $format);
			//	full month name
			$format = str_replace('mmmm', 'F', $format);
			//	short month name
			$format = str_replace('mmm', 'M', $format);
			//	mm is minutes if time or month w/leading zero
			$format = str_replace(':mm', ':i', $format);
			//	tmp place holder
			$format = str_replace('mm', 'x', $format);
			//	month no leading zero
			$format = str_replace('m', 'n', $format);
			//	month leading zero
			$format = str_replace('x', 'm', $format);
			//	12-hour suffix
			$format = str_replace('am/pm', 'A', $format);
			//	full day of week name
			$format = str_replace('dddd', 'l', $format);
			//	short day of week name
			$format = str_replace('ddd', 'D', $format);
			//	tmp place holder
			$format = str_replace('dd', 'x', $format);
			//	days no leading zero
			$format = str_replace('d', 'j', $format);
			//	days leading zero
			$format = str_replace('x', 'd', $format);
			//	seconds
			$format = str_replace('ss', 's', $format);
			//	fractional seconds - no php equivalent
			$format = str_replace('.s', '', $format);

			if (!strpos($format,'A')) { // 24-hour format
				$format = str_replace('h', 'H', $format);
			}

			return gmdate($format, PHPExcel_Shared_Date::ExcelToPHP($value));

		} else if (preg_match('/%$/', $format)) { // % number format
			if ($format === self::FORMAT_PERCENTAGE) {
				return round( (100 * $value), 0) . '%';
			}
			if (preg_match('/\.[#0]+/i', $format, $m)) {
				$s = substr($m[0], 0, 1) . (strlen($m[0]) - 1);
				$format = str_replace($m[0], $s, $format);
			}
			if (preg_match('/^[#0]+/', $format, $m)) {
				$format = str_replace($m[0], strlen($m[0]), $format);
			}
			$format = '%' . str_replace('%', 'f%%', $format);

			return sprintf($format, 100 * $value);

		} else {
			if (preg_match ("/^([0-9.,-]+)$/", $value)) {
	 			if ($format === self::FORMAT_CURRENCY_EUR_SIMPLE) {
	 				return 'EUR ' . sprintf('%1.2f', $value);

				} else {
					// In Excel formats, "_" is used to add spacing, which we can't do in HTML
					$format = preg_replace('/_./', '', $format);

					// Some non-number characters are escaped with \, which we don't need
					$format = preg_replace("/\\\\/", '', $format);

					// Some non-number strings are quoted, so we'll get rid of the quotes
					$format = preg_replace('/"/', '', $format);

					// TEMPORARY - Convert # to 0
					$format = preg_replace('/\\#/', '0', $format);

					// Find out if we need thousands separator
					$useThousands = preg_match('/,/', $format);
					if ($useThousands) {
						$format = preg_replace('/,/', '', $format);
					}

					if (preg_match('/0?.*\?\/\?/', $format, $m)) {
						//echo 'Format mask is fractional '.$format.' <br />';
						$sign = ($value < 0) ? '-' : '';

						$integerPart = floor(abs($value));
						$decimalPart = trim(fmod(abs($value),1),'0.');
						$decimalLength = strlen($decimalPart);
						$decimalDivisor = pow(10,$decimalLength);

						$GCD = PHPExcel_Calculation_Functions::GCD($decimalPart,$decimalDivisor);

						$adjustedDecimalPart = $decimalPart/$GCD;
						$adjustedDecimalDivisor = $decimalDivisor/$GCD;

						if (strpos($format,'0') !== false) {
							$value = "$sign$integerPart $adjustedDecimalPart/$adjustedDecimalDivisor";
						} else {
							$adjustedDecimalPart += $integerPart * $adjustedDecimalDivisor;
							$value = "$sign$adjustedDecimalPart/$adjustedDecimalDivisor";
						}

					} else {
						// Handle the number itself
						$number_regex = "/(\d+)(\.?)(\d*)/";
						if (preg_match($number_regex, $format, $matches)) {
							$left = $matches[1];
							$dec = $matches[2];
							$right = $matches[3];
							if ($useThousands) {
								$localeconv = localeconv();
								if (($localeconv['thousands_sep'] == '') || ($localeconv['decimal_point'] == '')) {
									$value = number_format($value, strlen($right), $localeconv['mon_decimal_point'], $localeconv['mon_thousands_sep']);
								} else {
									$value = number_format($value, strlen($right), $localeconv['decimal_point'], $localeconv['thousands_sep']);
								}
							} else {
								$sprintf_pattern = "%1." . strlen($right) . "f";
								$value = sprintf($sprintf_pattern, $value);
							}
							$value = preg_replace($number_regex, $value, $format);
						}
					}

					return $value;

				}
			}

			return $value;
		}
	}
}
