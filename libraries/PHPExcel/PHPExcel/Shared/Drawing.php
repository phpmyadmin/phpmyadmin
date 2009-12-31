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
 * PHPExcel_Shared_Drawing
 *
 * @category   PHPExcel
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Shared_Drawing
{
	/**
	 * Convert pixels to EMU
	 *
	 * @param 	int $pValue	Value in pixels
	 * @return 	int			Value in EMU
	 */
	public static function pixelsToEMU($pValue = 0) {
		return round($pValue * 9525);
	}
	
	/**
	 * Convert EMU to pixels
	 *
	 * @param 	int $pValue	Value in EMU
	 * @return 	int			Value in pixels
	 */
	public static function EMUToPixels($pValue = 0) {
		if ($pValue != 0) {
			return round($pValue / 9525);
		} else {
			return 0;
		}
	}
	
	/**
	 * Convert pixels to cell dimension. Exact algorithm not known.
	 * By inspection of a real Excel file using Calibri 11, one finds 1000px ~ 142.85546875
	 * This gives a conversion factor of 7. Also, we assume that pixels and font size are proportional.
	 *
	 * @param 	int $pValue	Value in pixels
	 * @param 	int $pFontSize	Default font size of workbook
	 * @return 	int			Value in cell dimension
	 */
	public static function pixelsToCellDimension($pValue = 0, $pFontSize = 11) {
		return $pValue * $pFontSize / 11 / 7;
	}
	
	/**
	 * Convert cell width to pixels
	 *
	 * @param 	int $pValue	Value in cell dimension
	 * @param 	int $pFontSize	Default font size of workbook
	 * @return 	int			Value in pixels
	 */
	public static function cellDimensionToPixels($pValue = 0, $pFontSize = 11) {
		if ($pValue != 0) {
			return $pValue * 7 * $pFontSize / 11;
		} else {
			return 0;
		}
	}
	
	/**
	 * Convert pixels to points
	 *
	 * @param 	int $pValue	Value in pixels
	 * @return 	int			Value in points
	 */
	public static function pixelsToPoints($pValue = 0) {
		return $pValue * 0.67777777;
	}
	
	/**
	 * Convert points to pixels
	 *
	 * @param 	int $pValue	Value in points
	 * @return 	int			Value in pixels
	 */
	public static function pointsToPixels($pValue = 0) {
		if ($pValue != 0) {
			return (int) ceil($pValue * 1.333333333);
		} else {
			return 0;
		}
	}

	/**
	 * Convert degrees to angle
	 *
	 * @param 	int $pValue	Degrees
	 * @return 	int			Angle
	 */
	public static function degreesToAngle($pValue = 0) {
		return (int)round($pValue * 60000);
	}
	
	/**
	 * Convert angle to degrees
	 *
	 * @param 	int $pValue	Angle
	 * @return 	int			Degrees
	 */
	public static function angleToDegrees($pValue = 0) {
		if ($pValue != 0) {
			return round($pValue / 60000);
		} else {
			return 0;
		}
	}
}
