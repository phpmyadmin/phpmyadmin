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
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.0, 2009-08-10
 */


/**
 * PHPExcel_Shared_Font
 *
 * @category   PHPExcel
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Shared_Font
{
	/** Character set codes used by BIFF5-8 in Font records */
	const CHARSET_ANSI_LATIN				= 0x00;
	const CHARSET_SYSTEM_DEFAULT			= 0x01;
	const CHARSET_SYMBOL					= 0x02;
	const CHARSET_APPLE_ROMAN				= 0x4D;
	const CHARSET_ANSI_JAPANESE_SHIFTJIS	= 0x80;
	const CHARSET_ANSI_KOREAN_HANGUL		= 0x81;
	const CHARSET_ANSI_KOREAN_JOHAB			= 0x82;
	const CHARSET_ANSI_CHINESE_SIMIPLIFIED	= 0x86;
	const CHARSET_ANSI_CHINESE_TRADITIONAL	= 0x88;
	const CHARSET_ANSI_GREEK				= 0xA1;
	const CHARSET_ANSI_TURKISH				= 0xA2;
	const CHARSET_ANSI_VIETNAMESE			= 0xA3;
	const CHARSET_ANSI_HEBREW				= 0xB1;
	const CHARSET_ANSI_ARABIC				= 0xB2;
	const CHARSET_ANSI_BALTIC				= 0xBA;
	const CHARSET_ANSI_CYRILLIC				= 0xCC;
	const CHARSET_ANSI_THAI					= 0xDE;
	const CHARSET_ANSI_LATIN_II				= 0xEE;
	const CHARSET_OEM_LATIN_I				= 0xFF;
	
	/**
	 * Calculate an (approximate) OpenXML column width, based on font size and text contained
	 *
	 * @param 	int		$fontSize			Font size (in pixels or points)
	 * @param 	bool	$fontSizeInPixels	Is the font size specified in pixels (true) or in points (false) ?
	 * @param 	string	$columnText			Text to calculate width
	 * @param 	int		$rotation			Rotation angle
	 * @return 	int		Column width
	 */
	public static function calculateColumnWidth($fontSize = 9, $fontSizeInPixels = false, $columnText = '', $rotation = 0) {
		if (!$fontSizeInPixels) {
			// Translate points size to pixel size
			$fontSize = PHPExcel_Shared_Font::fontSizeToPixels($fontSize);
		}
		
		// If it is rich text, use rich text...
		if ($columnText instanceof PHPExcel_RichText) {
			$columnText = $columnText->getPlainText();
		}
		
		// Only measure the part before the first newline character
		if (strpos($columnText, "\r") !== false) {
			$columnText = substr($columnText, 0, strpos($columnText, "\r"));
		}
		if (strpos($columnText, "\n") !== false) {
			$columnText = substr($columnText, 0, strpos($columnText, "\n"));
		}
		
		// Calculate column width
		// values 1.025 and 0.584 found via interpolation by inspecting real Excel files with
		// Calibri font. May need further adjustment
		$columnWidth = 1.025 * strlen($columnText) + 0.584; // Excel adds some padding

		// Calculate approximate rotated column width
		if ($rotation !== 0) {
			if ($rotation == -165) {
				// stacked text
				$columnWidth = 4; // approximation
			} else {
				// rotated text
				$columnWidth = $columnWidth * cos(deg2rad($rotation))
								+ $fontSize * abs(sin(deg2rad($rotation))) / 5; // approximation
			}
		}

		// Return
		return round($columnWidth, 6);
	}
	
	/**
	 * Calculate an (approximate) pixel size, based on a font points size
	 *
	 * @param 	int		$fontSizeInPoints	Font size (in points)
	 * @return 	int		Font size (in pixels)
	 */
	public static function fontSizeToPixels($fontSizeInPoints = 12) {
		return ((16 / 12) * $fontSizeInPoints);
	}
	
	/**
	 * Calculate an (approximate) pixel size, based on inch size
	 *
	 * @param 	int		$sizeInInch	Font size (in inch)
	 * @return 	int		Size (in pixels)
	 */
	public static function inchSizeToPixels($sizeInInch = 1) {
		return ($sizeInInch * 96);
	}
	
	/**
	 * Calculate an (approximate) pixel size, based on centimeter size
	 *
	 * @param 	int		$sizeInCm	Font size (in centimeters)
	 * @return 	int		Size (in pixels)
	 */
	public static function centimeterSizeToPixels($sizeInCm = 1) {
		return ($sizeInCm * 37.795275591);
	}

	/**
	 * Returns the associated charset for the font name.
	 *
	 * @param string $name Font name
	 * @return int Character set code
	 */
	public static function getCharsetFromFontName($name)
	{
		switch ($name) {
			// Add more cases. Check FONT records in real Excel files.
			case 'Wingdings':		return self::CHARSET_SYMBOL;
			case 'Wingdings 2':		return self::CHARSET_SYMBOL;
			case 'Wingdings 3':		return self::CHARSET_SYMBOL;
			default:				return self::CHARSET_ANSI_LATIN;
		}
	}

}
