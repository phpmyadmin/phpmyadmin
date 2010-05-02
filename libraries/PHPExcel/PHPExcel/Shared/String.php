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
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.2, 2010-01-11
 */


/**
 * PHPExcel_Shared_String
 *
 * @category   PHPExcel
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Shared_String
{
	/**	Constants				*/
	/**	Regular Expressions		*/
	//	Fraction
	const STRING_REGEXP_FRACTION	= '(-?)(\d+)\s+(\d+\/\d+)';


	/**
	 * Control characters array
	 *
	 * @var string[]
	 */
	private static $_controlCharacters = array();

	/**
	 * Decimal separator
	 *
	 * @var string
	 */
	private static $_decimalSeparator;

	/**
	 * Thousands separator
	 *
	 * @var string
	 */
	private static $_thousandsSeparator;

	/**
	 * Is mbstring extension avalable?
	 *
	 * @var boolean
	 */
	private static $_isMbstringEnabled;

	/**
	 * Is iconv extension avalable?
	 *
	 * @var boolean
	 */
	private static $_isIconvEnabled;

	/**
	 * Build control characters array
	 */
	private static function _buildControlCharacters() {
		for ($i = 0; $i <= 31; ++$i) {
			if ($i != 9 && $i != 10 && $i != 13) {
				$find = '_x' . sprintf('%04s' , strtoupper(dechex($i))) . '_';
				$replace = chr($i);
				self::$_controlCharacters[$find] = $replace;
			}
		}
	}

	/**
	 * Get whether mbstring extension is available
	 *
	 * @return boolean
	 */
	public static function getIsMbstringEnabled()
	{
		if (isset(self::$_isMbstringEnabled)) {
			return self::$_isMbstringEnabled;
		}

		self::$_isMbstringEnabled = function_exists('mb_convert_encoding') ?
			true : false;

		return self::$_isMbstringEnabled;
	}

	/**
	 * Get whether iconv extension is available
	 *
	 * @return boolean
	 */
	public static function getIsIconvEnabled()
	{
		if (isset(self::$_isIconvEnabled)) {
			return self::$_isIconvEnabled;
		}

		// Check that iconv exists
		// Sometimes iconv is not working, and e.g. iconv('UTF-8', 'UTF-16LE', 'x') just returns false,
		// we cannot use iconv when that happens
		// Also, sometimes iconv_substr('A', 0, 1, 'UTF-8') just returns false in PHP 5.2.0
		// we cannot use iconv in that case either (http://bugs.php.net/bug.php?id=37773)
		if (function_exists('iconv')
			&& @iconv('UTF-8', 'UTF-16LE', 'x')
			&& @iconv_substr('A', 0, 1, 'UTF-8') ) {

			self::$_isIconvEnabled = true;
		} else {
			self::$_isIconvEnabled = false;
		}

		return self::$_isIconvEnabled;
	}

	/**
	 * Convert from OpenXML escaped control character to PHP control character
	 *
	 * Excel 2007 team:
	 * ----------------
	 * That's correct, control characters are stored directly in the shared-strings table.
	 * We do encode characters that cannot be represented in XML using the following escape sequence:
	 * _xHHHH_ where H represents a hexadecimal character in the character's value...
	 * So you could end up with something like _x0008_ in a string (either in a cell value (<v>)
	 * element or in the shared string <t> element.
	 *
	 * @param 	string	$value	Value to unescape
	 * @return 	string
	 */
	public static function ControlCharacterOOXML2PHP($value = '') {
		if(empty(self::$_controlCharacters)) {
			self::_buildControlCharacters();
		}

		return str_replace( array_keys(self::$_controlCharacters), array_values(self::$_controlCharacters), $value );
	}

	/**
	 * Convert from PHP control character to OpenXML escaped control character
	 *
	 * Excel 2007 team:
	 * ----------------
	 * That's correct, control characters are stored directly in the shared-strings table.
	 * We do encode characters that cannot be represented in XML using the following escape sequence:
	 * _xHHHH_ where H represents a hexadecimal character in the character's value...
	 * So you could end up with something like _x0008_ in a string (either in a cell value (<v>)
	 * element or in the shared string <t> element.
	 *
	 * @param 	string	$value	Value to escape
	 * @return 	string
	 */
	public static function ControlCharacterPHP2OOXML($value = '') {
		if(empty(self::$_controlCharacters)) {
			self::_buildControlCharacters();
		}

		return str_replace( array_values(self::$_controlCharacters), array_keys(self::$_controlCharacters), $value );
	}

	/**
	 * Try to sanitize UTF8, stripping invalid byte sequences. Not perfect. Does not surrogate characters.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function SanitizeUTF8($value)
	{
		if (self::getIsIconvEnabled()) {
			$value = @iconv('UTF-8', 'UTF-8', $value);
			return $value;
		}

		if (self::getIsMbstringEnabled()) {
			$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
			return $value;
		}

		// else, no conversion
		return $value;
	}

	/**
	 * Check if a string contains UTF8 data
	 *
	 * @param string $value
	 * @return boolean
	 */
	public static function IsUTF8($value = '') {
		return utf8_encode(utf8_decode($value)) === $value;
	}

	/**
	 * Formats a numeric value as a string for output in various output writers forcing
	 * point as decimal separator in case locale is other than English.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function FormatNumber($value) {
		if (is_float($value)) {
			return str_replace(',', '.', $value);
		}
		return (string) $value;
	}

	/**
	 * Converts a UTF-8 string into BIFF8 Unicode string data (8-bit string length)
	 * Writes the string using uncompressed notation, no rich text, no Asian phonetics
	 * If mbstring extension is not available, ASCII is assumed, and compressed notation is used
	 * although this will give wrong results for non-ASCII strings
	 * see OpenOffice.org's Documentation of the Microsoft Excel File Format, sect. 2.5.3
	 *
	 * @param string $value UTF-8 encoded string
	 * @return string
	 */
	public static function UTF8toBIFF8UnicodeShort($value)
	{
		// character count
		$ln = self::CountCharacters($value, 'UTF-8');

		// option flags
		$opt = (self::getIsIconvEnabled() || self::getIsMbstringEnabled()) ?
			0x0001 : 0x0000;

		// characters
		$chars = self::ConvertEncoding($value, 'UTF-16LE', 'UTF-8');

		$data = pack('CC', $ln, $opt) . $chars;
		return $data;
	}

	/**
	 * Converts a UTF-8 string into BIFF8 Unicode string data (16-bit string length)
	 * Writes the string using uncompressed notation, no rich text, no Asian phonetics
	 * If mbstring extension is not available, ASCII is assumed, and compressed notation is used
	 * although this will give wrong results for non-ASCII strings
	 * see OpenOffice.org's Documentation of the Microsoft Excel File Format, sect. 2.5.3
	 *
	 * @param string $value UTF-8 encoded string
	 * @return string
	 */
	public static function UTF8toBIFF8UnicodeLong($value)
	{
		// character count
		$ln = self::CountCharacters($value, 'UTF-8');

		// option flags
		$opt = (self::getIsIconvEnabled() || self::getIsMbstringEnabled()) ?
			0x0001 : 0x0000;

		// characters
		$chars = self::ConvertEncoding($value, 'UTF-16LE', 'UTF-8');

		$data = pack('vC', $ln, $opt) . $chars;
		return $data;
	}

	/**
	 * Convert string from one encoding to another. First try mbstring, then iconv, or no convertion
	 *
	 * @param string $value
	 * @param string $to Encoding to convert to, e.g. 'UTF-8'
	 * @param string $from Encoding to convert from, e.g. 'UTF-16LE'
	 * @return string
	 */
	public static function ConvertEncoding($value, $to, $from)
	{
		if (self::getIsIconvEnabled()) {
			$value = iconv($from, $to, $value);
			return $value;
		}

		if (self::getIsMbstringEnabled()) {
			$value = mb_convert_encoding($value, $to, $from);
			return $value;
		}

		// else, no conversion
		return $value;
	}

	/**
	 * Get character count. First try mbstring, then iconv, finally strlen
	 *
	 * @param string $value
	 * @param string $enc Encoding
	 * @return int Character count
	 */
	public static function CountCharacters($value, $enc = 'UTF-8')
	{
		if (self::getIsIconvEnabled()) {
			$count = iconv_strlen($value, $enc);
			return $count;
		}

		if (self::getIsMbstringEnabled()) {
			$count = mb_strlen($value, $enc);
			return $count;
		}

		// else strlen
		$count = strlen($value);
		return $count;
	}

	/**
	 * Get a substring of a UTF-8 encoded string
	 *
	 * @param string $pValue UTF-8 encoded string
	 * @param int $start Start offset
	 * @param int $length Maximum number of characters in substring
	 * @return string
	 */
	public static function Substring($pValue = '', $pStart = 0, $pLength = 0)
	{
		if (self::getIsIconvEnabled()) {
			$string = iconv_substr($pValue, $pStart, $pLength, 'UTF-8');
			return $string;
		}

		if (self::getIsMbstringEnabled()) {
			$string = mb_substr($pValue, $pStart, $pLength, 'UTF-8');
			return $string;
		}

		// else substr
		$string = substr($pValue, $pStart, $pLength);
		return $string;
	}


	/**
	 * Identify whether a string contains a fractional numeric value,
	 *    and convert it to a numeric if it is
	 *
	 * @param string &$operand string value to test
	 * @return boolean
	 */
	public static function convertToNumberIfFraction(&$operand) {
		if (preg_match('/^'.self::STRING_REGEXP_FRACTION.'$/i', $operand, $match)) {
			$sign = ($match[1] == '-') ? '-' : '+';
			$fractionFormula = '='.$sign.$match[2].$sign.$match[3];
			$operand = PHPExcel_Calculation::getInstance()->_calculateFormulaValue($fractionFormula);
			return true;
		}
		return false;
	}	//	function convertToNumberIfFraction()

	/**
	 * Get the decimal separator. If it has not yet been set explicitly, try to obtain number
	 * formatting information from locale.
	 *
	 * @return string
	 */
	public static function getDecimalSeparator()
	{
		if (!isset(self::$_decimalSeparator)) {
			$localeconv = localeconv();
			self::$_decimalSeparator = $localeconv['decimal_point'] != ''
				? $localeconv['decimal_point'] : $localeconv['mon_decimal_point'];
				
			if (self::$_decimalSeparator == '')
			{
				// Default to .
				self::$_decimalSeparator = '.';
			}
		}
		return self::$_decimalSeparator;
	}

	/**
	 * Set the decimal separator. Only used by PHPExcel_Style_NumberFormat::toFormattedString()
	 * to format output by PHPExcel_Writer_HTML and PHPExcel_Writer_PDF
	 *
	 * @param string $pValue Character for decimal separator
	 */
	public static function setDecimalSeparator($pValue = '.')
	{
		self::$_decimalSeparator = $pValue;
	}

	/**
	 * Get the thousands separator. If it has not yet been set explicitly, try to obtain number
	 * formatting information from locale.
	 *
	 * @return string
	 */
	public static function getThousandsSeparator()
	{
		if (!isset(self::$_thousandsSeparator)) {
			$localeconv = localeconv();
			self::$_thousandsSeparator = $localeconv['thousands_sep'] != ''
				? $localeconv['thousands_sep'] : $localeconv['mon_thousands_sep'];
		}
		return self::$_thousandsSeparator;
	}

	/**
	 * Set the thousands separator. Only used by PHPExcel_Style_NumberFormat::toFormattedString()
	 * to format output by PHPExcel_Writer_HTML and PHPExcel_Writer_PDF
	 *
	 * @param string $pValue Character for thousands separator
	 */
	public static function setThousandsSeparator($pValue = ',')
	{
		self::$_thousandsSeparator = $pValue;
	}

}
