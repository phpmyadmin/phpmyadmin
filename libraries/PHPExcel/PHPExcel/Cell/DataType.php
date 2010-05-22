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
 * @package    PHPExcel_Cell
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

/** PHPExcel_Cell_DefaultValueBinder */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell/DefaultValueBinder.php';


/**
 * PHPExcel_Cell_DataType
 *
 * @category   PHPExcel
 * @package    PHPExcel_Cell
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Cell_DataType
{
	/* Data types */
	const TYPE_STRING		= 's';
	const TYPE_FORMULA		= 'f';
	const TYPE_NUMERIC		= 'n';
	const TYPE_BOOL			= 'b';
    const TYPE_NULL			= 's';
    const TYPE_INLINE		= 'inlineStr';
	const TYPE_ERROR		= 'e';

	/**
	 * List of error codes
	 *
	 * @var array
	 */
	private static $_errorCodes	= array('#NULL!' => 0, '#DIV/0!' => 1, '#VALUE!' => 2, '#REF!' => 3, '#NAME?' => 4, '#NUM!' => 5, '#N/A' => 6);

	/**
	 * Get list of error codes
	 *
	 * @return array
	 */
	public static function getErrorCodes() {
		return self::$_errorCodes;
	}
	
	/**
	 * DataType for value
	 *
	 * @deprecated Replaced by PHPExcel_Cell_IValueBinder infrastructure
	 * @param	mixed 	$pValue
	 * @return 	int
	 */
	public static function dataTypeForValue($pValue = null) {
		return PHPExcel_Cell_DefaultValueBinder::dataTypeForValue($pValue);
	}
}
