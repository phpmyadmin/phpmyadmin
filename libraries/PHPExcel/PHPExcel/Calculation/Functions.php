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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category	PHPExcel
 * @package		PHPExcel_Calculation
 * @copyright	Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license		http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version		1.7.0, 2009-08-10
 */


/** EPS */
define('EPS', 2.22e-16);

/** MAX_VALUE */
define('MAX_VALUE', 1.2e308);

/** LOG_GAMMA_X_MAX_VALUE */
define('LOG_GAMMA_X_MAX_VALUE', 2.55e305);

/** SQRT2PI */
define('SQRT2PI', 2.5066282746310005024157652848110452530069867406099);

/** XMININ */
define('XMININ', 2.23e-308);

/** MAX_ITERATIONS */
define('MAX_ITERATIONS', 150);

/** PRECISION */
define('PRECISION', 8.88E-016);

/** EULER */
define('EULER', 2.71828182845904523536);

$savedPrecision = ini_get('precision');
if ($savedPrecision < 16) {
	ini_set('precision',16);
}


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	/**
	 * @ignore
	 */
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../../');
}

/** PHPExcel_Cell */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell.php';

/** PHPExcel_Cell_DataType */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell/DataType.php';

/** PHPExcel_Style_NumberFormat */
require_once PHPEXCEL_ROOT . 'PHPExcel/Style/NumberFormat.php';

/** PHPExcel_Shared_Date */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/Date.php';

/** Matrix */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/JAMA/Matrix.php';
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/trend/trendClass.php';


/**
 * PHPExcel_Calculation_Functions
 *
 * @category	PHPExcel
 * @package		PHPExcel_Calculation
 * @copyright	Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Calculation_Functions {

	/** constants */
	const COMPATIBILITY_EXCEL		= 'Excel';
	const COMPATIBILITY_GNUMERIC	= 'Gnumeric';
	const COMPATIBILITY_OPENOFFICE	= 'OpenOfficeCalc';

	const RETURNDATE_PHP_NUMERIC	= 'P';
	const RETURNDATE_PHP_OBJECT		= 'O';
	const RETURNDATE_EXCEL			= 'E';


	/**
	 *	Compatibility mode to use for error checking and responses
	 *
	 *	@access	private
	 *	@var string
	 */
	private static $compatibilityMode	= self::COMPATIBILITY_EXCEL;

	/**
	 *	Data Type to use when returning date values
	 *
	 *	@access	private
	 *	@var integer
	 */
	private static $ReturnDateType	= self::RETURNDATE_EXCEL;

	/**
	 *	List of error codes
	 *
	 *	@access	private
	 *	@var array
	 */
	private static $_errorCodes	= array( 'null'				=> '#NULL!',
										 'divisionbyzero'	=> '#DIV/0!',
										 'value'			=> '#VALUE!',
										 'reference'		=> '#REF!',
										 'name'				=> '#NAME?',
										 'num'				=> '#NUM!',
										 'na'				=> '#N/A',
										 'gettingdata'		=> '#GETTING_DATA'
									   );


	/**
	 *	Set the Compatibility Mode
	 *
	 *	@access	public
	 *	@category Function Configuration
	 *	@param	 string		$compatibilityMode		Compatibility Mode
	 *												Permitted values are:
	 *													PHPExcel_Calculation_Functions::COMPATIBILITY_EXCEL			'Excel'
	 *													PHPExcel_Calculation_Functions::COMPATIBILITY_GNUMERIC		'Gnumeric'
	 *													PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE	'OpenOfficeCalc'
	 *	@return	 boolean	(Success or Failure)
	 */
	public static function setCompatibilityMode($compatibilityMode) {
		if (($compatibilityMode == self::COMPATIBILITY_EXCEL) ||
			($compatibilityMode == self::COMPATIBILITY_GNUMERIC) ||
			($compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
			self::$compatibilityMode = $compatibilityMode;
			return True;
		}
		return False;
	}	//	function setCompatibilityMode()


	/**
	 *	Return the current Compatibility Mode
	 *
	 *	@access	public
	 *	@category Function Configuration
	 *	@return	 string		Compatibility Mode
	 *							Possible Return values are:
	 *								PHPExcel_Calculation_Functions::COMPATIBILITY_EXCEL			'Excel'
	 *								PHPExcel_Calculation_Functions::COMPATIBILITY_GNUMERIC		'Gnumeric'
	 *								PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE	'OpenOfficeCalc'
	 */
	public static function getCompatibilityMode() {
		return self::$compatibilityMode;
	}	//	function getCompatibilityMode()


	/**
	 *	Set the Return Date Format used by functions that return a date/time (Excel, PHP Serialized Numeric or PHP Object)
	 *
	 *	@access	public
	 *	@category Function Configuration
	 *	@param	 string	$returnDateType			Return Date Format
	 *												Permitted values are:
	 *													PHPExcel_Calculation_Functions::RETURNDATE_PHP_NUMERIC		'P'
	 *													PHPExcel_Calculation_Functions::RETURNDATE_PHP_OBJECT		'O'
	 *													PHPExcel_Calculation_Functions::RETURNDATE_EXCEL			'E'
	 *	@return	 boolean							Success or failure
	 */
	public static function setReturnDateType($returnDateType) {
		if (($returnDateType == self::RETURNDATE_PHP_NUMERIC) ||
			($returnDateType == self::RETURNDATE_PHP_OBJECT) ||
			($returnDateType == self::RETURNDATE_EXCEL)) {
			self::$ReturnDateType = $returnDateType;
			return True;
		}
		return False;
	}	//	function setReturnDateType()


	/**
	 *	Return the current Return Date Format for functions that return a date/time (Excel, PHP Serialized Numeric or PHP Object)
	 *
	 *	@access	public
	 *	@category Function Configuration
	 *	@return	 string		Return Date Format
	 *							Possible Return values are:
	 *								PHPExcel_Calculation_Functions::RETURNDATE_PHP_NUMERIC		'P'
	 *								PHPExcel_Calculation_Functions::RETURNDATE_PHP_OBJECT		'O'
	 *								PHPExcel_Calculation_Functions::RETURNDATE_EXCEL			'E'
	 */
	public static function getReturnDateType() {
		return self::$ReturnDateType;
	}	//	function getReturnDateType()


	/**
	 *	DUMMY
	 *
	 *	@access	public
	 *	@category Error Returns
	 *	@return	string	#Not Yet Implemented
	 */
	public static function DUMMY() {
		return '#Not Yet Implemented';
	}	//	function DUMMY()


	/**
	 *	NA
	 *
	 *	@access	public
	 *	@category Error Returns
	 *	@return	string	#N/A!
	 */
	public static function NA() {
		return self::$_errorCodes['na'];
	}	//	function NA()


	/**
	 *	NAN
	 *
	 *	@access	public
	 *	@category Error Returns
	 *	@return	string	#NUM!
	 */
	public static function NaN() {
		return self::$_errorCodes['num'];
	}	//	function NAN()


	/**
	 *	NAME
	 *
	 *	@access	public
	 *	@category Error Returns
	 *	@return	string	#NAME!
	 */
	public static function NAME() {
		return self::$_errorCodes['name'];
	}	//	function NAME()


	/**
	 *	REF
	 *
	 *	@access	public
	 *	@category Error Returns
	 *	@return	string	#REF!
	 */
	public static function REF() {
		return self::$_errorCodes['reference'];
	}	//	function REF()


	/**
	 *	LOGICAL_AND
	 *
	 *	Returns boolean TRUE if all its arguments are TRUE; returns FALSE if one or more argument is FALSE.
	 *
	 *	Excel Function:
	 *		AND(logical1[,logical2[, ...]])
	 *
	 *		The arguments must evaluate to logical values such as TRUE or FALSE, or the arguments must be arrays
	 *			or references that contain logical values.
	 *
	 *		Booleans arguments are treated as True or False as appropriate
	 *		Integer or floating point arguments are treated as True, except for 0 or 0.0 which are False
	 *		If any argument value is a string, or a Null, the function returns a #VALUE! error, unless the string holds the value TRUE or FALSE,
	 *			holds the value TRUE or FALSE, in which case it is evaluated as a boolean
	 *
	 *	@access	public
	 *	@category Logical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	boolean		The logical AND of the arguments.
	 */
	public static function LOGICAL_AND() {
		// Return value
		$returnValue = True;

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		$argCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a boolean value?
			if (is_bool($arg)) {
				$returnValue = $returnValue && $arg;
				++$argCount;
			} elseif ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue = $returnValue && ($arg != 0);
				++$argCount;
			} elseif (is_string($arg)) {
				$arg = strtoupper($arg);
				if ($arg == 'TRUE') {
					$arg = 1;
				} elseif ($arg == 'FALSE') {
					$arg = 0;
				} else {
					return self::$_errorCodes['value'];
				}
				$returnValue = $returnValue && ($arg != 0);
				++$argCount;
			}
		}

		// Return
		if ($argCount == 0) {
			return self::$_errorCodes['value'];
		}
		return $returnValue;
	}	//	function LOGICAL_AND()


	/**
	 *	LOGICAL_OR
	 *
	 *	Returns boolean TRUE if any argument is TRUE; returns FALSE if all arguments are FALSE.
	 *
	 *	Excel Function:
	 *		OR(logical1[,logical2[, ...]])
	 *
	 *		The arguments must evaluate to logical values such as TRUE or FALSE, or the arguments must be arrays
	 *			or references that contain logical values.
	 *
	 *		Booleans arguments are treated as True or False as appropriate
	 *		Integer or floating point arguments are treated as True, except for 0 or 0.0 which are False
	 *		If any argument value is a string, or a Null, the function returns a #VALUE! error, unless the string
	 *			holds the value TRUE or FALSE, in which case it is evaluated as a boolean
	 *
	 *	@access	public
	 *	@category Logical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	boolean		The logical OR of the arguments.
	 */
	public static function LOGICAL_OR() {
		// Return value
		$returnValue = False;

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		$argCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a boolean value?
			if (is_bool($arg)) {
				$returnValue = $returnValue || $arg;
				++$argCount;
			} elseif ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue = $returnValue || ($arg != 0);
				++$argCount;
			} elseif (is_string($arg)) {
				$arg = strtoupper($arg);
				if ($arg == 'TRUE') {
					$arg = 1;
				} elseif ($arg == 'FALSE') {
					$arg = 0;
				} else {
					return self::$_errorCodes['value'];
				}
				$returnValue = $returnValue || ($arg != 0);
				++$argCount;
			}
		}

		// Return
		if ($argCount == 0) {
			return self::$_errorCodes['value'];
		}
		return $returnValue;
	}	//	function LOGICAL_OR()


	/**
	 *	LOGICAL_FALSE
	 *
	 *	Returns the boolean FALSE.
	 *
	 *	Excel Function:
	 *		FALSE()
	 *
	 *	@access	public
	 *	@category Logical Functions
	 *	@return	boolean		False
	 */
	public static function LOGICAL_FALSE() {
		return False;
	}	//	function LOGICAL_FALSE()


	/**
	 *	LOGICAL_TRUE
	 *
	 *	Returns the boolean TRUE.
	 *
	 *	Excel Function:
	 *		TRUE()
	 *
	 *	@access	public
	 *	@category Logical Functions
	 *	@return	boolean		True
	 */
	public static function LOGICAL_TRUE() {
		return True;
	}	//	function LOGICAL_TRUE()


	/**
	 *	LOGICAL_NOT
	 *
	 *	Returns the boolean inverse of the argument.
	 *
	 *	Excel Function:
	 *		NOT(logical)
	 *
	 *	@access	public
	 *	@category Logical Functions
	 *	@param	mixed		$logical	A value or expression that can be evaluated to TRUE or FALSE
	 *	@return	boolean		The boolean inverse of the argument.
	 */
	public static function LOGICAL_NOT($logical) {
		return !$logical;
	}	//	function LOGICAL_NOT()


	/**
	 *	STATEMENT_IF
	 *
	 *	Returns one value if a condition you specify evaluates to TRUE and another value if it evaluates to FALSE.
	 *
	 *	Excel Function:
	 *		IF(condition[,returnIfTrue[,returnIfFalse]])
	 *
	 *		Condition is any value or expression that can be evaluated to TRUE or FALSE.
	 *			For example, A10=100 is a logical expression; if the value in cell A10 is equal to 100,
	 *			the expression evaluates to TRUE. Otherwise, the expression evaluates to FALSE.
	 *			This argument can use any comparison calculation operator.
	 *		ReturnIfTrue is the value that is returned if condition evaluates to TRUE.
	 *			For example, if this argument is the text string "Within budget" and the condition argument evaluates to TRUE,
	 *			then the IF function returns the text "Within budget"
	 *			If condition is TRUE and ReturnIfTrue is blank, this argument returns 0 (zero). To display the word TRUE, use
	 *			the logical value TRUE for this argument.
	 *			ReturnIfTrue can be another formula.
	 *		ReturnIfFalse is the value that is returned if condition evaluates to FALSE.
	 *			For example, if this argument is the text string "Over budget" and the condition argument evaluates to FALSE,
	 *			then the IF function returns the text "Over budget".
	 *			If condition is FALSE and ReturnIfFalse is omitted, then the logical value FALSE is returned.
	 *			If condition is FALSE and ReturnIfFalse is blank, then the value 0 (zero) is returned.
	 *			ReturnIfFalse can be another formula.
	 *
	 *	@param	mixed	$condition		Condition to evaluate
	 *	@param	mixed	$returnIfTrue	Value to return when condition is true
	 *	@param	mixed	$returnIfFalse	Value to return when condition is false
	 *	@return	mixed	The value of returnIfTrue or returnIfFalse determined by condition
	 */
	public static function STATEMENT_IF($condition = true, $returnIfTrue = 0, $returnIfFalse = False) {
		$condition		= self::flattenSingleValue($condition);
		$returnIfTrue	= self::flattenSingleValue($returnIfTrue);
		$returnIfFalse	= self::flattenSingleValue($returnIfFalse);
		if (is_null($returnIfTrue)) { $returnIfTrue = 0; }
		if (is_null($returnIfFalse)) { $returnIfFalse = 0; }

		return ($condition ? $returnIfTrue : $returnIfFalse);
	}	//	function STATEMENT_IF()


	/**
	 *	STATEMENT_IFERROR
	 *
	 *	@param	mixed	$value		Value to check , is also value when no error
	 *	@param	mixed	$errorpart	Value when error
	 *	@return	mixed
	 */
	public static function STATEMENT_IFERROR($value = '', $errorpart = '') {
		return self::STATEMENT_IF(self::IS_ERROR($value), $errorpart, $value);
	}	//	function STATEMENT_IFERROR()


	/**
	 *	ATAN2
	 *
	 *	This function calculates the arc tangent of the two variables x and y. It is similar to
	 *		calculating the arc tangent of y ÷ x, except that the signs of both arguments are used
	 *		to determine the quadrant of the result.
	 *	The arctangent is the angle from the x-axis to a line containing the origin (0, 0) and a
	 *		point with coordinates (xCoordinate, yCoordinate). The angle is given in radians between
	 *		-pi and pi, excluding -pi.
	 *
	 *	Note that the Excel ATAN2() function accepts its arguments in the reverse order to the standard
	 *		PHP atan2() function, so we need to reverse them here before calling the PHP atan() function.
	 *
	 *	Excel Function:
	 *		ATAN2(xCoordinate,yCoordinate)
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	float	$xCoordinate		The x-coordinate of the point.
	 *	@param	float	$yCoordinate		The y-coordinate of the point.
	 *	@return	float	The inverse tangent of the specified x- and y-coordinates.
	 */
	public static function REVERSE_ATAN2($xCoordinate, $yCoordinate) {
		$xCoordinate	= (float) self::flattenSingleValue($xCoordinate);
		$yCoordinate	= (float) self::flattenSingleValue($yCoordinate);

		if (($xCoordinate == 0) && ($yCoordinate == 0)) {
			return self::$_errorCodes['divisionbyzero'];
		}

		return atan2($yCoordinate, $xCoordinate);
	}	//	function REVERSE_ATAN2()


	/**
	 *	LOG_BASE
	 *
	 *	Returns the logarithm of a number to a specified base. The default base is 10.
	 *
	 *	Excel Function:
	 *		LOG(number[,base])
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	float	$value		The positive real number for which you want the logarithm
	 *	@param	float	$base		The base of the logarithm. If base is omitted, it is assumed to be 10.
	 *	@return	float
	 */
	public static function LOG_BASE($number, $base=10) {
		$number	= self::flattenSingleValue($number);
		$base	= self::flattenSingleValue($base);

		return log($number, $base);
	}	//	function LOG_BASE()


	/**
	 *	SUM
	 *
	 *	SUM computes the sum of all the values and cells referenced in the argument list.
	 *
	 *	Excel Function:
	 *		SUM(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function SUM() {
		// Return value
		$returnValue = 0;

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue += $arg;
			}
		}

		// Return
		return $returnValue;
	}	//	function SUM()


	/**
	 *	SUMSQ
	 *
	 *	SUMSQ returns the sum of the squares of the arguments
	 *
	 *	Excel Function:
	 *		SUMSQ(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function SUMSQ() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue += pow($arg,2);
			}
		}

		// Return
		return $returnValue;
	}	//	function SUMSQ()


	/**
	 *	PRODUCT
	 *
	 *	PRODUCT returns the product of all the values and cells referenced in the argument list.
	 *
	 *	Excel Function:
	 *		PRODUCT(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function PRODUCT() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if (is_null($returnValue)) {
					$returnValue = $arg;
				} else {
					$returnValue *= $arg;
				}
			}
		}

		// Return
		if (is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}	//	function PRODUCT()


	/**
	 *	QUOTIENT
	 *
	 *	QUOTIENT function returns the integer portion of a division. Numerator is the divided number
	 *		and denominator is the divisor.
	 *
	 *	Excel Function:
	 *		QUOTIENT(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function QUOTIENT() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if (is_null($returnValue)) {
					$returnValue = ($arg == 0) ? 0 : $arg;
				} else {
					if (($returnValue == 0) || ($arg == 0)) {
						$returnValue = 0;
					} else {
						$returnValue /= $arg;
					}
				}
			}
		}

		// Return
		return intval($returnValue);
	}	//	function QUOTIENT()


	/**
	 *	MIN
	 *
	 *	MIN returns the value of the element of the values passed that has the smallest value,
	 *		with negative numbers considered smaller than positive numbers.
	 *
	 *	Excel Function:
	 *		MIN(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function MIN() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if ((is_null($returnValue)) || ($arg < $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}	//	function MIN()


	/**
	 *	MINA
	 *
	 *	Returns the smallest value in a list of arguments, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		MINA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function MINA() {
		// Return value
		$returnValue = null;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				if ((is_null($returnValue)) || ($arg < $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}	//	function MINA()


	/**
	 *	SMALL
	 *
	 *	Returns the nth smallest value in a data set. You can use this function to
	 *		select a value based on its relative standing.
	 *
	 *	Excel Function:
	 *		SMALL(value1[,value2[, ...]],entry)
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	int			$entry			Position (ordered from the smallest) in the array or range of data to return
	 *	@return	float
	 */
	public static function SMALL() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$n = array_pop($aArgs);

		if ((is_numeric($n)) && (!is_string($n))) {
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$count = self::COUNT($mArgs);
			$n = floor(--$n);
			if (($n < 0) || ($n >= $count) || ($count == 0)) {
				return self::$_errorCodes['num'];
			}
			sort($mArgs);
			return $mArgs[$n];
		}
		return self::$_errorCodes['value'];
	}	//	function SMALL()


	/**
	 *	MAX
	 *
	 *	MAX returns the value of the element of the values passed that has the highest value,
	 *		with negative numbers considered smaller than positive numbers.
	 *
	 *	Excel Function:
	 *		MAX(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function MAX() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if ((is_null($returnValue)) || ($arg > $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}	//	function MAX()


	/**
	 *	MAXA
	 *
	 *	Returns the greatest value in a list of arguments, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		MAXA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function MAXA() {
		// Return value
		$returnValue = null;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				if ((is_null($returnValue)) || ($arg > $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}	//	function MAXA()


	/**
	 *	LARGE
	 *
	 *	Returns the nth largest value in a data set. You can use this function to
	 *		select a value based on its relative standing.
	 *
	 *	Excel Function:
	 *		LARGE(value1[,value2[, ...]],entry)
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	int			$entry			Position (ordered from the largest) in the array or range of data to return
	 *	@return	float
	 *
	 */
	public static function LARGE() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$n = floor(array_pop($aArgs));

		if ((is_numeric($n)) && (!is_string($n))) {
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$count = self::COUNT($mArgs);
			$n = floor(--$n);
			if (($n < 0) || ($n >= $count) || ($count == 0)) {
				return self::$_errorCodes['num'];
			}
			rsort($mArgs);
			return $mArgs[$n];
		}
		return self::$_errorCodes['value'];
	}	//	function LARGE()


	/**
	 *	PERCENTILE
	 *
	 *	Returns the nth percentile of values in a range..
	 *
	 *	Excel Function:
	 *		PERCENTILE(value1[,value2[, ...]],entry)
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	float		$entry			Percentile value in the range 0..1, inclusive.
	 *	@return	float
	 */
	public static function PERCENTILE() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$entry = array_pop($aArgs);

		if ((is_numeric($entry)) && (!is_string($entry))) {
			if (($entry < 0) || ($entry > 1)) {
				return self::$_errorCodes['num'];
			}
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$mValueCount = count($mArgs);
			if ($mValueCount > 0) {
				sort($mArgs);
				$count = self::COUNT($mArgs);
				$index = $entry * ($count-1);
				$iBase = floor($index);
				if ($index == $iBase) {
					return $mArgs[$index];
				} else {
					$iNext = $iBase + 1;
					$iProportion = $index - $iBase;
					return $mArgs[$iBase] + (($mArgs[$iNext] - $mArgs[$iBase]) * $iProportion) ;
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function PERCENTILE()


	/**
	 *	QUARTILE
	 *
	 *	Returns the quartile of a data set.
	 *
	 *	Excel Function:
	 *		QUARTILE(value1[,value2[, ...]],entry)
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	int			$entry			Quartile value in the range 1..3, inclusive.
	 *	@return	float
	 */
	public static function QUARTILE() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$entry = floor(array_pop($aArgs));

		if ((is_numeric($entry)) && (!is_string($entry))) {
			$entry /= 4;
			if (($entry < 0) || ($entry > 1)) {
				return self::$_errorCodes['num'];
			}
			return self::PERCENTILE($aArgs,$entry);
		}
		return self::$_errorCodes['value'];
	}	//	function QUARTILE()


	/**
	 *	COUNT
	 *
	 *	Counts the number of cells that contain numbers within the list of arguments
	 *
	 *	Excel Function:
	 *		COUNT(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	int
	 */
	public static function COUNT() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			if ((is_bool($arg)) && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
				$arg = (int) $arg;
			}
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}	//	function COUNT()


	/**
	 *	COUNTBLANK
	 *
	 *	Counts the number of empty cells within the list of arguments
	 *
	 *	Excel Function:
	 *		COUNTBLANK(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	int
	 */
	public static function COUNTBLANK() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a blank cell?
			if ((is_null($arg)) || ((is_string($arg)) && ($arg == ''))) {
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}	//	function COUNTBLANK()


	/**
	 *	COUNTA
	 *
	 *	Counts the number of cells that are not empty within the list of arguments
	 *
	 *	Excel Function:
	 *		COUNTA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	int
	 */
	public static function COUNTA() {
		// Return value
		$returnValue = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric, boolean or string value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}	//	function COUNTA()


	/**
	 *	COUNTIF
	 *
	 *	Counts the number of cells that contain numbers within the list of arguments
	 *
	 *	Excel Function:
	 *		COUNTIF(value1[,value2[, ...]],condition)
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	string		$condition		The criteria that defines which cells will be counted.
	 *	@return	int
	 */
	public static function COUNTIF($aArgs,$condition) {
		// Return value
		$returnValue = 0;

		$aArgs = self::flattenArray($aArgs);
		if (!in_array($condition{0},array('>', '<', '='))) {
			if (!is_numeric($condition)) { $condition = PHPExcel_Calculation::_wrapResult(strtoupper($condition)); }
			$condition = '='.$condition;
		}
		// Loop through arguments
		foreach ($aArgs as $arg) {
			if (!is_numeric($arg)) { $arg = PHPExcel_Calculation::_wrapResult(strtoupper($arg)); }
			$testCondition = '='.$arg.$condition;
			if (PHPExcel_Calculation::getInstance()->_calculateFormulaValue($testCondition)) {
				// Is it a value within our criteria
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}	//	function COUNTIF()


	/**
	 *	SUMIF
	 *
	 *	Counts the number of cells that contain numbers within the list of arguments
	 *
	 *	Excel Function:
	 *		SUMIF(value1[,value2[, ...]],condition)
	 *
	 *	@access	public
	 *	@category Mathematical and Trigonometric Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	string		$condition		The criteria that defines which cells will be summed.
	 *	@return	float
	 */
	public static function SUMIF($aArgs,$condition,$sumArgs = array()) {
		// Return value
		$returnValue = 0;

		$aArgs = self::flattenArray($aArgs);
		$sumArgs = self::flattenArray($sumArgs);
		if (count($sumArgs) == 0) {
			$sumArgs = $aArgs;
		}
		if (!in_array($condition{0},array('>', '<', '='))) {
			if (!is_numeric($condition)) { $condition = PHPExcel_Calculation::_wrapResult(strtoupper($condition)); }
			$condition = '='.$condition;
		}
		// Loop through arguments
		foreach ($aArgs as $key => $arg) {
			if (!is_numeric($arg)) { $arg = PHPExcel_Calculation::_wrapResult(strtoupper($arg)); }
			$testCondition = '='.$arg.$condition;
			if (PHPExcel_Calculation::getInstance()->_calculateFormulaValue($testCondition)) {
				// Is it a value within our criteria
				$returnValue += $sumArgs[$key];
			}
		}

		// Return
		return $returnValue;
	}	//	function SUMIF()


	/**
	 *	AVERAGE
	 *
	 *	Returns the average (arithmetic mean) of the arguments
	 *
	 *	Excel Function:
	 *		AVERAGE(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function AVERAGE() {
		// Return value
		$returnValue = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			if ((is_bool($arg)) && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
				$arg = (integer) $arg;
			}
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if (is_null($returnValue)) {
					$returnValue = $arg;
				} else {
					$returnValue += $arg;
				}
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			return $returnValue / $aCount;
		} else {
			return self::$_errorCodes['divisionbyzero'];
		}
	}	//	function AVERAGE()


	/**
	 *	AVERAGEA
	 *
	 *	Returns the average of its arguments, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		AVERAGEA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function AVERAGEA() {
		// Return value
		$returnValue = null;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				if (is_null($returnValue)) {
					$returnValue = $arg;
				} else {
					$returnValue += $arg;
				}
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			return $returnValue / $aCount;
		} else {
			return self::$_errorCodes['divisionbyzero'];
		}
	}	//	function AVERAGEA()


	/**
	 *	MEDIAN
	 *
	 *	Returns the median of the given numbers. The median is the number in the middle of a set of numbers.
	 *
	 *	Excel Function:
	 *		MEDIAN(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function MEDIAN() {
		// Return value
		$returnValue = self::$_errorCodes['num'];

		$mArgs = array();
		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$mArgs[] = $arg;
			}
		}

		$mValueCount = count($mArgs);
		if ($mValueCount > 0) {
			sort($mArgs,SORT_NUMERIC);
			$mValueCount = $mValueCount / 2;
			if ($mValueCount == floor($mValueCount)) {
				$returnValue = ($mArgs[$mValueCount--] + $mArgs[$mValueCount]) / 2;
			} else {
				$mValueCount == floor($mValueCount);
				$returnValue = $mArgs[$mValueCount];
			}
		}

		// Return
		return $returnValue;
	}	//	function MEDIAN()


	//
	//	Special variant of array_count_values that isn't limited to strings and integers,
	//		but can work with floating point numbers as values
	//
	private static function _modeCalc($data) {
		$frequencyArray = array();
		foreach($data as $datum) {
			$found = False;
			foreach($frequencyArray as $key => $value) {
				if ((string) $value['value'] == (string) $datum) {
					++$frequencyArray[$key]['frequency'];
					$found = True;
					break;
				}
			}
			if (!$found) {
				$frequencyArray[] = array('value'		=> $datum,
										  'frequency'	=>	1 );
			}
		}

		foreach($frequencyArray as $key => $value) {
			$frequencyList[$key] = $value['frequency'];
			$valueList[$key] = $value['value'];
		}
		array_multisort($frequencyList, SORT_DESC, $valueList, SORT_ASC, SORT_NUMERIC, $frequencyArray);

		if ($frequencyArray[0]['frequency'] == 1) {
			return self::NA();
		}
		return $frequencyArray[0]['value'];
	}	//	function _modeCalc()


	/**
	 *	MODE
	 *
	 *	Returns the most frequently occurring, or repetitive, value in an array or range of data
	 *
	 *	Excel Function:
	 *		MODE(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function MODE() {
		// Return value
		$returnValue = self::NA();

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());

		$mArgs = array();
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$mArgs[] = $arg;
			}
		}

		if (count($mArgs) > 0) {
			return self::_modeCalc($mArgs);
		}

		// Return
		return $returnValue;
	}	//	function MODE()


	/**
	 *	DEVSQ
	 *
	 *	Returns the sum of squares of deviations of data points from their sample mean.
	 *
	 *	Excel Function:
	 *		DEVSQ(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function DEVSQ() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = -1;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_bool($arg)) && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
					$arg = (int) $arg;
				}
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (is_null($returnValue)) {
				return self::$_errorCodes['num'];
			} else {
				return $returnValue;
			}
		}
		return self::NA();
	}	//	function DEVSQ()


	/**
	 *	AVEDEV
	 *
	 *	Returns the average of the absolute deviations of data points from their mean.
	 *	AVEDEV is a measure of the variability in a data set.
	 *
	 *	Excel Function:
	 *		AVEDEV(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function AVEDEV() {
		$aArgs = self::flattenArray(func_get_args());

		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE($aArgs);
		if ($aMean != self::$_errorCodes['divisionbyzero']) {
			$aCount = 0;
			foreach ($aArgs as $arg) {
				if ((is_bool($arg)) && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
					$arg = (integer) $arg;
				}
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = abs($arg - $aMean);
					} else {
						$returnValue += abs($arg - $aMean);
					}
					++$aCount;
				}
			}

			// Return
			return $returnValue / $aCount ;
		}
		return self::$_errorCodes['num'];
	}	//	function AVEDEV()


	/**
	 *	GEOMEAN
	 *
	 *	Returns the geometric mean of an array or range of positive data. For example, you
	 *		can use GEOMEAN to calculate average growth rate given compound interest with
	 *		variable rates.
	 *
	 *	Excel Function:
	 *		GEOMEAN(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function GEOMEAN() {
		$aMean = self::PRODUCT(func_get_args());
		if (is_numeric($aMean) && ($aMean > 0)) {
			$aArgs = self::flattenArray(func_get_args());
			$aCount = self::COUNT($aArgs) ;
			if (self::MIN($aArgs) > 0) {
				return pow($aMean, (1 / $aCount));
			}
		}
		return self::$_errorCodes['num'];
	}	//	GEOMEAN()


	/**
	 *	HARMEAN
	 *
	 *	Returns the harmonic mean of a data set. The harmonic mean is the reciprocal of the
	 *		arithmetic mean of reciprocals.
	 *
	 *	Excel Function:
	 *		HARMEAN(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function HARMEAN() {
		// Return value
		$returnValue = self::NA();

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		if (self::MIN($aArgs) < 0) {
			return self::$_errorCodes['num'];
		}
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if ($arg <= 0) {
					return self::$_errorCodes['num'];
				}
				if (is_null($returnValue)) {
					$returnValue = (1 / $arg);
				} else {
					$returnValue += (1 / $arg);
				}
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			return 1 / ($returnValue / $aCount);
		} else {
			return $returnValue;
		}
	}	//	function HARMEAN()


	/**
	 *	TRIMMEAN
	 *
	 *	Returns the mean of the interior of a data set. TRIMMEAN calculates the mean
	 *	taken by excluding a percentage of data points from the top and bottom tails
	 *	of a data set.
	 *
	 *	Excel Function:
	 *		TRIMEAN(value1[,value2[, ...]],$discard)
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@param	float		$discard		Percentage to discard
	 *	@return	float
	 */
	public static function TRIMMEAN() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$percent = array_pop($aArgs);

		if ((is_numeric($percent)) && (!is_string($percent))) {
			if (($percent < 0) || ($percent > 1)) {
				return self::$_errorCodes['num'];
			}
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$discard = floor(self::COUNT($mArgs) * $percent / 2);
			sort($mArgs);
			for ($i=0; $i < $discard; ++$i) {
				array_pop($mArgs);
				array_shift($mArgs);
			}
			return self::AVERAGE($mArgs);
		}
		return self::$_errorCodes['value'];
	}	//	function TRIMMEAN()


	/**
	 *	STDEV
	 *
	 *	Estimates standard deviation based on a sample. The standard deviation is a measure of how
	 *	widely values are dispersed from the average value (the mean).
	 *
	 *	Excel Function:
	 *		STDEV(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function STDEV() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = -1;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}	//	function STDEV()


	/**
	 *	STDEVA
	 *
	 *	Estimates standard deviation based on a sample, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		STDEVA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function STDEVA() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGEA(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = -1;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
					if (is_bool($arg)) {
						$arg = (integer) $arg;
					} elseif (is_string($arg)) {
						$arg = 0;
					}
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}	//	function STDEVA()


	/**
	 *	STDEVP
	 *
	 *	Calculates standard deviation based on the entire population
	 *
	 *	Excel Function:
	 *		STDEVP(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function STDEVP() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = 0;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}	//	function STDEVP()


	/**
	 *	STDEVPA
	 *
	 *	Calculates standard deviation based on the entire population, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		STDEVPA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function STDEVPA() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGEA(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = 0;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
					if (is_bool($arg)) {
						$arg = (integer) $arg;
					} elseif (is_string($arg)) {
						$arg = 0;
					}
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}	//	function STDEVPA()


	/**
	 *	VARFunc
	 *
	 *	Estimates variance based on a sample.
	 *
	 *	Excel Function:
	 *		VAR(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function VARFunc() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 1) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * ($aCount - 1));
		}
		return $returnValue;
	}	//	function VARFunc()


	/**
	 *	VARA
	 *
	 *	Estimates variance based on a sample, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		VARA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function VARA() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
				if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 1) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * ($aCount - 1));
		}
		return $returnValue;
	}	//	function VARA()


	/**
	 *	VARP
	 *
	 *	Calculates variance based on the entire population
	 *
	 *	Excel Function:
	 *		VARP(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function VARP() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * $aCount);
		}
		return $returnValue;
	}	//	function VARP()


	/**
	 *	VARPA
	 *
	 *	Calculates variance based on the entire population, including numbers, text, and logical values
	 *
	 *	Excel Function:
	 *		VARPA(value1[,value2[, ...]])
	 *
	 *	@access	public
	 *	@category Statistical Functions
	 *	@param	mixed		$arg,...		Data values
	 *	@return	float
	 */
	public static function VARPA() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * $aCount);
		}
		return $returnValue;
	}	//	function VARPA()


	/**
	 *	RANK
	 *
	 *	Returns the rank of a number in a list of numbers.
	 *
	 *	@param	number				The number whose rank you want to find.
	 *	@param	array of number		An array of, or a reference to, a list of numbers.
	 *	@param	mixed				Order to sort the values in the value set
	 *	@return	float
	 */
	public static function RANK($value,$valueSet,$order=0) {
		$value = self::flattenSingleValue($value);
		$valueSet = self::flattenArray($valueSet);
		$order = self::flattenSingleValue($order);

		foreach($valueSet as $key => $valueEntry) {
			if (!is_numeric($valueEntry)) {
				unset($valueSet[$key]);
			}
		}

		if ($order == 0) {
			rsort($valueSet,SORT_NUMERIC);
		} else {
			sort($valueSet,SORT_NUMERIC);
		}
		$pos = array_search($value,$valueSet);
		if ($pos === False) {
			return self::$_errorCodes['na'];
		}

		return ++$pos;
	}	//	function RANK()


	/**
	 *	PERCENTRANK
	 *
	 *	Returns the rank of a value in a data set as a percentage of the data set.
	 *
	 *	@param	array of number		An array of, or a reference to, a list of numbers.
	 *	@param	number				The number whose rank you want to find.
	 *	@param	number				The number of significant digits for the returned percentage value.
	 *	@return	float
	 */
	public static function PERCENTRANK($valueSet,$value,$significance=3) {
		$valueSet = self::flattenArray($valueSet);
		$value = self::flattenSingleValue($value);
		$significance = self::flattenSingleValue($significance);

		foreach($valueSet as $key => $valueEntry) {
			if (!is_numeric($valueEntry)) {
				unset($valueSet[$key]);
			}
		}
		sort($valueSet,SORT_NUMERIC);
		$valueCount = count($valueSet);
		if ($valueCount == 0) {
			return self::$_errorCodes['num'];
		}

		$valueAdjustor = $valueCount - 1;
		if (($value < $valueSet[0]) || ($value > $valueSet[$valueAdjustor])) {
			return self::$_errorCodes['na'];
		}

		$pos = array_search($value,$valueSet);
		if ($pos === False) {
			$pos = 0;
			$testValue = $valueSet[0];
			while ($testValue < $value) {
				$testValue = $valueSet[++$pos];
			}
			--$pos;
			$pos += (($value - $valueSet[$pos]) / ($testValue - $valueSet[$pos]));
		}

		return round($pos / $valueAdjustor,$significance);
	}	//	function PERCENTRANK()


	private static function _checkTrendArray($values) {
		foreach($values as $key => $value) {
			if ((is_bool($value)) || ((is_string($value)) && (trim($value) == ''))) {
				unset($values[$key]);
			} elseif (is_string($value)) {
				if (is_numeric($value)) {
					$values[$key] = (float) $value;
				} else {
					unset($values[$key]);
				}
			}
		}
		return $values;
	}	//	function _checkTrendArray()


	/**
	 *	INTERCEPT
	 *
	 *	Calculates the point at which a line will intersect the y-axis by using existing x-values and y-values.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function INTERCEPT($yValues,$xValues) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getIntersect();
	}	//	function INTERCEPT()


	/**
	 *	RSQ
	 *
	 *	Returns the square of the Pearson product moment correlation coefficient through data points in known_y's and known_x's.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function RSQ($yValues,$xValues) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getGoodnessOfFit();
	}	//	function RSQ()


	/**
	 *	SLOPE
	 *
	 *	Returns the slope of the linear regression line through data points in known_y's and known_x's.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function SLOPE($yValues,$xValues) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getSlope();
	}	//	function SLOPE()


	/**
	 *	STEYX
	 *
	 *	Returns the standard error of the predicted y-value for each x in the regression.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function STEYX($yValues,$xValues) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getStdevOfResiduals();
	}	//	function STEYX()


	/**
	 *	COVAR
	 *
	 *	Returns covariance, the average of the products of deviations for each data point pair.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function COVAR($yValues,$xValues) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getCovariance();
	}	//	function COVAR()


	/**
	 *	CORREL
	 *
	 *	Returns covariance, the average of the products of deviations for each data point pair.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function CORREL($yValues,$xValues) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getCorrelation();
	}	//	function CORREL()


	/**
	 *	LINEST
	 *
	 *	Calculates the statistics for a line by using the "least squares" method to calculate a straight line that best fits your data,
	 *		and then returns an array that describes the line.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@param	boolean				A logical value specifying whether to force the intersect to equal 0.
	 *	@param	boolean				A logical value specifying whether to return additional regression statistics.
	 *	@return	array
	 */
	public static function LINEST($yValues,$xValues,$const=True,$stats=False) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);
		$const	= (boolean) self::flattenSingleValue($const);
		$stats	= (boolean) self::flattenSingleValue($stats);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues,$const);
		if ($stats) {
			return array( array( $bestFitLinear->getSlope(),
						 		 $bestFitLinear->getSlopeSE(),
						 		 $bestFitLinear->getGoodnessOfFit(),
						 		 $bestFitLinear->getF(),
						 		 $bestFitLinear->getSSRegression(),
							   ),
						  array( $bestFitLinear->getIntersect(),
								 $bestFitLinear->getIntersectSE(),
								 $bestFitLinear->getStdevOfResiduals(),
								 $bestFitLinear->getDFResiduals(),
								 $bestFitLinear->getSSResiduals()
							   )
						);
		} else {
			return array( $bestFitLinear->getSlope(),
						  $bestFitLinear->getIntersect()
						);
		}
	}	//	function LINEST()


	/**
	 *	LOGEST
	 *
	 *	Calculates an exponential curve that best fits the X and Y data series,
	 *		and then returns an array that describes the line.
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@param	boolean				A logical value specifying whether to force the intersect to equal 0.
	 *	@param	boolean				A logical value specifying whether to return additional regression statistics.
	 *	@return	array
	 */
	public static function LOGEST($yValues,$xValues,$const=True,$stats=False) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);
		$const	= (boolean) self::flattenSingleValue($const);
		$stats	= (boolean) self::flattenSingleValue($stats);

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitExponential = trendClass::calculate(trendClass::TREND_EXPONENTIAL,$yValues,$xValues,$const);
		if ($stats) {
			return array( array( $bestFitExponential->getSlope(),
						 		 $bestFitExponential->getSlopeSE(),
						 		 $bestFitExponential->getGoodnessOfFit(),
						 		 $bestFitExponential->getF(),
						 		 $bestFitExponential->getSSRegression(),
							   ),
						  array( $bestFitExponential->getIntersect(),
								 $bestFitExponential->getIntersectSE(),
								 $bestFitExponential->getStdevOfResiduals(),
								 $bestFitExponential->getDFResiduals(),
								 $bestFitExponential->getSSResiduals()
							   )
						);
		} else {
			return array( $bestFitExponential->getSlope(),
						  $bestFitExponential->getIntersect()
						);
		}
	}	//	function LOGEST()


	/**
	 *	FORECAST
	 *
	 *	Calculates, or predicts, a future value by using existing values. The predicted value is a y-value for a given x-value.
	 *
	 *	@param	float				Value of X for which we want to find Y
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@return	float
	 */
	public static function FORECAST($xValue,$yValues,$xValues) {
		$xValue	= self::flattenSingleValue($xValue);
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);

		if (!is_numeric($xValue)) {
			return self::$_errorCodes['value'];
		}

		$yValues = self::_checkTrendArray($yValues);
		$yValueCount = count($yValues);
		$xValues = self::_checkTrendArray($xValues);
		$xValueCount = count($xValues);

		if (($yValueCount == 0) || ($yValueCount != $xValueCount)) {
			return self::$_errorCodes['na'];
		} elseif ($yValueCount == 1) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues);
		return $bestFitLinear->getValueOfYForX($xValue);
	}	//	function FORECAST()


	/**
	 *	TREND
	 *
	 *	Returns values along a linear trend
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@param	array of mixed		Values of X for which we want to find Y
	 *	@param	boolean				A logical value specifying whether to force the intersect to equal 0.
	 *	@return	array of float
	 */
	public static function TREND($yValues,$xValues=array(),$newValues=array(),$const=True) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);
		$newValues = self::flattenArray($newValues);
		$const	= (boolean) self::flattenSingleValue($const);

		$bestFitLinear = trendClass::calculate(trendClass::TREND_LINEAR,$yValues,$xValues,$const);
		if (count($newValues) == 0) {
			$newValues = $bestFitLinear->getXValues();
		}

		$returnArray = array();
		foreach($newValues as $xValue) {
			$returnArray[0][] = $bestFitLinear->getValueOfYForX($xValue);
		}

		return $returnArray;
	}	//	function TREND()


	/**
	 *	GROWTH
	 *
	 *	Returns values along a predicted emponential trend
	 *
	 *	@param	array of mixed		Data Series Y
	 *	@param	array of mixed		Data Series X
	 *	@param	array of mixed		Values of X for which we want to find Y
	 *	@param	boolean				A logical value specifying whether to force the intersect to equal 0.
	 *	@return	array of float
	 */
	public static function GROWTH($yValues,$xValues=array(),$newValues=array(),$const=True) {
		$yValues = self::flattenArray($yValues);
		$xValues = self::flattenArray($xValues);
		$newValues = self::flattenArray($newValues);
		$const	= (boolean) self::flattenSingleValue($const);

		$bestFitExponential = trendClass::calculate(trendClass::TREND_EXPONENTIAL,$yValues,$xValues,$const);
		if (count($newValues) == 0) {
			$newValues = $bestFitExponential->getXValues();
		}

		$returnArray = array();
		foreach($newValues as $xValue) {
			$returnArray[0][] = $bestFitExponential->getValueOfYForX($xValue);
		}

		return $returnArray;
	}	//	function GROWTH()


	private static function _romanCut($num, $n) {
		return ($num - ($num % $n ) ) / $n;
	}	//	function _romanCut()


	public static function ROMAN($aValue, $style=0) {
		$aValue	= (integer) self::flattenSingleValue($aValue);
		if ((!is_numeric($aValue)) || ($aValue < 0) || ($aValue >= 4000)) {
			return self::$_errorCodes['value'];
		}
		if ($aValue == 0) {
			return '';
		}

		$mill = Array('', 'M', 'MM', 'MMM', 'MMMM', 'MMMMM');
		$cent = Array('', 'C', 'CC', 'CCC', 'CD', 'D', 'DC', 'DCC', 'DCCC', 'CM');
		$tens = Array('', 'X', 'XX', 'XXX', 'XL', 'L', 'LX', 'LXX', 'LXXX', 'XC');
		$ones = Array('', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX');

		$roman = '';
		while ($aValue > 5999) {
			$roman .= 'M';
			$aValue -= 1000;
		}
		$m = self::_romanCut($aValue, 1000);	$aValue %= 1000;
		$c = self::_romanCut($aValue, 100);		$aValue %= 100;
		$t = self::_romanCut($aValue, 10);		$aValue %= 10;

		return $roman.$mill[$m].$cent[$c].$tens[$t].$ones[$aValue];
	}	//	function ROMAN()


	/**
	 * SUBTOTAL
	 *
	 * Returns a subtotal in a list or database.
	 *
	 * @param	int		the number 1 to 11 that specifies which function to
	 *					use in calculating subtotals within a list.
	 * @param	array of mixed		Data Series
	 * @return	float
	 */
	public static function SUBTOTAL() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$subtotal = array_shift($aArgs);

		if ((is_numeric($subtotal)) && (!is_string($subtotal))) {
			switch($subtotal) {
				case 1	:
					return self::AVERAGE($aArgs);
					break;
				case 2	:
					return self::COUNT($aArgs);
					break;
				case 3	:
					return self::COUNTA($aArgs);
					break;
				case 4	:
					return self::MAX($aArgs);
					break;
				case 5	:
					return self::MIN($aArgs);
					break;
				case 6	:
					return self::PRODUCT($aArgs);
					break;
				case 7	:
					return self::STDEV($aArgs);
					break;
				case 8	:
					return self::STDEVP($aArgs);
					break;
				case 9	:
					return self::SUM($aArgs);
					break;
				case 10	:
					return self::VARFunc($aArgs);
					break;
				case 11	:
					return self::VARP($aArgs);
					break;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function SUBTOTAL()


	/**
	 * SQRTPI
	 *
	 * Returns the square root of (number * pi).
	 *
	 * @param	float	$number		Number
	 * @return	float	Square Root of Number * Pi
	 */
	public static function SQRTPI($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			if ($number < 0) {
				return self::$_errorCodes['num'];
			}
			return sqrt($number * pi()) ;
		}
		return self::$_errorCodes['value'];
	}	//	function SQRTPI()


	/**
	 * FACT
	 *
	 * Returns the factorial of a number.
	 *
	 * @param	float	$factVal	Factorial Value
	 * @return	int		Factorial
	 */
	public static function FACT($factVal) {
		$factVal	= self::flattenSingleValue($factVal);

		if (is_numeric($factVal)) {
			if ($factVal < 0) {
				return self::$_errorCodes['num'];
			}
			$factLoop = floor($factVal);
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				if ($factVal > $factLoop) {
					return self::$_errorCodes['num'];
				}
			}
			$factorial = 1;
			while ($factLoop > 1) {
				$factorial *= $factLoop--;
			}
			return $factorial ;
		}
		return self::$_errorCodes['value'];
	}	//	function FACT()


	/**
	 * FACTDOUBLE
	 *
	 * Returns the double factorial of a number.
	 *
	 * @param	float	$factVal	Factorial Value
	 * @return	int		Double Factorial
	 */
	public static function FACTDOUBLE($factVal) {
		$factLoop	= floor(self::flattenSingleValue($factVal));

		if (is_numeric($factLoop)) {
			if ($factVal < 0) {
				return self::$_errorCodes['num'];
			}
			$factorial = 1;
			while ($factLoop > 1) {
				$factorial *= $factLoop--;
				--$factLoop;
			}
			return $factorial ;
		}
		return self::$_errorCodes['value'];
	}	//	function FACTDOUBLE()


	/**
	 * MULTINOMIAL
	 *
	 * Returns the ratio of the factorial of a sum of values to the product of factorials.
	 *
	 * @param	array of mixed		Data Series
	 * @return	float
	 */
	public static function MULTINOMIAL() {
		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$summer = 0;
		$divisor = 1;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if (is_numeric($arg)) {
				if ($arg < 1) {
					return self::$_errorCodes['num'];
				}
				$summer += floor($arg);
				$divisor *= self::FACT($arg);
			} else {
				return self::$_errorCodes['value'];
			}
		}

		// Return
		if ($summer > 0) {
			$summer = self::FACT($summer);
			return $summer / $divisor;
		}
		return 0;
	}	//	function MULTINOMIAL()


	/**
	 * CEILING
	 *
	 * Returns number rounded up, away from zero, to the nearest multiple of significance.
	 *
	 * @param	float	$number			Number to round
	 * @param	float	$significance	Significance
	 * @return	float	Rounded Number
	 */
	public static function CEILING($number,$significance=null) {
		$number			= self::flattenSingleValue($number);
		$significance	= self::flattenSingleValue($significance);

		if ((is_null($significance)) && (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC)) {
			$significance = $number/abs($number);
		}

		if ((is_numeric($number)) && (is_numeric($significance))) {
			if (self::SIGN($number) == self::SIGN($significance)) {
				if ($significance == 0.0) {
					return 0;
				}
				return ceil($number / $significance) * $significance;
			} else {
				return self::$_errorCodes['num'];
			}
		}
		return self::$_errorCodes['value'];
	}	//	function CEILING()


	/**
	 * EVEN
	 *
	 * Returns number rounded up to the nearest even integer.
	 *
	 * @param	float	$number			Number to round
	 * @return	int		Rounded Number
	 */
	public static function EVEN($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			$significance = 2 * self::SIGN($number);
			return self::CEILING($number,$significance);
		}
		return self::$_errorCodes['value'];
	}	//	function EVEN()


	/**
	 * ODD
	 *
	 * Returns number rounded up to the nearest odd integer.
	 *
	 * @param	float	$number			Number to round
	 * @return	int		Rounded Number
	 */
	public static function ODD($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			$significance = self::SIGN($number);
			if ($significance == 0) {
				return 1;
			}
			$result = self::CEILING($number,$significance);
			if (self::IS_EVEN($result)) {
				$result += $significance;
			}
			return $result;
		}
		return self::$_errorCodes['value'];
	}	//	function ODD()


	/**
	 *	INTVALUE
	 *
	 *	Casts a floating point value to an integer
	 *
	 *	@param	float	$number			Number to cast to an integer
	 *	@return	integer	Integer value
	 */
	public static function INTVALUE($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			return (int) floor($number);
		}
		return self::$_errorCodes['value'];
	}	//	function INTVALUE()


	/**
	 * ROUNDUP
	 *
	 * Rounds a number up to a specified number of decimal places
	 *
	 * @param	float	$number			Number to round
	 * @param	int		$digits			Number of digits to which you want to round $number
	 * @return	float	Rounded Number
	 */
	public static function ROUNDUP($number,$digits) {
		$number	= self::flattenSingleValue($number);
		$digits	= self::flattenSingleValue($digits);

		if ((is_numeric($number)) && (is_numeric($digits))) {
			$significance = pow(10,$digits);
			if ($number < 0.0) {
				return floor($number * $significance) / $significance;
			} else {
				return ceil($number * $significance) / $significance;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function ROUNDUP()


	/**
	 * ROUNDDOWN
	 *
	 * Rounds a number down to a specified number of decimal places
	 *
	 * @param	float	$number			Number to round
	 * @param	int		$digits			Number of digits to which you want to round $number
	 * @return	float	Rounded Number
	 */
	public static function ROUNDDOWN($number,$digits) {
		$number	= self::flattenSingleValue($number);
		$digits	= self::flattenSingleValue($digits);

		if ((is_numeric($number)) && (is_numeric($digits))) {
			$significance = pow(10,$digits);
			if ($number < 0.0) {
				return ceil($number * $significance) / $significance;
			} else {
				return floor($number * $significance) / $significance;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function ROUNDDOWN()


	/**
	 * MROUND
	 *
	 * Rounds a number to the nearest multiple of a specified value
	 *
	 * @param	float	$number			Number to round
	 * @param	int		$multiple		Multiple to which you want to round $number
	 * @return	float	Rounded Number
	 */
	public static function MROUND($number,$multiple) {
		$number		= self::flattenSingleValue($number);
		$multiple	= self::flattenSingleValue($multiple);

		if ((is_numeric($number)) && (is_numeric($multiple))) {
			if ($multiple == 0) {
				return 0;
			}
			if ((self::SIGN($number)) == (self::SIGN($multiple))) {
				$multiplier = 1 / $multiple;
				return round($number * $multiplier) / $multiplier;
			}
			return self::$_errorCodes['num'];
		}
		return self::$_errorCodes['value'];
	}	//	function MROUND()


	/**
	 * SIGN
	 *
	 * Determines the sign of a number. Returns 1 if the number is positive, zero (0)
	 * if the number is 0, and -1 if the number is negative.
	 *
	 * @param	float	$number			Number to round
	 * @return	int		sign value
	 */
	public static function SIGN($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			if ($number == 0.0) {
				return 0;
			}
			return $number / abs($number);
		}
		return self::$_errorCodes['value'];
	}	//	function SIGN()


	/**
	 * FLOOR
	 *
	 * Rounds number down, toward zero, to the nearest multiple of significance.
	 *
	 * @param	float	$number			Number to round
	 * @param	float	$significance	Significance
	 * @return	float	Rounded Number
	 */
	public static function FLOOR($number,$significance=null) {
		$number			= self::flattenSingleValue($number);
		$significance	= self::flattenSingleValue($significance);

		if ((is_null($significance)) && (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC)) {
			$significance = $number/abs($number);
		}

		if ((is_numeric($number)) && (is_numeric($significance))) {
			if ((float) $significance == 0.0) {
				return self::$_errorCodes['divisionbyzero'];
			}
			if (self::SIGN($number) == self::SIGN($significance)) {
				return floor($number / $significance) * $significance;
			} else {
				return self::$_errorCodes['num'];
			}
		}
		return self::$_errorCodes['value'];
	}	//	function FLOOR()


	/**
	 *	PERMUT
	 *
	 *	Returns the number of permutations for a given number of objects that can be
	 *	selected from number objects. A permutation is any set or subset of objects or
	 *	events where internal order is significant. Permutations are different from
	 *	combinations, for which the internal order is not significant. Use this function
	 *	for lottery-style probability calculations.
	 *
	 *	@param	int		$numObjs	Number of different objects
	 *	@param	int		$numInSet	Number of objects in each permutation
	 *	@return	int		Number of permutations
	 */
	public static function PERMUT($numObjs,$numInSet) {
		$numObjs	= self::flattenSingleValue($numObjs);
		$numInSet	= self::flattenSingleValue($numInSet);

		if ((is_numeric($numObjs)) && (is_numeric($numInSet))) {
			if ($numObjs < $numInSet) {
				return self::$_errorCodes['num'];
			}
			return round(self::FACT($numObjs) / self::FACT($numObjs - $numInSet));
		}
		return self::$_errorCodes['value'];
	}	//	function PERMUT()


	/**
	 *	COMBIN
	 *
	 *	Returns the number of combinations for a given number of items. Use COMBIN to
	 *	determine the total possible number of groups for a given number of items.
	 *
	 *	@param	int		$numObjs	Number of different objects
	 *	@param	int		$numInSet	Number of objects in each combination
	 *	@return	int		Number of combinations
	 */
	public static function COMBIN($numObjs,$numInSet) {
		$numObjs	= self::flattenSingleValue($numObjs);
		$numInSet	= self::flattenSingleValue($numInSet);

		if ((is_numeric($numObjs)) && (is_numeric($numInSet))) {
			if ($numObjs < $numInSet) {
				return self::$_errorCodes['num'];
			} elseif ($numInSet < 0) {
				return self::$_errorCodes['num'];
			}
			return round(self::FACT($numObjs) / self::FACT($numObjs - $numInSet)) / self::FACT($numInSet);
		}
		return self::$_errorCodes['value'];
	}	//	function COMBIN()


	/**
	 * SERIESSUM
	 *
	 * Returns the sum of a power series
	 *
	 * @param	float			$x	Input value to the power series
	 * @param	float			$n	Initial power to which you want to raise $x
	 * @param	float			$m	Step by which to increase $n for each term in the series
	 * @param	array of mixed		Data Series
	 * @return	float
	 */
	public static function SERIESSUM() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());

		$x = array_shift($aArgs);
		$n = array_shift($aArgs);
		$m = array_shift($aArgs);

		if ((is_numeric($x)) && (is_numeric($n)) && (is_numeric($m))) {
			// Calculate
			$i = 0;
			foreach($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$returnValue += $arg * pow($x,$n + ($m * $i++));
				} else {
					return self::$_errorCodes['value'];
				}
			}
			// Return
			return $returnValue;
		}
		return self::$_errorCodes['value'];
	}	//	function SERIESSUM()


	/**
	 * STANDARDIZE
	 *
	 * Returns a normalized value from a distribution characterized by mean and standard_dev.
	 *
	 * @param	float	$value		Value to normalize
	 * @param	float	$mean		Mean Value
	 * @param	float	$stdDev		Standard Deviation
	 * @return	float	Standardized value
	 */
	public static function STANDARDIZE($value,$mean,$stdDev) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);
		$stdDev	= self::flattenSingleValue($stdDev);

		if ((is_numeric($value)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if ($stdDev <= 0) {
				return self::$_errorCodes['num'];
			}
			return ($value - $mean) / $stdDev ;
		}
		return self::$_errorCodes['value'];
	}	//	function STANDARDIZE()


	//
	//	Private method to return an array of the factors of the input value
	//
	private static function _factors($value) {
		$startVal = floor(sqrt($value));

		$factorArray = array();
		for ($i = $startVal; $i > 1; --$i) {
			if (($value % $i) == 0) {
				$factorArray = array_merge($factorArray,self::_factors($value / $i));
				$factorArray = array_merge($factorArray,self::_factors($i));
				if ($i <= sqrt($value)) {
					break;
				}
			}
		}
		if (count($factorArray) > 0) {
			rsort($factorArray);
			return $factorArray;
		} else {
			return array((integer) $value);
		}
	}	//	function _factors()


	/**
	 * LCM
	 *
	 * Returns the lowest common multiplier of a series of numbers
	 *
	 * @param	$array	Values to calculate the Lowest Common Multiplier
	 * @return	int		Lowest Common Multiplier
	 */
	public static function LCM() {
		$aArgs = self::flattenArray(func_get_args());

		$returnValue = 1;
		$allPoweredFactors = array();
		foreach($aArgs as $value) {
			if (!is_numeric($value)) {
				return self::$_errorCodes['value'];
			}
			if ($value == 0) {
				return 0;
			} elseif ($value < 0) {
				return self::$_errorCodes['num'];
			}
			$myFactors = self::_factors(floor($value));
			$myCountedFactors = array_count_values($myFactors);
			$myPoweredFactors = array();
			foreach($myCountedFactors as $myCountedFactor => $myCountedPower) {
				$myPoweredFactors[$myCountedFactor] = pow($myCountedFactor,$myCountedPower);
			}
			foreach($myPoweredFactors as $myPoweredValue => $myPoweredFactor) {
				if (array_key_exists($myPoweredValue,$allPoweredFactors)) {
					if ($allPoweredFactors[$myPoweredValue] < $myPoweredFactor) {
						$allPoweredFactors[$myPoweredValue] = $myPoweredFactor;
					}
				} else {
					$allPoweredFactors[$myPoweredValue] = $myPoweredFactor;
				}
			}
		}
		foreach($allPoweredFactors as $allPoweredFactor) {
			$returnValue *= (integer) $allPoweredFactor;
		}
		return $returnValue;
	}	//	function LCM()


	/**
	 * GCD
	 *
	 * Returns the greatest common divisor of a series of numbers
	 *
	 * @param	$array	Values to calculate the Greatest Common Divisor
	 * @return	int		Greatest Common Divisor
	 */
	public static function GCD() {
		$aArgs = self::flattenArray(func_get_args());

		$returnValue = 1;
		$allPoweredFactors = array();
		foreach($aArgs as $value) {
			if ($value == 0) {
				break;
			}
			$myFactors = self::_factors($value);
			$myCountedFactors = array_count_values($myFactors);
			$allValuesFactors[] = $myCountedFactors;
		}
		$allValuesCount = count($allValuesFactors);
		$mergedArray = $allValuesFactors[0];
		for ($i=1;$i < $allValuesCount; ++$i) {
			$mergedArray = array_intersect_key($mergedArray,$allValuesFactors[$i]);
		}
		$mergedArrayValues = count($mergedArray);
		if ($mergedArrayValues == 0) {
			return $returnValue;
		} elseif ($mergedArrayValues > 1) {
			foreach($mergedArray as $mergedKey => $mergedValue) {
				foreach($allValuesFactors as $highestPowerTest) {
					foreach($highestPowerTest as $testKey => $testValue) {
						if (($testKey == $mergedKey) && ($testValue < $mergedValue)) {
							$mergedArray[$mergedKey] = $testValue;
							$mergedValue = $testValue;
						}
					}
				}
			}

			$returnValue = 1;
			foreach($mergedArray as $key => $value) {
				$returnValue *= pow($key,$value);
			}
			return $returnValue;
		} else {
			$keys = array_keys($mergedArray);
			$key = $keys[0];
			$value = $mergedArray[$key];
			foreach($allValuesFactors as $testValue) {
				foreach($testValue as $mergedKey => $mergedValue) {
					if (($mergedKey == $key) && ($mergedValue < $value)) {
						$value = $mergedValue;
					}
				}
			}
			return pow($key,$value);
		}
	}	//	function GCD()


	/**
	 * BINOMDIST
	 *
	 * Returns the individual term binomial distribution probability. Use BINOMDIST in problems with
	 * a fixed number of tests or trials, when the outcomes of any trial are only success or failure,
	 * when trials are independent, and when the probability of success is constant throughout the
	 * experiment. For example, BINOMDIST can calculate the probability that two of the next three
	 * babies born are male.
	 *
	 * @param	float		$value			Number of successes in trials
	 * @param	float		$trials			Number of trials
	 * @param	float		$probability	Probability of success on each trial
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 * @todo	Cumulative distribution function
	 *
	 */
	public static function BINOMDIST($value, $trials, $probability, $cumulative) {
		$value			= floor(self::flattenSingleValue($value));
		$trials			= floor(self::flattenSingleValue($trials));
		$probability	= self::flattenSingleValue($probability);

		if ((is_numeric($value)) && (is_numeric($trials)) && (is_numeric($probability))) {
			if (($value < 0) || ($value > $trials)) {
				return self::$_errorCodes['num'];
			}
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					$summer = 0;
					for ($i = 0; $i <= $value; ++$i) {
						$summer += self::COMBIN($trials,$i) * pow($probability,$i) * pow(1 - $probability,$trials - $i);
					}
					return $summer;
				} else {
					return self::COMBIN($trials,$value) * pow($probability,$value) * pow(1 - $probability,$trials - $value) ;
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function BINOMDIST()


	/**
	 * NEGBINOMDIST
	 *
	 * Returns the negative binomial distribution. NEGBINOMDIST returns the probability that
	 * there will be number_f failures before the number_s-th success, when the constant
	 * probability of a success is probability_s. This function is similar to the binomial
	 * distribution, except that the number of successes is fixed, and the number of trials is
	 * variable. Like the binomial, trials are assumed to be independent.
	 *
	 * @param	float		$failures		Number of Failures
	 * @param	float		$successes		Threshold number of Successes
	 * @param	float		$probability	Probability of success on each trial
	 * @return	float
	 *
	 */
	public static function NEGBINOMDIST($failures, $successes, $probability) {
		$failures		= floor(self::flattenSingleValue($failures));
		$successes		= floor(self::flattenSingleValue($successes));
		$probability	= self::flattenSingleValue($probability);

		if ((is_numeric($failures)) && (is_numeric($successes)) && (is_numeric($probability))) {
			if (($failures < 0) || ($successes < 1)) {
				return self::$_errorCodes['num'];
			}
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				if (($failures + $successes - 1) <= 0) {
					return self::$_errorCodes['num'];
				}
			}
			return (self::COMBIN($failures + $successes - 1,$successes - 1)) * (pow($probability,$successes)) * (pow(1 - $probability,$failures)) ;
		}
		return self::$_errorCodes['value'];
	}	//	function NEGBINOMDIST()


	/**
	 * CRITBINOM
	 *
	 * Returns the smallest value for which the cumulative binomial distribution is greater
	 * than or equal to a criterion value
	 *
	 * See http://support.microsoft.com/kb/828117/ for details of the algorithm used
	 *
	 * @param	float		$trials			number of Bernoulli trials
	 * @param	float		$probability	probability of a success on each trial
	 * @param	float		$alpha			criterion value
	 * @return	int
	 *
	 *	@todo	Warning. This implementation differs from the algorithm detailed on the MS
	 *			web site in that $CumPGuessMinus1 = $CumPGuess - 1 rather than $CumPGuess - $PGuess
	 *			This eliminates a potential endless loop error, but may have an adverse affect on the
	 *			accuracy of the function (although all my tests have so far returned correct results).
	 *
	 */
	public static function CRITBINOM($trials, $probability, $alpha) {
		$trials			= floor(self::flattenSingleValue($trials));
		$probability	= self::flattenSingleValue($probability);
		$alpha			= self::flattenSingleValue($alpha);

		if ((is_numeric($trials)) && (is_numeric($probability)) && (is_numeric($alpha))) {
			if ($trials < 0) {
				return self::$_errorCodes['num'];
			}
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if (($alpha < 0) || ($alpha > 1)) {
				return self::$_errorCodes['num'];
			}
			if ($alpha <= 0.5) {
				$t = sqrt(log(1 / pow($alpha,2)));
				$trialsApprox = 0 - ($t + (2.515517 + 0.802853 * $t + 0.010328 * $t * $t) / (1 + 1.432788 * $t + 0.189269 * $t * $t + 0.001308 * $t * $t * $t));
			} else {
				$t = sqrt(log(1 / pow(1 - $alpha,2)));
				$trialsApprox = $t - (2.515517 + 0.802853 * $t + 0.010328 * $t * $t) / (1 + 1.432788 * $t + 0.189269 * $t * $t + 0.001308 * $t * $t * $t);
			}
			$Guess = floor($trials * $probability + $trialsApprox * sqrt($trials * $probability * (1 - $probability)));
			if ($Guess < 0) {
				$Guess = 0;
			} elseif ($Guess > $trials) {
				$Guess = $trials;
			}

			$TotalUnscaledProbability = $UnscaledPGuess = $UnscaledCumPGuess = 0.0;
			$EssentiallyZero = 10e-12;

			$m = floor($trials * $probability);
			++$TotalUnscaledProbability;
			if ($m == $Guess) { ++$UnscaledPGuess; }
			if ($m <= $Guess) { ++$UnscaledCumPGuess; }

			$PreviousValue = 1;
			$Done = False;
			$k = $m + 1;
			while ((!$Done) && ($k <= $trials)) {
				$CurrentValue = $PreviousValue * ($trials - $k + 1) * $probability / ($k * (1 - $probability));
				$TotalUnscaledProbability += $CurrentValue;
				if ($k == $Guess) { $UnscaledPGuess += $CurrentValue; }
				if ($k <= $Guess) { $UnscaledCumPGuess += $CurrentValue; }
				if ($CurrentValue <= $EssentiallyZero) { $Done = True; }
				$PreviousValue = $CurrentValue;
				++$k;
			}

			$PreviousValue = 1;
			$Done = False;
			$k = $m - 1;
			while ((!$Done) && ($k >= 0)) {
				$CurrentValue = $PreviousValue * $k + 1 * (1 - $probability) / (($trials - $k) * $probability);
				$TotalUnscaledProbability += $CurrentValue;
				if ($k == $Guess) { $UnscaledPGuess += $CurrentValue; }
				if ($k <= $Guess) { $UnscaledCumPGuess += $CurrentValue; }
				if ($CurrentValue <= $EssentiallyZero) { $Done = True; }
				$PreviousValue = $CurrentValue;
				--$k;
			}

			$PGuess = $UnscaledPGuess / $TotalUnscaledProbability;
			$CumPGuess = $UnscaledCumPGuess / $TotalUnscaledProbability;

//			$CumPGuessMinus1 = $CumPGuess - $PGuess;
			$CumPGuessMinus1 = $CumPGuess - 1;

			while (True) {
				if (($CumPGuessMinus1 < $alpha) && ($CumPGuess >= $alpha)) {
					return $Guess;
				} elseif (($CumPGuessMinus1 < $alpha) && ($CumPGuess < $alpha)) {
					$PGuessPlus1 = $PGuess * ($trials - $Guess) * $probability / $Guess / (1 - $probability);
					$CumPGuessMinus1 = $CumPGuess;
					$CumPGuess = $CumPGuess + $PGuessPlus1;
					$PGuess = $PGuessPlus1;
					++$Guess;
				} elseif (($CumPGuessMinus1 >= $alpha) && ($CumPGuess >= $alpha)) {
					$PGuessMinus1 = $PGuess * $Guess * (1 - $probability) / ($trials - $Guess + 1) / $probability;
					$CumPGuess = $CumPGuessMinus1;
					$CumPGuessMinus1 = $CumPGuessMinus1 - $PGuess;
					$PGuess = $PGuessMinus1;
					--$Guess;
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function CRITBINOM()


	/**
	 * CHIDIST
	 *
	 * Returns the one-tailed probability of the chi-squared distribution.
	 *
	 * @param	float		$value			Value for the function
	 * @param	float		$degrees		degrees of freedom
	 * @return	float
	 */
	public static function CHIDIST($value, $degrees) {
		$value		= self::flattenSingleValue($value);
		$degrees	= floor(self::flattenSingleValue($degrees));

		if ((is_numeric($value)) && (is_numeric($degrees))) {
			if ($degrees < 1) {
				return self::$_errorCodes['num'];
			}
			if ($value < 0) {
				if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
					return 1;
				}
				return self::$_errorCodes['num'];
			}
			return 1 - (self::_incompleteGamma($degrees/2,$value/2) / self::_gamma($degrees/2));
		}
		return self::$_errorCodes['value'];
	}	//	function CHIDIST()


	/**
	 * CHIINV
	 *
	 * Returns the one-tailed probability of the chi-squared distribution.
	 *
	 * @param	float		$probability	Probability for the function
	 * @param	float		$degrees		degrees of freedom
	 * @return	float
	 */
	public static function CHIINV($probability, $degrees) {
		$probability	= self::flattenSingleValue($probability);
		$degrees		= floor(self::flattenSingleValue($degrees));

		if ((is_numeric($probability)) && (is_numeric($degrees))) {
			$xLo = 100;
			$xHi = 0;
			$maxIteration = 100;

			$x = $xNew = 1;
			$dx	= 1;
			$i = 0;

			while ((abs($dx) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				// Apply Newton-Raphson step
				$result = self::CHIDIST($x, $degrees);
				$error = $result - $probability;
				if ($error == 0.0) {
					$dx = 0;
				} elseif ($error < 0.0) {
					$xLo = $x;
				} else {
					$xHi = $x;
				}
				// Avoid division by zero
				if ($result != 0.0) {
					$dx = $error / $result;
					$xNew = $x - $dx;
				}
				// If the NR fails to converge (which for example may be the
				// case if the initial guess is too rough) we apply a bisection
				// step to determine a more narrow interval around the root.
				if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
					$xNew = ($xLo + $xHi) / 2;
					$dx = $xNew - $x;
				}
				$x = $xNew;
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($x,12);
		}
		return self::$_errorCodes['value'];
	}	//	function CHIINV()


	/**
	 * EXPONDIST
	 *
	 * Returns the exponential distribution. Use EXPONDIST to model the time between events,
	 * such as how long an automated bank teller takes to deliver cash. For example, you can
	 * use EXPONDIST to determine the probability that the process takes at most 1 minute.
	 *
	 * @param	float		$value			Value of the function
	 * @param	float		$lambda			The parameter value
	 * @param	boolean		$cumulative
	 * @return	float
	 */
	public static function EXPONDIST($value, $lambda, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$lambda	= self::flattenSingleValue($lambda);
		$cumulative	= self::flattenSingleValue($cumulative);

		if ((is_numeric($value)) && (is_numeric($lambda))) {
			if (($value < 0) || ($lambda < 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return 1 - exp(0-$value*$lambda);
				} else {
					return $lambda * exp(0-$value*$lambda);
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function EXPONDIST()


	/**
	 * FISHER
	 *
	 * Returns the Fisher transformation at x. This transformation produces a function that
	 * is normally distributed rather than skewed. Use this function to perform hypothesis
	 * testing on the correlation coefficient.
	 *
	 * @param	float		$value
	 * @return	float
	 */
	public static function FISHER($value) {
		$value	= self::flattenSingleValue($value);

		if (is_numeric($value)) {
			if (($value <= -1) || ($value >= 1)) {
				return self::$_errorCodes['num'];
			}
			return 0.5 * log((1+$value)/(1-$value));
		}
		return self::$_errorCodes['value'];
	}	//	function FISHER()


	/**
	 * FISHERINV
	 *
	 * Returns the inverse of the Fisher transformation. Use this transformation when
	 * analyzing correlations between ranges or arrays of data. If y = FISHER(x), then
	 * FISHERINV(y) = x.
	 *
	 * @param	float		$value
	 * @return	float
	 */
	public static function FISHERINV($value) {
		$value	= self::flattenSingleValue($value);

		if (is_numeric($value)) {
			return (exp(2 * $value) - 1) / (exp(2 * $value) + 1);
		}
		return self::$_errorCodes['value'];
	}	//	function FISHERINV()


	// Function cache for _logBeta function
	private static $_logBetaCache_p			= 0.0;
	private static $_logBetaCache_q			= 0.0;
	private static $_logBetaCache_result	= 0.0;

	/**
	 * The natural logarithm of the beta function.
	 * @param p require p>0
	 * @param q require q>0
	 * @return 0 if p<=0, q<=0 or p+q>2.55E305 to avoid errors and over/underflow
	 * @author Jaco van Kooten
	 */
	private static function _logBeta($p, $q) {
		if ($p != self::$_logBetaCache_p || $q != self::$_logBetaCache_q) {
			self::$_logBetaCache_p = $p;
			self::$_logBetaCache_q = $q;
			if (($p <= 0.0) || ($q <= 0.0) || (($p + $q) > LOG_GAMMA_X_MAX_VALUE)) {
				self::$_logBetaCache_result = 0.0;
			} else {
				self::$_logBetaCache_result = self::_logGamma($p) + self::_logGamma($q) - self::_logGamma($p + $q);
			}
		}
		return self::$_logBetaCache_result;
	}	//	function _logBeta()


	/**
	 * Evaluates of continued fraction part of incomplete beta function.
	 * Based on an idea from Numerical Recipes (W.H. Press et al, 1992).
	 * @author Jaco van Kooten
	 */
	private static function _betaFraction($x, $p, $q) {
		$c = 1.0;
		$sum_pq = $p + $q;
		$p_plus = $p + 1.0;
		$p_minus = $p - 1.0;
		$h = 1.0 - $sum_pq * $x / $p_plus;
		if (abs($h) < XMININ) {
			$h = XMININ;
		}
		$h = 1.0 / $h;
		$frac = $h;
		$m	 = 1;
		$delta = 0.0;
		while ($m <= MAX_ITERATIONS && abs($delta-1.0) > PRECISION ) {
			$m2 = 2 * $m;
			// even index for d
			$d = $m * ($q - $m) * $x / ( ($p_minus + $m2) * ($p + $m2));
			$h = 1.0 + $d * $h;
			if (abs($h) < XMININ) {
				$h = XMININ;
			}
			$h = 1.0 / $h;
			$c = 1.0 + $d / $c;
			if (abs($c) < XMININ) {
				$c = XMININ;
			}
			$frac *= $h * $c;
			// odd index for d
			$d = -($p + $m) * ($sum_pq + $m) * $x / (($p + $m2) * ($p_plus + $m2));
			$h = 1.0 + $d * $h;
			if (abs($h) < XMININ) {
				$h = XMININ;
			}
			$h = 1.0 / $h;
			$c = 1.0 + $d / $c;
			if (abs($c) < XMININ) {
				$c = XMININ;
			}
			$delta = $h * $c;
			$frac *= $delta;
			++$m;
		}
		return $frac;
	}	//	function _betaFraction()


	/**
	 * logGamma function
	 *
	 * @version 1.1
	 * @author Jaco van Kooten
	 *
	 * Original author was Jaco van Kooten. Ported to PHP by Paul Meagher.
	 *
	 * The natural logarithm of the gamma function. <br />
	 * Based on public domain NETLIB (Fortran) code by W. J. Cody and L. Stoltz <br />
	 * Applied Mathematics Division <br />
	 * Argonne National Laboratory <br />
	 * Argonne, IL 60439 <br />
	 * <p>
	 * References:
	 * <ol>
	 * <li>W. J. Cody and K. E. Hillstrom, 'Chebyshev Approximations for the Natural
	 *	 Logarithm of the Gamma Function,' Math. Comp. 21, 1967, pp. 198-203.</li>
	 * <li>K. E. Hillstrom, ANL/AMD Program ANLC366S, DGAMMA/DLGAMA, May, 1969.</li>
	 * <li>Hart, Et. Al., Computer Approximations, Wiley and sons, New York, 1968.</li>
	 * </ol>
	 * </p>
	 * <p>
	 * From the original documentation:
	 * </p>
	 * <p>
	 * This routine calculates the LOG(GAMMA) function for a positive real argument X.
	 * Computation is based on an algorithm outlined in references 1 and 2.
	 * The program uses rational functions that theoretically approximate LOG(GAMMA)
	 * to at least 18 significant decimal digits. The approximation for X > 12 is from
	 * reference 3, while approximations for X < 12.0 are similar to those in reference
	 * 1, but are unpublished. The accuracy achieved depends on the arithmetic system,
	 * the compiler, the intrinsic functions, and proper selection of the
	 * machine-dependent constants.
	 * </p>
	 * <p>
	 * Error returns: <br />
	 * The program returns the value XINF for X .LE. 0.0 or when overflow would occur.
	 * The computation is believed to be free of underflow and overflow.
	 * </p>
	 * @return MAX_VALUE for x < 0.0 or when overflow would occur, i.e. x > 2.55E305
	 */

	// Function cache for logGamma
	private static $_logGammaCache_result	= 0.0;
	private static $_logGammaCache_x		= 0.0;

	private static function _logGamma($x) {
		// Log Gamma related constants
		static $lg_d1 = -0.5772156649015328605195174;
		static $lg_d2 = 0.4227843350984671393993777;
		static $lg_d4 = 1.791759469228055000094023;

		static $lg_p1 = array(	4.945235359296727046734888,
								201.8112620856775083915565,
								2290.838373831346393026739,
								11319.67205903380828685045,
								28557.24635671635335736389,
								38484.96228443793359990269,
								26377.48787624195437963534,
								7225.813979700288197698961 );
		static $lg_p2 = array(	4.974607845568932035012064,
								542.4138599891070494101986,
								15506.93864978364947665077,
								184793.2904445632425417223,
								1088204.76946882876749847,
								3338152.967987029735917223,
								5106661.678927352456275255,
								3074109.054850539556250927 );
		static $lg_p4 = array(	14745.02166059939948905062,
								2426813.369486704502836312,
								121475557.4045093227939592,
								2663432449.630976949898078,
								29403789566.34553899906876,
								170266573776.5398868392998,
								492612579337.743088758812,
								560625185622.3951465078242 );

		static $lg_q1 = array(	67.48212550303777196073036,
								1113.332393857199323513008,
								7738.757056935398733233834,
								27639.87074403340708898585,
								54993.10206226157329794414,
								61611.22180066002127833352,
								36351.27591501940507276287,
								8785.536302431013170870835 );
		static $lg_q2 = array(	183.0328399370592604055942,
								7765.049321445005871323047,
								133190.3827966074194402448,
								1136705.821321969608938755,
								5267964.117437946917577538,
								13467014.54311101692290052,
								17827365.30353274213975932,
								9533095.591844353613395747 );
		static $lg_q4 = array(	2690.530175870899333379843,
								639388.5654300092398984238,
								41355999.30241388052042842,
								1120872109.61614794137657,
								14886137286.78813811542398,
								101680358627.2438228077304,
								341747634550.7377132798597,
								446315818741.9713286462081 );

		static $lg_c  = array(	-0.001910444077728,
								8.4171387781295e-4,
								-5.952379913043012e-4,
								7.93650793500350248e-4,
								-0.002777777777777681622553,
								0.08333333333333333331554247,
								0.0057083835261 );

	// Rough estimate of the fourth root of logGamma_xBig
	static $lg_frtbig = 2.25e76;
	static $pnt68	 = 0.6796875;


	if ($x == self::$_logGammaCache_x) {
		return self::$_logGammaCache_result;
	}
	$y = $x;
	if ($y > 0.0 && $y <= LOG_GAMMA_X_MAX_VALUE) {
		if ($y <= EPS) {
			$res = -log(y);
		} elseif ($y <= 1.5) {
			// ---------------------
			//	EPS .LT. X .LE. 1.5
			// ---------------------
			if ($y < $pnt68) {
				$corr = -log($y);
				$xm1 = $y;
			} else {
				$corr = 0.0;
				$xm1 = $y - 1.0;
			}
			if ($y <= 0.5 || $y >= $pnt68) {
				$xden = 1.0;
				$xnum = 0.0;
				for ($i = 0; $i < 8; ++$i) {
					$xnum = $xnum * $xm1 + $lg_p1[$i];
					$xden = $xden * $xm1 + $lg_q1[$i];
				}
				$res = $corr + $xm1 * ($lg_d1 + $xm1 * ($xnum / $xden));
			} else {
				$xm2 = $y - 1.0;
				$xden = 1.0;
				$xnum = 0.0;
				for ($i = 0; $i < 8; ++$i) {
					$xnum = $xnum * $xm2 + $lg_p2[$i];
					$xden = $xden * $xm2 + $lg_q2[$i];
				}
				$res = $corr + $xm2 * ($lg_d2 + $xm2 * ($xnum / $xden));
			}
		} elseif ($y <= 4.0) {
			// ---------------------
			//	1.5 .LT. X .LE. 4.0
			// ---------------------
			$xm2 = $y - 2.0;
			$xden = 1.0;
			$xnum = 0.0;
			for ($i = 0; $i < 8; ++$i) {
				$xnum = $xnum * $xm2 + $lg_p2[$i];
				$xden = $xden * $xm2 + $lg_q2[$i];
			}
			$res = $xm2 * ($lg_d2 + $xm2 * ($xnum / $xden));
		} elseif ($y <= 12.0) {
			// ----------------------
			//	4.0 .LT. X .LE. 12.0
			// ----------------------
			$xm4 = $y - 4.0;
			$xden = -1.0;
			$xnum = 0.0;
			for ($i = 0; $i < 8; ++$i) {
				$xnum = $xnum * $xm4 + $lg_p4[$i];
				$xden = $xden * $xm4 + $lg_q4[$i];
			}
			$res = $lg_d4 + $xm4 * ($xnum / $xden);
		} else {
			// ---------------------------------
			//	Evaluate for argument .GE. 12.0
			// ---------------------------------
			$res = 0.0;
			if ($y <= $lg_frtbig) {
				$res = $lg_c[6];
				$ysq = $y * $y;
				for ($i = 0; $i < 6; ++$i)
					$res = $res / $ysq + $lg_c[$i];
				}
				$res /= $y;
				$corr = log($y);
				$res = $res + log(SQRT2PI) - 0.5 * $corr;
				$res += $y * ($corr - 1.0);
			}
		} else {
			// --------------------------
			//	Return for bad arguments
			// --------------------------
			$res = MAX_VALUE;
		}
		// ------------------------------
		//	Final adjustments and return
		// ------------------------------
		self::$_logGammaCache_x = $x;
		self::$_logGammaCache_result = $res;
		return $res;
	}	//	function _logGamma()


	/**
	 * Beta function.
	 *
	 * @author Jaco van Kooten
	 *
	 * @param p require p>0
	 * @param q require q>0
	 * @return 0 if p<=0, q<=0 or p+q>2.55E305 to avoid errors and over/underflow
	 */
	private static function _beta($p, $q) {
		if ($p <= 0.0 || $q <= 0.0 || ($p + $q) > LOG_GAMMA_X_MAX_VALUE) {
			return 0.0;
		} else {
			return exp(self::_logBeta($p, $q));
		}
	}	//	function _beta()


	/**
	 * Incomplete beta function
	 *
	 * @author Jaco van Kooten
	 * @author Paul Meagher
	 *
	 * The computation is based on formulas from Numerical Recipes, Chapter 6.4 (W.H. Press et al, 1992).
	 * @param x require 0<=x<=1
	 * @param p require p>0
	 * @param q require q>0
	 * @return 0 if x<0, p<=0, q<=0 or p+q>2.55E305 and 1 if x>1 to avoid errors and over/underflow
	 */
	private static function _incompleteBeta($x, $p, $q) {
		if ($x <= 0.0) {
			return 0.0;
		} elseif ($x >= 1.0) {
			return 1.0;
		} elseif (($p <= 0.0) || ($q <= 0.0) || (($p + $q) > LOG_GAMMA_X_MAX_VALUE)) {
			return 0.0;
		}
		$beta_gam = exp((0 - self::_logBeta($p, $q)) + $p * log($x) + $q * log(1.0 - $x));
		if ($x < ($p + 1.0) / ($p + $q + 2.0)) {
			return $beta_gam * self::_betaFraction($x, $p, $q) / $p;
		} else {
			return 1.0 - ($beta_gam * self::_betaFraction(1 - $x, $q, $p) / $q);
		}
	}	//	function _incompleteBeta()


	/**
	 * BETADIST
	 *
	 * Returns the beta distribution.
	 *
	 * @param	float		$value			Value at which you want to evaluate the distribution
	 * @param	float		$alpha			Parameter to the distribution
	 * @param	float		$beta			Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function BETADIST($value,$alpha,$beta,$rMin=0,$rMax=1) {
		$value	= self::flattenSingleValue($value);
		$alpha	= self::flattenSingleValue($alpha);
		$beta	= self::flattenSingleValue($beta);
		$rMin	= self::flattenSingleValue($rMin);
		$rMax	= self::flattenSingleValue($rMax);

		if ((is_numeric($value)) && (is_numeric($alpha)) && (is_numeric($beta)) && (is_numeric($rMin)) && (is_numeric($rMax))) {
			if (($value < $rMin) || ($value > $rMax) || ($alpha <= 0) || ($beta <= 0) || ($rMin == $rMax)) {
				return self::$_errorCodes['num'];
			}
			if ($rMin > $rMax) {
				$tmp = $rMin;
				$rMin = $rMax;
				$rMax = $tmp;
			}
			$value -= $rMin;
			$value /= ($rMax - $rMin);
			return self::_incompleteBeta($value,$alpha,$beta);
		}
		return self::$_errorCodes['value'];
	}	//	function BETADIST()


	/**
	 * BETAINV
	 *
	 * Returns the inverse of the beta distribution.
	 *
	 * @param	float		$probability	Probability at which you want to evaluate the distribution
	 * @param	float		$alpha			Parameter to the distribution
	 * @param	float		$beta			Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function BETAINV($probability,$alpha,$beta,$rMin=0,$rMax=1) {
		$probability	= self::flattenSingleValue($probability);
		$alpha			= self::flattenSingleValue($alpha);
		$beta			= self::flattenSingleValue($beta);
		$rMin			= self::flattenSingleValue($rMin);
		$rMax			= self::flattenSingleValue($rMax);

		if ((is_numeric($probability)) && (is_numeric($alpha)) && (is_numeric($beta)) && (is_numeric($rMin)) && (is_numeric($rMax))) {
			if (($alpha <= 0) || ($beta <= 0) || ($rMin == $rMax) || ($probability <= 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if ($rMin > $rMax) {
				$tmp = $rMin;
				$rMin = $rMax;
				$rMax = $tmp;
			}
			$a = 0;
			$b = 2;
			$maxIteration = 100;

			$i = 0;
			while ((($b - $a) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				$guess = ($a + $b) / 2;
				$result = self::BETADIST($guess, $alpha, $beta);
				if (($result == $probability) || ($result == 0)) {
					$b = $a;
				} elseif ($result > $probability) {
					$b = $guess;
				} else {
					$a = $guess;
				}
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($rMin + $guess * ($rMax - $rMin),12);
		}
		return self::$_errorCodes['value'];
	}	//	function BETAINV()


	//
	//	Private implementation of the incomplete Gamma function
	//
	private static function _incompleteGamma($a,$x) {
		static $max = 32;
		$summer = 0;
		for ($n=0; $n<=$max; ++$n) {
			$divisor = $a;
			for ($i=1; $i<=$n; ++$i) {
				$divisor *= ($a + $i);
			}
			$summer += (pow($x,$n) / $divisor);
		}
		return pow($x,$a) * exp(0-$x) * $summer;
	}	//	function _incompleteGamma()


	//
	//	Private implementation of the Gamma function
	//
	private static function _gamma($data) {
		if ($data == 0.0) return 0;

		static $p0 = 1.000000000190015;
		static $p = array ( 1 => 76.18009172947146,
							2 => -86.50532032941677,
							3 => 24.01409824083091,
							4 => -1.231739572450155,
							5 => 1.208650973866179e-3,
							6 => -5.395239384953e-6
						  );

		$y = $x = $data;
		$tmp = $x + 5.5;
		$tmp -= ($x + 0.5) * log($tmp);

		$summer = $p0;
		for ($j=1;$j<=6;++$j) {
			$summer += ($p[$j] / ++$y);
		}
		return exp(0 - $tmp + log(2.5066282746310005 * $summer / $x));
	}	//	function _gamma()


	/**
	 * GAMMADIST
	 *
	 * Returns the gamma distribution.
	 *
	 * @param	float		$value			Value at which you want to evaluate the distribution
	 * @param	float		$a				Parameter to the distribution
	 * @param	float		$b				Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function GAMMADIST($value,$a,$b,$cumulative) {
		$value	= self::flattenSingleValue($value);
		$a		= self::flattenSingleValue($a);
		$b		= self::flattenSingleValue($b);

		if ((is_numeric($value)) && (is_numeric($a)) && (is_numeric($b))) {
			if (($value < 0) || ($a <= 0) || ($b <= 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return self::_incompleteGamma($a,$value / $b) / self::_gamma($a);
				} else {
					return (1 / (pow($b,$a) * self::_gamma($a))) * pow($value,$a-1) * exp(0-($value / $b));
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function GAMMADIST()


	/**
	 * GAMMAINV
	 *
	 * Returns the inverse of the beta distribution.
	 *
	 * @param	float		$probability	Probability at which you want to evaluate the distribution
	 * @param	float		$alpha			Parameter to the distribution
	 * @param	float		$beta			Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function GAMMAINV($probability,$alpha,$beta) {
		$probability	= self::flattenSingleValue($probability);
		$alpha			= self::flattenSingleValue($alpha);
		$beta			= self::flattenSingleValue($beta);
//		$rMin			= self::flattenSingleValue($rMin);
//		$rMax			= self::flattenSingleValue($rMax);

		if ((is_numeric($probability)) && (is_numeric($alpha)) && (is_numeric($beta))) {
			if (($alpha <= 0) || ($beta <= 0) || ($probability <= 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			$xLo = 0;
			$xHi = 100;
			$maxIteration = 100;

			$x = $xNew = 1;
			$dx	= 1;
			$i = 0;

			while ((abs($dx) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				// Apply Newton-Raphson step
				$result = self::GAMMADIST($x, $alpha, $beta, True);
				$error = $result - $probability;
				if ($error == 0.0) {
					$dx = 0;
				} elseif ($error < 0.0) {
					$xLo = $x;
				} else {
					$xHi = $x;
				}
				// Avoid division by zero
				if ($result != 0.0) {
					$dx = $error / $result;
					$xNew = $x - $dx;
				}
				// If the NR fails to converge (which for example may be the
				// case if the initial guess is too rough) we apply a bisection
				// step to determine a more narrow interval around the root.
				if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
					$xNew = ($xLo + $xHi) / 2;
					$dx = $xNew - $x;
				}
				$x = $xNew;
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($x,12);
		}
		return self::$_errorCodes['value'];
	}	//	function GAMMAINV()


	/**
	 * GAMMALN
	 *
	 * Returns the natural logarithm of the gamma function.
	 *
	 * @param	float		$value
	 * @return	float
	 */
	public static function GAMMALN($value) {
		$value	= self::flattenSingleValue($value);

		if (is_numeric($value)) {
			if ($value <= 0) {
				return self::$_errorCodes['num'];
			}
			return log(self::_gamma($value));
		}
		return self::$_errorCodes['value'];
	}	//	function GAMMALN()


	/**
	 * NORMDIST
	 *
	 * Returns the normal distribution for the specified mean and standard deviation. This
	 * function has a very wide range of applications in statistics, including hypothesis
	 * testing.
	 *
	 * @param	float		$value
	 * @param	float		$mean		Mean Value
	 * @param	float		$stdDev		Standard Deviation
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function NORMDIST($value, $mean, $stdDev, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);
		$stdDev	= self::flattenSingleValue($stdDev);

		if ((is_numeric($value)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if ($stdDev < 0) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return 0.5 * (1 + self::_erfVal(($value - $mean) / ($stdDev * sqrt(2))));
				} else {
					return (1 / (SQRT2PI * $stdDev)) * exp(0 - (pow($value - $mean,2) / (2 * pow($stdDev,2))));
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function NORMDIST()


	/**
	 * NORMSDIST
	 *
	 * Returns the standard normal cumulative distribution function. The distribution has
	 * a mean of 0 (zero) and a standard deviation of one. Use this function in place of a
	 * table of standard normal curve areas.
	 *
	 * @param	float		$value
	 * @return	float
	 */
	public static function NORMSDIST($value) {
		$value	= self::flattenSingleValue($value);

		return self::NORMDIST($value, 0, 1, True);
	}	//	function NORMSDIST()


	/**
	 * LOGNORMDIST
	 *
	 * Returns the cumulative lognormal distribution of x, where ln(x) is normally distributed
	 * with parameters mean and standard_dev.
	 *
	 * @param	float		$value
	 * @return	float
	 */
	public static function LOGNORMDIST($value, $mean, $stdDev) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);
		$stdDev	= self::flattenSingleValue($stdDev);

		if ((is_numeric($value)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if (($value <= 0) || ($stdDev <= 0)) {
				return self::$_errorCodes['num'];
			}
			return self::NORMSDIST((log($value) - $mean) / $stdDev);
		}
		return self::$_errorCodes['value'];
	}	//	function LOGNORMDIST()


	/***************************************************************************
	 *								inverse_ncdf.php
	 *							-------------------
	 *	begin				: Friday, January 16, 2004
	 *	copyright			: (C) 2004 Michael Nickerson
	 *	email				: nickersonm@yahoo.com
	 *
	 ***************************************************************************/
	private static function _inverse_ncdf($p) {
		//	Inverse ncdf approximation by Peter J. Acklam, implementation adapted to
		//	PHP by Michael Nickerson, using Dr. Thomas Ziegler's C implementation as
		//	a guide. http://home.online.no/~pjacklam/notes/invnorm/index.html
		//	I have not checked the accuracy of this implementation. Be aware that PHP
		//	will truncate the coeficcients to 14 digits.

		//	You have permission to use and distribute this function freely for
		//	whatever purpose you want, but please show common courtesy and give credit
		//	where credit is due.

		//	Input paramater is $p - probability - where 0 < p < 1.

		//	Coefficients in rational approximations
		static $a = array(	1 => -3.969683028665376e+01,
							2 => 2.209460984245205e+02,
							3 => -2.759285104469687e+02,
							4 => 1.383577518672690e+02,
							5 => -3.066479806614716e+01,
							6 => 2.506628277459239e+00
						 );

		static $b = array(	1 => -5.447609879822406e+01,
							2 => 1.615858368580409e+02,
							3 => -1.556989798598866e+02,
							4 => 6.680131188771972e+01,
							5 => -1.328068155288572e+01
						 );

		static $c = array(	1 => -7.784894002430293e-03,
							2 => -3.223964580411365e-01,
							3 => -2.400758277161838e+00,
							4 => -2.549732539343734e+00,
							5 => 4.374664141464968e+00,
							6 => 2.938163982698783e+00
						 );

		static $d = array(	1 => 7.784695709041462e-03,
							2 => 3.224671290700398e-01,
							3 => 2.445134137142996e+00,
							4 => 3.754408661907416e+00
						 );

		//	Define lower and upper region break-points.
		$p_low = 0.02425;			//Use lower region approx. below this
		$p_high = 1 - $p_low;		//Use upper region approx. above this

		if (0 < $p && $p < $p_low) {
			//	Rational approximation for lower region.
			$q = sqrt(-2 * log($p));
			return ((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) * $q + $c[6]) /
					(((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) * $q + 1);
		} elseif ($p_low <= $p && $p <= $p_high) {
			//	Rational approximation for central region.
			$q = $p - 0.5;
			$r = $q * $q;
			return ((((($a[1] * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $r + $a[6]) * $q /
				   ((((($b[1] * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + $b[5]) * $r + 1);
		} elseif ($p_high < $p && $p < 1) {
			//	Rational approximation for upper region.
			$q = sqrt(-2 * log(1 - $p));
			return -((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) * $q + $c[6]) /
					 (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) * $q + 1);
		}
		//	If 0 < p < 1, return a null value
		return self::$_errorCodes['null'];
	}	//	function _inverse_ncdf()


	private static function _inverse_ncdf2($prob) {
		//	Approximation of inverse standard normal CDF developed by
		//	B. Moro, "The Full Monte," Risk 8(2), Feb 1995, 57-58.

		$a1 = 2.50662823884;
		$a2 = -18.61500062529;
		$a3 = 41.39119773534;
		$a4 = -25.44106049637;

		$b1 = -8.4735109309;
		$b2 = 23.08336743743;
		$b3 = -21.06224101826;
		$b4 = 3.13082909833;

		$c1 = 0.337475482272615;
		$c2 = 0.976169019091719;
		$c3 = 0.160797971491821;
		$c4 = 2.76438810333863E-02;
		$c5 = 3.8405729373609E-03;
		$c6 = 3.951896511919E-04;
		$c7 = 3.21767881768E-05;
		$c8 = 2.888167364E-07;
		$c9 = 3.960315187E-07;

		$y = $prob - 0.5;
		if (abs($y) < 0.42) {
			$z = pow($y,2);
			$z = $y * ((($a4 * $z + $a3) * $z + $a2) * $z + $a1) / (((($b4 * $z + $b3) * $z + $b2) * $z + $b1) * $z + 1);
		} else {
			if ($y > 0) {
				$z = log(-log(1 - $prob));
			} else {
				$z = log(-log($prob));
			}
			$z = $c1 + $z * ($c2 + $z * ($c3 + $z * ($c4 + $z * ($c5 + $z * ($c6 + $z * ($c7 + $z * ($c8 + $z * $c9)))))));
			if ($y < 0) {
				$z = -$z;
			}
		}
		return $z;
	}	//	function _inverse_ncdf2()


	private static function _inverse_ncdf3($p) {
		//	ALGORITHM AS241 APPL. STATIST. (1988) VOL. 37, NO. 3.
		//	Produces the normal deviate Z corresponding to a given lower
		//	tail area of P; Z is accurate to about 1 part in 10**16.
		//
		//	This is a PHP version of the original FORTRAN code that can
		//	be found at http://lib.stat.cmu.edu/apstat/
		$split1 = 0.425;
		$split2 = 5;
		$const1 = 0.180625;
		$const2 = 1.6;

		//	coefficients for p close to 0.5
		$a0 = 3.3871328727963666080;
		$a1 = 1.3314166789178437745E+2;
		$a2 = 1.9715909503065514427E+3;
		$a3 = 1.3731693765509461125E+4;
		$a4 = 4.5921953931549871457E+4;
		$a5 = 6.7265770927008700853E+4;
		$a6 = 3.3430575583588128105E+4;
		$a7 = 2.5090809287301226727E+3;

		$b1 = 4.2313330701600911252E+1;
		$b2 = 6.8718700749205790830E+2;
		$b3 = 5.3941960214247511077E+3;
		$b4 = 2.1213794301586595867E+4;
		$b5 = 3.9307895800092710610E+4;
		$b6 = 2.8729085735721942674E+4;
		$b7 = 5.2264952788528545610E+3;

		//	coefficients for p not close to 0, 0.5 or 1.
		$c0 = 1.42343711074968357734;
		$c1 = 4.63033784615654529590;
		$c2 = 5.76949722146069140550;
		$c3 = 3.64784832476320460504;
		$c4 = 1.27045825245236838258;
		$c5 = 2.41780725177450611770E-1;
		$c6 = 2.27238449892691845833E-2;
		$c7 = 7.74545014278341407640E-4;

		$d1 = 2.05319162663775882187;
		$d2 = 1.67638483018380384940;
		$d3 = 6.89767334985100004550E-1;
		$d4 = 1.48103976427480074590E-1;
		$d5 = 1.51986665636164571966E-2;
		$d6 = 5.47593808499534494600E-4;
		$d7 = 1.05075007164441684324E-9;

		//	coefficients for p near 0 or 1.
		$e0 = 6.65790464350110377720;
		$e1 = 5.46378491116411436990;
		$e2 = 1.78482653991729133580;
		$e3 = 2.96560571828504891230E-1;
		$e4 = 2.65321895265761230930E-2;
		$e5 = 1.24266094738807843860E-3;
		$e6 = 2.71155556874348757815E-5;
		$e7 = 2.01033439929228813265E-7;

		$f1 = 5.99832206555887937690E-1;
		$f2 = 1.36929880922735805310E-1;
		$f3 = 1.48753612908506148525E-2;
		$f4 = 7.86869131145613259100E-4;
		$f5 = 1.84631831751005468180E-5;
		$f6 = 1.42151175831644588870E-7;
		$f7 = 2.04426310338993978564E-15;

		$q = $p - 0.5;

		//	computation for p close to 0.5
		if (abs($q) <= split1) {
			$R = $const1 - $q * $q;
			$z = $q * ((((((($a7 * $R + $a6) * $R + $a5) * $R + $a4) * $R + $a3) * $R + $a2) * $R + $a1) * $R + $a0) /
					  ((((((($b7 * $R + $b6) * $R + $b5) * $R + $b4) * $R + $b3) * $R + $b2) * $R + $b1) * $R + 1);
		} else {
			if ($q < 0) {
				$R = $p;
			} else {
				$R = 1 - $p;
			}
			$R = pow(-log($R),2);

			//	computation for p not close to 0, 0.5 or 1.
			If ($R <= $split2) {
				$R = $R - $const2;
				$z = ((((((($c7 * $R + $c6) * $R + $c5) * $R + $c4) * $R + $c3) * $R + $c2) * $R + $c1) * $R + $c0) /
					 ((((((($d7 * $R + $d6) * $R + $d5) * $R + $d4) * $R + $d3) * $R + $d2) * $R + $d1) * $R + 1);
			} else {
			//	computation for p near 0 or 1.
				$R = $R - $split2;
				$z = ((((((($e7 * $R + $e6) * $R + $e5) * $R + $e4) * $R + $e3) * $R + $e2) * $R + $e1) * $R + $e0) /
					 ((((((($f7 * $R + $f6) * $R + $f5) * $R + $f4) * $R + $f3) * $R + $f2) * $R + $f1) * $R + 1);
			}
			if ($q < 0) {
				$z = -$z;
			}
		}
		return $z;
	}	//	function _inverse_ncdf3()


	/**
	 * NORMINV
	 *
	 * Returns the inverse of the normal cumulative distribution for the specified mean and standard deviation.
	 *
	 * @param	float		$value
	 * @param	float		$mean		Mean Value
	 * @param	float		$stdDev		Standard Deviation
	 * @return	float
	 *
	 */
	public static function NORMINV($probability,$mean,$stdDev) {
		$probability	= self::flattenSingleValue($probability);
		$mean			= self::flattenSingleValue($mean);
		$stdDev			= self::flattenSingleValue($stdDev);

		if ((is_numeric($probability)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if ($stdDev < 0) {
				return self::$_errorCodes['num'];
			}
			return (self::_inverse_ncdf($probability) * $stdDev) + $mean;
		}
		return self::$_errorCodes['value'];
	}	//	function NORMINV()


	/**
	 * NORMSINV
	 *
	 * Returns the inverse of the standard normal cumulative distribution
	 *
	 * @param	float		$value
	 * @return	float
	 */
	public static function NORMSINV($value) {
		return self::NORMINV($value, 0, 1);
	}	//	function NORMSINV()


	/**
	 * LOGINV
	 *
	 * Returns the inverse of the normal cumulative distribution
	 *
	 * @param	float		$value
	 * @return	float
	 *
	 * @todo	Try implementing P J Acklam's refinement algorithm for greater
	 *			accuracy if I can get my head round the mathematics
	 *			(as described at) http://home.online.no/~pjacklam/notes/invnorm/
	 */
	public static function LOGINV($probability, $mean, $stdDev) {
		$probability	= self::flattenSingleValue($probability);
		$mean			= self::flattenSingleValue($mean);
		$stdDev			= self::flattenSingleValue($stdDev);

		if ((is_numeric($probability)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if (($probability < 0) || ($probability > 1) || ($stdDev <= 0)) {
				return self::$_errorCodes['num'];
			}
			return exp($mean + $stdDev * self::NORMSINV($probability));
		}
		return self::$_errorCodes['value'];
	}	//	function LOGINV()


	/**
	 * HYPGEOMDIST
	 *
	 * Returns the hypergeometric distribution. HYPGEOMDIST returns the probability of a given number of
	 * sample successes, given the sample size, population successes, and population size.
	 *
	 * @param	float		$sampleSuccesses		Number of successes in the sample
	 * @param	float		$sampleNumber			Size of the sample
	 * @param	float		$populationSuccesses	Number of successes in the population
	 * @param	float		$populationNumber		Population size
	 * @return	float
	 *
	 */
	public static function HYPGEOMDIST($sampleSuccesses, $sampleNumber, $populationSuccesses, $populationNumber) {
		$sampleSuccesses		= floor(self::flattenSingleValue($sampleSuccesses));
		$sampleNumber			= floor(self::flattenSingleValue($sampleNumber));
		$populationSuccesses	= floor(self::flattenSingleValue($populationSuccesses));
		$populationNumber		= floor(self::flattenSingleValue($populationNumber));

		if ((is_numeric($sampleSuccesses)) && (is_numeric($sampleNumber)) && (is_numeric($populationSuccesses)) && (is_numeric($populationNumber))) {
			if (($sampleSuccesses < 0) || ($sampleSuccesses > $sampleNumber) || ($sampleSuccesses > $populationSuccesses)) {
				return self::$_errorCodes['num'];
			}
			if (($sampleNumber <= 0) || ($sampleNumber > $populationNumber)) {
				return self::$_errorCodes['num'];
			}
			if (($populationSuccesses <= 0) || ($populationSuccesses > $populationNumber)) {
				return self::$_errorCodes['num'];
			}
			return self::COMBIN($populationSuccesses,$sampleSuccesses) *
				   self::COMBIN($populationNumber - $populationSuccesses,$sampleNumber - $sampleSuccesses) /
				   self::COMBIN($populationNumber,$sampleNumber);
		}
		return self::$_errorCodes['value'];
	}	//	function HYPGEOMDIST()


	/**
	 * TDIST
	 *
	 * Returns the probability of Student's T distribution.
	 *
	 * @param	float		$value			Value for the function
	 * @param	float		$degrees		degrees of freedom
	 * @param	float		$tails			number of tails (1 or 2)
	 * @return	float
	 */
	public static function TDIST($value, $degrees, $tails) {
		$value		= self::flattenSingleValue($value);
		$degrees	= floor(self::flattenSingleValue($degrees));
		$tails		= floor(self::flattenSingleValue($tails));

		if ((is_numeric($value)) && (is_numeric($degrees)) && (is_numeric($tails))) {
			if (($value < 0) || ($degrees < 1) || ($tails < 1) || ($tails > 2)) {
				return self::$_errorCodes['num'];
			}
			//	tdist, which finds the probability that corresponds to a given value
			//	of t with k degrees of freedom. This algorithm is translated from a
			//	pascal function on p81 of "Statistical Computing in Pascal" by D
			//	Cooke, A H Craven & G M Clark (1985: Edward Arnold (Pubs.) Ltd:
			//	London). The above Pascal algorithm is itself a translation of the
			//	fortran algoritm "AS 3" by B E Cooper of the Atlas Computer
			//	Laboratory as reported in (among other places) "Applied Statistics
			//	Algorithms", editied by P Griffiths and I D Hill (1985; Ellis
			//	Horwood Ltd.; W. Sussex, England).
//			$ta = 2 / pi();
			$ta = 0.636619772367581;
			$tterm = $degrees;
			$ttheta = atan2($value,sqrt($tterm));
			$tc = cos($ttheta);
			$ts = sin($ttheta);
			$tsum = 0;

			if (($degrees % 2) == 1) {
				$ti = 3;
				$tterm = $tc;
			} else {
				$ti = 2;
				$tterm = 1;
			}

			$tsum = $tterm;
			while ($ti < $degrees) {
				$tterm *= $tc * $tc * ($ti - 1) / $ti;
				$tsum += $tterm;
				$ti += 2;
			}
			$tsum *= $ts;
			if (($degrees % 2) == 1) { $tsum = $ta * ($tsum + $ttheta); }
			$tValue = 0.5 * (1 + $tsum);
			if ($tails == 1) {
				return 1 - abs($tValue);
			} else {
				return 1 - abs((1 - $tValue) - $tValue);
			}
		}
		return self::$_errorCodes['value'];
	}	//	function TDIST()


	/**
	 * TINV
	 *
	 * Returns the one-tailed probability of the chi-squared distribution.
	 *
	 * @param	float		$probability	Probability for the function
	 * @param	float		$degrees		degrees of freedom
	 * @return	float
	 */
	public static function TINV($probability, $degrees) {
		$probability	= self::flattenSingleValue($probability);
		$degrees		= floor(self::flattenSingleValue($degrees));

		if ((is_numeric($probability)) && (is_numeric($degrees))) {
			$xLo = 100;
			$xHi = 0;
			$maxIteration = 100;

			$x = $xNew = 1;
			$dx	= 1;
			$i = 0;

			while ((abs($dx) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				// Apply Newton-Raphson step
				$result = self::TDIST($x, $degrees, 2);
				$error = $result - $probability;
				if ($error == 0.0) {
					$dx = 0;
				} elseif ($error < 0.0) {
					$xLo = $x;
				} else {
					$xHi = $x;
				}
				// Avoid division by zero
				if ($result != 0.0) {
					$dx = $error / $result;
					$xNew = $x - $dx;
				}
				// If the NR fails to converge (which for example may be the
				// case if the initial guess is too rough) we apply a bisection
				// step to determine a more narrow interval around the root.
				if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
					$xNew = ($xLo + $xHi) / 2;
					$dx = $xNew - $x;
				}
				$x = $xNew;
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($x,12);
		}
		return self::$_errorCodes['value'];
	}	//	function TINV()


	/**
	 * CONFIDENCE
	 *
	 * Returns the confidence interval for a population mean
	 *
	 * @param	float		$alpha
	 * @param	float		$stdDev		Standard Deviation
	 * @param	float		$size
	 * @return	float
	 *
	 */
	public static function CONFIDENCE($alpha,$stdDev,$size) {
		$alpha	= self::flattenSingleValue($alpha);
		$stdDev	= self::flattenSingleValue($stdDev);
		$size	= floor(self::flattenSingleValue($size));

		if ((is_numeric($alpha)) && (is_numeric($stdDev)) && (is_numeric($size))) {
			if (($alpha <= 0) || ($alpha >= 1)) {
				return self::$_errorCodes['num'];
			}
			if (($stdDev <= 0) || ($size < 1)) {
				return self::$_errorCodes['num'];
			}
			return self::NORMSINV(1 - $alpha / 2) * $stdDev / sqrt($size);
		}
		return self::$_errorCodes['value'];
	}	//	function CONFIDENCE()


	/**
	 * POISSON
	 *
	 * Returns the Poisson distribution. A common application of the Poisson distribution
	 * is predicting the number of events over a specific time, such as the number of
	 * cars arriving at a toll plaza in 1 minute.
	 *
	 * @param	float		$value
	 * @param	float		$mean		Mean Value
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function POISSON($value, $mean, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);

		if ((is_numeric($value)) && (is_numeric($mean))) {
			if (($value <= 0) || ($mean <= 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					$summer = 0;
					for ($i = 0; $i <= floor($value); ++$i) {
						$summer += pow($mean,$i) / self::FACT($i);
					}
					return exp(0-$mean) * $summer;
				} else {
					return (exp(0-$mean) * pow($mean,$value)) / self::FACT($value);
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function POISSON()


	/**
	 * WEIBULL
	 *
	 * Returns the Weibull distribution. Use this distribution in reliability
	 * analysis, such as calculating a device's mean time to failure.
	 *
	 * @param	float		$value
	 * @param	float		$alpha		Alpha Parameter
	 * @param	float		$beta		Beta Parameter
	 * @param	boolean		$cumulative
	 * @return	float
	 *
	 */
	public static function WEIBULL($value, $alpha, $beta, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$alpha	= self::flattenSingleValue($alpha);
		$beta	= self::flattenSingleValue($beta);

		if ((is_numeric($value)) && (is_numeric($alpha)) && (is_numeric($beta))) {
			if (($value < 0) || ($alpha <= 0) || ($beta <= 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return 1 - exp(0 - pow($value / $beta,$alpha));
				} else {
					return ($alpha / pow($beta,$alpha)) * pow($value,$alpha - 1) * exp(0 - pow($value / $beta,$alpha));
				}
			}
		}
		return self::$_errorCodes['value'];
	}	//	function WEIBULL()


	/**
	 * SKEW
	 *
	 * Returns the skewness of a distribution. Skewness characterizes the degree of asymmetry
	 * of a distribution around its mean. Positive skewness indicates a distribution with an
	 * asymmetric tail extending toward more positive values. Negative skewness indicates a
	 * distribution with an asymmetric tail extending toward more negative values.
	 *
	 * @param	array	Data Series
	 * @return	float
	 */
	public static function SKEW() {
		$aArgs = self::flattenArray(func_get_args());
		$mean = self::AVERAGE($aArgs);
		$stdDev = self::STDEV($aArgs);

		$count = $summer = 0;
		// Loop through arguments
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$summer += pow((($arg - $mean) / $stdDev),3) ;
				++$count;
			}
		}

		// Return
		if ($count > 2) {
			return $summer * ($count / (($count-1) * ($count-2)));
		}
		return self::$_errorCodes['divisionbyzero'];
	}	//	function SKEW()


	/**
	 * KURT
	 *
	 * Returns the kurtosis of a data set. Kurtosis characterizes the relative peakedness
	 * or flatness of a distribution compared with the normal distribution. Positive
	 * kurtosis indicates a relatively peaked distribution. Negative kurtosis indicates a
	 * relatively flat distribution.
	 *
	 * @param	array	Data Series
	 * @return	float
	 */
	public static function KURT() {
		$aArgs = self::flattenArray(func_get_args());
		$mean = self::AVERAGE($aArgs);
		$stdDev = self::STDEV($aArgs);

		if ($stdDev > 0) {
			$count = $summer = 0;
			// Loop through arguments
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$summer += pow((($arg - $mean) / $stdDev),4) ;
					++$count;
				}
			}

			// Return
			if ($count > 3) {
				return $summer * ($count * ($count+1) / (($count-1) * ($count-2) * ($count-3))) - (3 * pow($count-1,2) / (($count-2) * ($count-3)));
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}	//	function KURT()


	/**
	 * RAND
	 *
	 * @param	int		$min	Minimal value
	 * @param	int		$max	Maximal value
	 * @return	int		Random number
	 */
	public static function RAND($min = 0, $max = 0) {
		$min		= self::flattenSingleValue($min);
		$max		= self::flattenSingleValue($max);

		if ($min == 0 && $max == 0) {
			return (rand(0,10000000)) / 10000000;
		} else {
			return rand($min, $max);
		}
	}	//	function RAND()


	/**
	 * MOD
	 *
	 * @param	int		$a		Dividend
	 * @param	int		$b		Divisor
	 * @return	int		Remainder
	 */
	public static function MOD($a = 1, $b = 1) {
		$a		= self::flattenSingleValue($a);
		$b		= self::flattenSingleValue($b);

		return fmod($a,$b);
	}	//	function MOD()


	/**
	 * CHARACTER
	 *
	 * @param	string	$character	Value
	 * @return	int
	 */
	public static function CHARACTER($character) {
		$character	= self::flattenSingleValue($character);

		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding('&#'.intval($character).';', 'UTF-8', 'HTML-ENTITIES');
		} else {
			return chr(intval($character));
		}
	}

	/**
	 * ASCIICODE
	 *
	 * @param	string	$character	Value
	 * @return	int
	 */
	public static function ASCIICODE($characters) {
		$characters	= self::flattenSingleValue($characters);
		if (is_bool($characters)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$characters = (int) $characters;
			} else {
				if ($characters) {
					$characters = 'True';
				} else {
					$characters = 'False';
				}
			}
		}

		if ((function_exists('mb_strlen')) && (function_exists('mb_substr'))) {
			if (mb_strlen($characters, 'UTF-8') > 0) {
				$character = mb_substr($characters, 0, 1, 'UTF-8');
				$byteLength = strlen($character);
				$xValue = 0;
				for ($i = 0; $i < $byteLength; ++$i) {
					$xValue = ($xValue * 256) + ord($character{$i});
				}
				return $xValue;
			}
		} else {
			if (strlen($characters) > 0) {
				return ord(substr($characters, 0, 1));
			}
		}
		return self::$_errorCodes['value'];
	}	//	function ASCIICODE()


	/**
	 * CONCATENATE
	 *
	 * @return	string
	 */
	public static function CONCATENATE() {
		// Return value
		$returnValue = '';

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			if (is_bool($arg)) {
				if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
					$arg = (int) $arg;
				} else {
					if ($arg) {
						$arg = 'TRUE';
					} else {
						$arg = 'FALSE';
					}
				}
			}
			$returnValue .= $arg;
		}

		// Return
		return $returnValue;
	}	//	function CONCATENATE()


	/**
	 * STRINGLENGTH
	 *
	 * @param	string	$value	Value
	 * @param	int		$chars	Number of characters
	 * @return	string
	 */
	public static function STRINGLENGTH($value = '') {
		$value		= self::flattenSingleValue($value);

		if (function_exists('mb_strlen')) {
			return mb_strlen($value, 'UTF-8');
		} else {
			return strlen($value);
		}
	}	//	function STRINGLENGTH()


	/**
	 * SEARCHSENSITIVE
	 *
	 * @param	string	$needle		The string to look for
	 * @param	string	$haystack	The string in which to look
	 * @param	int		$offset		Offset within $haystack
	 * @return	string
	 */
	public static function SEARCHSENSITIVE($needle,$haystack,$offset=1) {
		$needle		= (string) self::flattenSingleValue($needle);
		$haystack	= (string) self::flattenSingleValue($haystack);
		$offset		= self::flattenSingleValue($offset);

		if (($offset > 0) && (strlen($haystack) > $offset)) {
			if (function_exists('mb_strpos')) {
				$pos = mb_strpos($haystack, $needle, --$offset,'UTF-8');
			} else {
				$pos = strpos($haystack, $needle, --$offset);
			}
			if ($pos !== false) {
				return ++$pos;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function SEARCHSENSITIVE()


	/**
	 * SEARCHINSENSITIVE
	 *
	 * @param	string	$needle		The string to look for
	 * @param	string	$haystack	The string in which to look
	 * @param	int		$offset		Offset within $haystack
	 * @return	string
	 */
	public static function SEARCHINSENSITIVE($needle,$haystack,$offset=1) {
		$needle		= (string) self::flattenSingleValue($needle);
		$haystack	= (string) self::flattenSingleValue($haystack);
		$offset		= self::flattenSingleValue($offset);

		if (($offset > 0) && (strlen($haystack) > $offset)) {
			if (function_exists('mb_stripos')) {
				$pos = mb_stripos($haystack, $needle, --$offset,'UTF-8');
			} else {
				$pos = stripos($haystack, $needle, --$offset);
			}
			if ($pos !== false) {
				return ++$pos;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function SEARCHINSENSITIVE()


	/**
	 * LEFT
	 *
	 * @param	string	$value	Value
	 * @param	int		$chars	Number of characters
	 * @return	string
	 */
	public static function LEFT($value = '', $chars = 1) {
		$value		= self::flattenSingleValue($value);
		$chars		= self::flattenSingleValue($chars);

		if (function_exists('mb_substr')) {
			return mb_substr($value, 0, $chars, 'UTF-8');
		} else {
			return substr($value, 0, $chars);
		}
	}	//	function LEFT()


	/**
	 *	RIGHT
	 *
	 *	@param	string	$value	Value
	 *	@param	int		$chars	Number of characters
	 *	@return	string
	 */
	public static function RIGHT($value = '', $chars = 1) {
		$value		= self::flattenSingleValue($value);
		$chars		= self::flattenSingleValue($chars);

		if ((function_exists('mb_substr')) && (function_exists('mb_strlen'))) {
			return mb_substr($value, mb_strlen($value, 'UTF-8') - $chars, $chars, 'UTF-8');
		} else {
			return substr($value, strlen($value) - $chars);
		}
	}	//	function RIGHT()


	/**
	 *	MID
	 *
	 *	@param	string	$value	Value
	 *	@param	int		$start	Start character
	 *	@param	int		$chars	Number of characters
	 *	@return	string
	 */
	public static function MID($value = '', $start = 1, $chars = null) {
		$value		= self::flattenSingleValue($value);
		$start		= self::flattenSingleValue($start);
		$chars		= self::flattenSingleValue($chars);

		if (function_exists('mb_substr')) {
			return mb_substr($value, --$start, $chars, 'UTF-8');
		} else {
			return substr($value, --$start, $chars);
		}
	}	//	function MID()


	/**
	 *	REPLACE
	 *
	 *	@param	string	$value	Value
	 *	@param	int		$start	Start character
	 *	@param	int		$chars	Number of characters
	 *	@return	string
	 */
	public static function REPLACE($oldText = '', $start = 1, $chars = null, $newText) {
		$oldText	= self::flattenSingleValue($oldText);
		$start		= self::flattenSingleValue($start);
		$chars		= self::flattenSingleValue($chars);
		$newText	= self::flattenSingleValue($newText);

		$left = self::LEFT($oldText,$start-1);
		$right = self::RIGHT($oldText,self::STRINGLENGTH($oldText)-($start+$chars)+1);

		return $left.$newText.$right;
	}	//	function REPLACE()


	/**
	 *	RETURNSTRING
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function RETURNSTRING($testValue = '') {
		$testValue	= self::flattenSingleValue($testValue);

		if (is_string($testValue)) {
			return $testValue;
		}
		return Null;
	}	//	function RETURNSTRING()


	/**
	 *	FIXEDFORMAT
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function FIXEDFORMAT($value,$decimals=2,$no_commas=false) {
		$value		= self::flattenSingleValue($value);
		$decimals	= self::flattenSingleValue($decimals);
		$no_commas		= self::flattenSingleValue($no_commas);

		$valueResult = round($value,$decimals);
		if ($decimals < 0) { $decimals = 0; }
		if (!$no_commas) {
			$valueResult = number_format($valueResult,$decimals);
		}

		return (string) $valueResult;
	}	//	function FIXEDFORMAT()


	/**
	 *	TEXTFORMAT
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function TEXTFORMAT($value,$format) {
		$value	= self::flattenSingleValue($value);
		$format	= self::flattenSingleValue($format);

		return (string) PHPExcel_Style_NumberFormat::toFormattedString($value,$format);
	}	//	function TEXTFORMAT()


	/**
	 *	TRIMSPACES
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	string
	 */
	public static function TRIMSPACES($stringValue = '') {
		$stringValue	= self::flattenSingleValue($stringValue);

		if (is_string($stringValue) || is_numeric($stringValue)) {
			return trim(preg_replace('/  +/',' ',$stringValue));
		}
		return Null;
	}	//	function TRIMSPACES()


	private static $_invalidChars = Null;

	/**
	 *	TRIMNONPRINTABLE
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	string
	 */
	public static function TRIMNONPRINTABLE($stringValue = '') {
		$stringValue	= self::flattenSingleValue($stringValue);

		if (self::$_invalidChars == Null) {
			self::$_invalidChars = range(chr(0),chr(31));
		}

		if (is_string($stringValue) || is_numeric($stringValue)) {
			return str_replace(self::$_invalidChars,'',trim($stringValue,"\x00..\x1F"));
		}
		return Null;
	}	//	function TRIMNONPRINTABLE()


	/**
	 *	ERROR_TYPE
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function ERROR_TYPE($value = '') {
		$value	= self::flattenSingleValue($value);

		$i = 1;
		foreach(self::$_errorCodes as $errorCode) {
			if ($value == $errorCode) {
				return $i;
			}
			++$i;
		}
		return self::$_errorCodes['na'];
	}	//	function ERROR_TYPE()


	/**
	 *	IS_BLANK
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function IS_BLANK($value = null) {
		if (!is_null($value)) {
			$value	= self::flattenSingleValue($value);
		}

		return is_null($value);
	}	//	function IS_BLANK()


	/**
	 *	IS_ERR
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function IS_ERR($value = '') {
		$value		= self::flattenSingleValue($value);

		return self::IS_ERROR($value) && (!self::IS_NA($value));
	}	//	function IS_ERR()


	/**
	 *	IS_ERROR
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function IS_ERROR($value = '') {
		$value		= self::flattenSingleValue($value);

		return in_array($value, array_values(self::$_errorCodes));
	}	//	function IS_ERROR()


	/**
	 *	IS_NA
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function IS_NA($value = '') {
		$value		= self::flattenSingleValue($value);

		return ($value === self::$_errorCodes['na']);
	}	//	function IS_NA()


	/**
	 *	IS_EVEN
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function IS_EVEN($value = 0) {
		$value		= self::flattenSingleValue($value);

		if ((is_bool($value)) || ((is_string($value)) && (!is_numeric($value)))) {
			return self::$_errorCodes['value'];
		}
		return ($value % 2 == 0);
	}	//	function IS_EVEN()


	/**
	 *	IS_ODD
	 *
	 *	@param	mixed	$value	Value to check
	 *	@return	boolean
	 */
	public static function IS_ODD($value = null) {
		$value		= self::flattenSingleValue($value);

		if ((is_bool($value)) || ((is_string($value)) && (!is_numeric($value)))) {
			return self::$_errorCodes['value'];
		}
		return (abs($value) % 2 == 1);
	}	//	function IS_ODD()


	/**
	 *	IS_NUMBER
	 *
	 *	@param	mixed	$value		Value to check
	 *	@return	boolean
	 */
	public static function IS_NUMBER($value = 0) {
		$value		= self::flattenSingleValue($value);

		if (is_string($value)) {
			return False;
		}
		return is_numeric($value);
	}	//	function IS_NUMBER()


	/**
	 *	IS_LOGICAL
	 *
	 *	@param	mixed	$value		Value to check
	 *	@return	boolean
	 */
	public static function IS_LOGICAL($value = true) {
		$value		= self::flattenSingleValue($value);

		return is_bool($value);
	}	//	function IS_LOGICAL()


	/**
	 *	IS_TEXT
	 *
	 *	@param	mixed	$value		Value to check
	 *	@return	boolean
	 */
	public static function IS_TEXT($value = '') {
		$value		= self::flattenSingleValue($value);

		return is_string($value);
	}	//	function IS_TEXT()


	/**
	 *	IS_NONTEXT
	 *
	 *	@param	mixed	$value		Value to check
	 *	@return	boolean
	 */
	public static function IS_NONTEXT($value = '') {
		return !self::IS_TEXT($value);
	}	//	function IS_NONTEXT()


	/**
	 *	VERSION
	 *
	 *	@return	string	Version information
	 */
	public static function VERSION() {
		return 'PHPExcel 1.7.0, 2009-08-10';
	}	//	function VERSION()


	/**
	 * DATE
	 *
	 * @param	long	$year
	 * @param	long	$month
	 * @param	long	$day
	 * @return	mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATE($year = 0, $month = 1, $day = 1) {
		$year	= (integer) self::flattenSingleValue($year);
		$month	= (integer) self::flattenSingleValue($month);
		$day	= (integer) self::flattenSingleValue($day);

		$baseYear = PHPExcel_Shared_Date::getExcelCalendar();
		// Validate parameters
		if ($year < ($baseYear-1900)) {
			return self::$_errorCodes['num'];
		}
		if ((($baseYear-1900) != 0) && ($year < $baseYear) && ($year >= 1900)) {
			return self::$_errorCodes['num'];
		}

		if (($year < $baseYear) && ($year >= ($baseYear-1900))) {
			$year += 1900;
		}

		if ($month < 1) {
			//	Handle year/month adjustment if month < 1
			--$month;
			$year += ceil($month / 12) - 1;
			$month = 13 - abs($month % 12);
		} elseif ($month > 12) {
			//	Handle year/month adjustment if month > 12
			$year += floor($month / 12);
			$month = ($month % 12);
		}

		// Re-validate the year parameter after adjustments
		if (($year < $baseYear) || ($year >= 10000)) {
			return self::$_errorCodes['num'];
		}

		// Execute function
		$excelDateValue = PHPExcel_Shared_Date::FormattedPHPToExcel($year, $month, $day);
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) $excelDateValue;
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($excelDateValue);
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return PHPExcel_Shared_Date::ExcelToPHPObject($excelDateValue);
												  break;
		}
	}	//	function DATE()


	/**
	 * TIME
	 *
	 * @param	long	$hour
	 * @param	long	$minute
	 * @param	long	$second
	 * @return	mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function TIME($hour = 0, $minute = 0, $second = 0) {
		$hour	= self::flattenSingleValue($hour);
		$minute	= self::flattenSingleValue($minute);
		$second	= self::flattenSingleValue($second);

		if ($hour == '') { $hour = 0; }
		if ($minute == '') { $minute = 0; }
		if ($second == '') { $second = 0; }

		if ((!is_numeric($hour)) || (!is_numeric($minute)) || (!is_numeric($second))) {
			return self::$_errorCodes['value'];
		}
		$hour	= (integer) $hour;
		$minute	= (integer) $minute;
		$second	= (integer) $second;

		if ($second < 0) {
			$minute += floor($second / 60);
			$second = 60 - abs($second % 60);
			if ($second == 60) { $second = 0; }
		} elseif ($second >= 60) {
			$minute += floor($second / 60);
			$second = $second % 60;
		}
		if ($minute < 0) {
			$hour += floor($minute / 60);
			$minute = 60 - abs($minute % 60);
			if ($minute == 60) { $minute = 0; }
		} elseif ($minute >= 60) {
			$hour += floor($minute / 60);
			$minute = $minute % 60;
		}

		if ($hour > 23) {
			$hour = $hour % 24;
		} elseif ($hour < 0) {
			return self::$_errorCodes['num'];
		}

		// Execute function
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: $date = 0;
												  $calendar = PHPExcel_Shared_Date::getExcelCalendar();
												  if ($calendar != PHPExcel_Shared_Date::CALENDAR_WINDOWS_1900) {
													 $date = 1;
												  }
												  return (float) PHPExcel_Shared_Date::FormattedPHPToExcel($calendar, 1, $date, $hour, $minute, $second);
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP(PHPExcel_Shared_Date::FormattedPHPToExcel(1970, 1, 1, $hour-1, $minute, $second));	// -2147468400; //	-2147472000 + 3600
												  break;
			case self::RETURNDATE_PHP_OBJECT	: $dayAdjust = 0;
												  if ($hour < 0) {
													 $dayAdjust = floor($hour / 24);
													 $hour = 24 - abs($hour % 24);
													 if ($hour == 24) { $hour = 0; }
												  } elseif ($hour >= 24) {
													 $dayAdjust = floor($hour / 24);
													 $hour = $hour % 24;
												  }
												  $phpDateObject = new DateTime('1900-01-01 '.$hour.':'.$minute.':'.$second);
												  if ($dayAdjust != 0) {
													 $phpDateObject->modify($dayAdjust.' days');
												  }
												  return $phpDateObject;
												  break;
		}
	}	//	function TIME()


	/**
	 * DATEVALUE
	 *
	 * @param	string	$dateValue
	 * @return	mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATEVALUE($dateValue = 1) {
		$dateValue	= str_replace(array('/','.',' '),array('-','-','-'),self::flattenSingleValue(trim($dateValue,'"')));

		$yearFound = false;
		$t1 = explode('-',$dateValue);
		foreach($t1 as &$t) {
			if ((is_numeric($t)) && (($t > 31) && ($t < 100))) {
				if ($yearFound) {
					return self::$_errorCodes['value'];
				} else {
					$t += 1900;
					$yearFound = true;
				}
			}
		}
		unset($t);
		$dateValue = implode('-',$t1);

		$PHPDateArray = date_parse($dateValue);
		if (($PHPDateArray === False) || ($PHPDateArray['error_count'] > 0)) {
			$testVal1 = strtok($dateValue,'- ');
			if ($testVal1 !== False) {
				$testVal2 = strtok('- ');
				if ($testVal2 !== False) {
					$testVal3 = strtok('- ');
					if ($testVal3 === False) {
						$testVal3 = strftime('%Y');
					}
				} else {
					return self::$_errorCodes['value'];
				}
			} else {
				return self::$_errorCodes['value'];
			}
			$PHPDateArray = date_parse($testVal1.'-'.$testVal2.'-'.$testVal3);
			if (($PHPDateArray === False) || ($PHPDateArray['error_count'] > 0)) {
				$PHPDateArray = date_parse($testVal2.'-'.$testVal1.'-'.$testVal3);
				if (($PHPDateArray === False) || ($PHPDateArray['error_count'] > 0)) {
					return self::$_errorCodes['value'];
				}
			}
		}

		if (($PHPDateArray !== False) && ($PHPDateArray['error_count'] == 0)) {
			// Execute function
			if ($PHPDateArray['year'] == '')	{ $PHPDateArray['year'] = strftime('%Y'); }
			if ($PHPDateArray['month'] == '')	{ $PHPDateArray['month'] = strftime('%m'); }
			if ($PHPDateArray['day'] == '')		{ $PHPDateArray['day'] = strftime('%d'); }
			$excelDateValue = floor(PHPExcel_Shared_Date::FormattedPHPToExcel($PHPDateArray['year'],$PHPDateArray['month'],$PHPDateArray['day'],$PHPDateArray['hour'],$PHPDateArray['minute'],$PHPDateArray['second']));

			switch (self::getReturnDateType()) {
				case self::RETURNDATE_EXCEL			: return (float) $excelDateValue;
													  break;
				case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($excelDateValue);
													  break;
				case self::RETURNDATE_PHP_OBJECT	: return new DateTime($PHPDateArray['year'].'-'.$PHPDateArray['month'].'-'.$PHPDateArray['day'].' 00:00:00');
													  break;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function DATEVALUE()


	/**
	 * _getDateValue
	 *
	 * @param	string	$dateValue
	 * @return	mixed	Excel date/time serial value, or string if error
	 */
	private static function _getDateValue($dateValue) {
		if (!is_numeric($dateValue)) {
			if ((is_string($dateValue)) && (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC)) {
				return self::$_errorCodes['value'];
			}
			if ((is_object($dateValue)) && ($dateValue instanceof PHPExcel_Shared_Date::$dateTimeObjectType)) {
				$dateValue = PHPExcel_Shared_Date::PHPToExcel($dateValue);
			} else {
				$saveReturnDateType = self::getReturnDateType();
				self::setReturnDateType(self::RETURNDATE_EXCEL);
				$dateValue = self::DATEVALUE($dateValue);
				self::setReturnDateType($saveReturnDateType);
			}
		}
		return $dateValue;
	}	//	function _getDateValue()


	/**
	 * TIMEVALUE
	 *
	 * @param	string	$timeValue
	 * @return	mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function TIMEVALUE($timeValue) {
		$timeValue	= self::flattenSingleValue($timeValue);

		if ((($PHPDateArray = date_parse($timeValue)) !== False) && ($PHPDateArray['error_count'] == 0)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$excelDateValue = PHPExcel_Shared_Date::FormattedPHPToExcel($PHPDateArray['year'],$PHPDateArray['month'],$PHPDateArray['day'],$PHPDateArray['hour'],$PHPDateArray['minute'],$PHPDateArray['second']);
			} else {
				$excelDateValue = PHPExcel_Shared_Date::FormattedPHPToExcel(1900,1,1,$PHPDateArray['hour'],$PHPDateArray['minute'],$PHPDateArray['second']) - 1;
			}

			switch (self::getReturnDateType()) {
				case self::RETURNDATE_EXCEL			: return (float) $excelDateValue;
													  break;
				case self::RETURNDATE_PHP_NUMERIC	: return (integer) $phpDateValue = PHPExcel_Shared_Date::ExcelToPHP($excelDateValue+25569) - 3600;;
													  break;
				case self::RETURNDATE_PHP_OBJECT	: return new DateTime('1900-01-01 '.$PHPDateArray['hour'].':'.$PHPDateArray['minute'].':'.$PHPDateArray['second']);
													  break;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function TIMEVALUE()


	/**
	 * _getTimeValue
	 *
	 * @param	string	$timeValue
	 * @return	mixed	Excel date/time serial value, or string if error
	 */
	private static function _getTimeValue($timeValue) {
		$saveReturnDateType = self::getReturnDateType();
		self::setReturnDateType(self::RETURNDATE_EXCEL);
		$timeValue = self::TIMEVALUE($timeValue);
		self::setReturnDateType($saveReturnDateType);
		return $timeValue;
	}	//	function _getTimeValue()


	/**
	 * DATETIMENOW
	 *
	 * @return	mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATETIMENOW() {
		$saveTimeZone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$retValue = False;
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: $retValue = (float) PHPExcel_Shared_Date::PHPToExcel(time());
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: $retValue = (integer) time();
												  break;
			case self::RETURNDATE_PHP_OBJECT	: $retValue = new DateTime();
												  break;
		}
		date_default_timezone_set($saveTimeZone);

		return $retValue;
	}	//	function DATETIMENOW()


	/**
	 * DATENOW
	 *
	 * @return	mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATENOW() {
		$saveTimeZone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$retValue = False;
		$excelDateTime = floor(PHPExcel_Shared_Date::PHPToExcel(time()));
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: $retValue = (float) $excelDateTime;
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: $retValue = (integer) PHPExcel_Shared_Date::ExcelToPHP($excelDateTime) - 3600;
												  break;
			case self::RETURNDATE_PHP_OBJECT	: $retValue = PHPExcel_Shared_Date::ExcelToPHPObject($excelDateTime);
												  break;
		}
		date_default_timezone_set($saveTimeZone);

		return $retValue;
	}	//	function DATENOW()


	private static function _isLeapYear($year) {
		return ((($year % 4) == 0) && (($year % 100) != 0) || (($year % 400) == 0));
	}	//	function _isLeapYear()


	private static function _dateDiff360($startDay, $startMonth, $startYear, $endDay, $endMonth, $endYear, $methodUS) {
		if ($startDay == 31) {
			--$startDay;
		} elseif ($methodUS && ($startMonth == 2 && ($startDay == 29 || ($startDay == 28 && !self::_isLeapYear($startYear))))) {
			$startDay = 30;
		}
		if ($endDay == 31) {
			if ($methodUS && $startDay != 30) {
				$endDay = 1;
				if ($endMonth == 12) {
					++$endYear;
					$endMonth = 1;
				} else {
					++$endMonth;
				}
			} else {
				$endDay = 30;
			}
		}

		return $endDay + $endMonth * 30 + $endYear * 360 - $startDay - $startMonth * 30 - $startYear * 360;
	}	//	function _dateDiff360()


	/**
	 * DAYS360
	 *
	 * @param	long	$startDate		Excel date serial value or a standard date string
	 * @param	long	$endDate		Excel date serial value or a standard date string
	 * @param	boolean	$method			US or European Method
	 * @return	long	PHP date/time serial
	 */
	public static function DAYS360($startDate = 0, $endDate = 0, $method = false) {
		$startDate	= self::flattenSingleValue($startDate);
		$endDate	= self::flattenSingleValue($endDate);

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPStartDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($startDate);
		$startDay = $PHPStartDateObject->format('j');
		$startMonth = $PHPStartDateObject->format('n');
		$startYear = $PHPStartDateObject->format('Y');

		$PHPEndDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($endDate);
		$endDay = $PHPEndDateObject->format('j');
		$endMonth = $PHPEndDateObject->format('n');
		$endYear = $PHPEndDateObject->format('Y');

		return self::_dateDiff360($startDay, $startMonth, $startYear, $endDay, $endMonth, $endYear, !$method);
	}	//	function DAYS360()


	/**
	 * DATEDIF
	 *
	 * @param	long	$startDate		Excel date serial value or a standard date string
	 * @param	long	$endDate		Excel date serial value or a standard date string
	 * @param	string	$unit
	 * @return	long	Interval between the dates
	 */
	public static function DATEDIF($startDate = 0, $endDate = 0, $unit = 'D') {
		$startDate	= self::flattenSingleValue($startDate);
		$endDate	= self::flattenSingleValue($endDate);
		$unit		= strtoupper(self::flattenSingleValue($unit));

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		// Validate parameters
		if ($startDate >= $endDate) {
			return self::$_errorCodes['num'];
		}

		// Execute function
		$difference = $endDate - $startDate;

		$PHPStartDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($startDate);
		$startDays = $PHPStartDateObject->format('j');
		$startMonths = $PHPStartDateObject->format('n');
		$startYears = $PHPStartDateObject->format('Y');

		$PHPEndDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($endDate);
		$endDays = $PHPEndDateObject->format('j');
		$endMonths = $PHPEndDateObject->format('n');
		$endYears = $PHPEndDateObject->format('Y');

		$retVal = self::$_errorCodes['num'];
		switch ($unit) {
			case 'D':
				$retVal = intval($difference);
				break;
			case 'M':
				$retVal = intval($endMonths - $startMonths) + (intval($endYears - $startYears) * 12);
				//	We're only interested in full months
				if ($endDays < $startDays) {
					--$retVal;
				}
				break;
			case 'Y':
				$retVal = intval($endYears - $startYears);
				//	We're only interested in full months
				if ($endMonths < $startMonths) {
					--$retVal;
				} elseif (($endMonths == $startMonths) && ($endDays < $startDays)) {
					--$retVal;
				}
				break;
			case 'MD':
				if ($endDays < $startDays) {
					$retVal = $endDays;
					$PHPEndDateObject->modify('-'.$endDays.' days');
					$adjustDays = $PHPEndDateObject->format('j');
					if ($adjustDays > $startDays) {
						$retVal += ($adjustDays - $startDays);
					}
				} else {
					$retVal = $endDays - $startDays;
				}
				break;
			case 'YM':
				$retVal = abs(intval($endMonths - $startMonths));
				//	We're only interested in full months
				if ($endDays < $startDays) {
					--$retVal;
				}
				break;
			case 'YD':
				$retVal = intval($difference);
				if ($endYears > $startYears) {
					while ($endYears > $startYears) {
						$PHPEndDateObject->modify('-1 year');
						$endYears = $PHPEndDateObject->format('Y');
					}
					$retVal = abs($PHPEndDateObject->format('z') - $PHPStartDateObject->format('z'));
				}
				break;
		}
		return $retVal;
	}	//	function DATEDIF()


	/**
	 *	YEARFRAC
	 *
	 *	Calculates the fraction of the year represented by the number of whole days between two dates (the start_date and the
	 *	end_date). Use the YEARFRAC worksheet function to identify the proportion of a whole year's benefits or obligations
	 *	to assign to a specific term.
	 *
	 *	@param	mixed	$startDate		Excel date serial value (float), PHP date timestamp (integer) or date object, or a standard date string
	 *	@param	mixed	$endDate		Excel date serial value (float), PHP date timestamp (integer) or date object, or a standard date string
	 *	@param	integer	$method			Method used for the calculation
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float	fraction of the year
	 */
	public static function YEARFRAC($startDate = 0, $endDate = 0, $method = 0) {
		$startDate	= self::flattenSingleValue($startDate);
		$endDate	= self::flattenSingleValue($endDate);
		$method		= self::flattenSingleValue($method);

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		if ((is_numeric($method)) && (!is_string($method))) {
			switch($method) {
				case 0	:
					return self::DAYS360($startDate,$endDate) / 360;
					break;
				case 1	:
					$startYear = self::YEAR($startDate);
					$endYear = self::YEAR($endDate);
					$leapDay = 0;
					if (self::_isLeapYear($startYear) || self::_isLeapYear($endYear)) {
						$leapDay = 1;
					}
					return self::DATEDIF($startDate,$endDate) / (365 + $leapDay);
					break;
				case 2	:
					return self::DATEDIF($startDate,$endDate) / 360;
					break;
				case 3	:
					return self::DATEDIF($startDate,$endDate) / 365;
					break;
				case 4	:
					return self::DAYS360($startDate,$endDate,True) / 360;
					break;
			}
		}
		return self::$_errorCodes['value'];
	}	//	function YEARFRAC()


	/**
	 * NETWORKDAYS
	 *
	 * @param	mixed				Start date
	 * @param	mixed				End date
	 * @param	array of mixed		Optional Date Series
	 * @return	long	Interval between the dates
	 */
	public static function NETWORKDAYS($startDate,$endDate) {
		//	Flush the mandatory start and end date that are referenced in the function definition
		$dateArgs = self::flattenArray(func_get_args());
		array_shift($dateArgs);
		array_shift($dateArgs);

		//	Validate the start and end dates
		if (is_string($startDate = $sDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = $eDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		if ($sDate > $eDate) {
			$startDate = $eDate;
			$endDate = $sDate;
		}

		// Execute function
		$startDoW = 6 - self::DAYOFWEEK($startDate,2);
		if ($startDoW < 0) { $startDoW = 0; }
		$endDoW = self::DAYOFWEEK($endDate,2);
		if ($endDoW >= 6) { $endDoW = 0; }

		$wholeWeekDays = floor(($endDate - $startDate) / 7) * 5;
		$partWeekDays = $endDoW + $startDoW;
		if ($partWeekDays > 5) {
			$partWeekDays -= 5;
		}

		//	Test any extra holiday parameters
		$holidayCountedArray = array();
		foreach ($dateArgs as $holidayDate) {
			if (is_string($holidayDate = self::_getDateValue($holidayDate))) {
				return self::$_errorCodes['value'];
			}
			if (($holidayDate >= $startDate) && ($holidayDate <= $endDate)) {
				if ((self::DAYOFWEEK($holidayDate,2) < 6) && (!in_array($holidayDate,$holidayCountedArray))) {
					--$partWeekDays;
					$holidayCountedArray[] = $holidayDate;
				}
			}
		}

		if ($sDate > $eDate) {
			return 0 - ($wholeWeekDays + $partWeekDays);
		}
		return $wholeWeekDays + $partWeekDays;
	}	//	function NETWORKDAYS()


	/**
	 * WORKDAY
	 *
	 * @param	mixed				Start date
	 * @param	mixed				number of days for adjustment
	 * @param	array of mixed		Optional Date Series
	 * @return	long	Interval between the dates
	 */
	public static function WORKDAY($startDate,$endDays) {
		$dateArgs = self::flattenArray(func_get_args());

		array_shift($dateArgs);
		array_shift($dateArgs);

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (!is_numeric($endDays)) {
			return self::$_errorCodes['value'];
		}
		$endDate = (float) $startDate + (floor($endDays / 5) * 7) + ($endDays % 5);
		if ($endDays < 0) {
			$endDate += 7;
		}

		$endDoW = self::DAYOFWEEK($endDate,3);
		if ($endDoW >= 5) {
			if ($endDays >= 0) {
				$endDate += (7 - $endDoW);
			} else {
				$endDate -= ($endDoW - 5);
			}
		}

		//	Test any extra holiday parameters
		if (count($dateArgs) > 0) {
			$holidayCountedArray = $holidayDates = array();
			foreach ($dateArgs as $holidayDate) {
				if (is_string($holidayDate = self::_getDateValue($holidayDate))) {
					return self::$_errorCodes['value'];
				}
				$holidayDates[] = $holidayDate;
			}
			if ($endDays >= 0) {
				sort($holidayDates, SORT_NUMERIC);
			} else {
				rsort($holidayDates, SORT_NUMERIC);
			}
			foreach ($holidayDates as $holidayDate) {
				if ($endDays >= 0) {
					if (($holidayDate >= $startDate) && ($holidayDate <= $endDate)) {
						if ((self::DAYOFWEEK($holidayDate,2) < 6) && (!in_array($holidayDate,$holidayCountedArray))) {
							++$endDate;
							$holidayCountedArray[] = $holidayDate;
						}
					}
				} else {
					if (($holidayDate <= $startDate) && ($holidayDate >= $endDate)) {
						if ((self::DAYOFWEEK($holidayDate,2) < 6) && (!in_array($holidayDate,$holidayCountedArray))) {
							--$endDate;
							$holidayCountedArray[] = $holidayDate;
						}
					}
				}
				$endDoW = self::DAYOFWEEK($endDate,3);
				if ($endDoW >= 5) {
					if ($endDays >= 0) {
						$endDate += (7 - $endDoW);
					} else {
						$endDate -= ($endDoW - 5);
					}
				}
			}
		}

		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) $endDate;
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($endDate);
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return PHPExcel_Shared_Date::ExcelToPHPObject($endDate);
												  break;
		}
	}	//	function WORKDAY()


	/**
	 * DAYOFMONTH
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return	int		Day
	 */
	public static function DAYOFMONTH($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = &PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);

		return (int) $PHPDateObject->format('j');
	}	//	function DAYOFMONTH()


	/**
	 * DAYOFWEEK
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return	int		Day
	 */
	public static function DAYOFWEEK($dateValue = 1, $style = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);
		$style		= floor(self::flattenSingleValue($style));

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);
		$DoW = $PHPDateObject->format('w');

		$firstDay = 1;
		switch ($style) {
			case 1: ++$DoW;
					break;
			case 2: if ($DoW == 0) { $DoW = 7; }
					break;
			case 3: if ($DoW == 0) { $DoW = 7; }
					$firstDay = 0;
					--$DoW;
					break;
			default:
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_EXCEL) {
			//	Test for Excel's 1900 leap year, and introduce the error as required
			if (($PHPDateObject->format('Y') == 1900) && ($PHPDateObject->format('n') <= 2)) {
				--$DoW;
				if ($DoW < $firstDay) {
					$DoW += 7;
				}
			}
		}

		return (int) $DoW;
	}	//	function DAYOFWEEK()


	/**
	 * WEEKOFYEAR
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @param	boolean	$method			Week begins on Sunday or Monday
	 * @return	int		Week Number
	 */
	public static function WEEKOFYEAR($dateValue = 1, $method = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);
		$method		= floor(self::flattenSingleValue($method));

		if (!is_numeric($method)) {
			return self::$_errorCodes['value'];
		} elseif (($method < 1) || ($method > 2)) {
			return self::$_errorCodes['num'];
		}

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);
		$dayOfYear = $PHPDateObject->format('z');
		$dow = $PHPDateObject->format('w');
		$PHPDateObject->modify('-'.$dayOfYear.' days');
		$dow = $PHPDateObject->format('w');
		$daysInFirstWeek = 7 - (($dow + (2 - $method)) % 7);
		$dayOfYear -= $daysInFirstWeek;
		$weekOfYear = ceil($dayOfYear / 7) + 1;

		return (int) $weekOfYear;
	}	//	function WEEKOFYEAR()


	/**
	 * MONTHOFYEAR
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return	int		Month
	 */
	public static function MONTHOFYEAR($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);

		return (int) $PHPDateObject->format('n');
	}	//	function MONTHOFYEAR()


	/**
	 * YEAR
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return	int		Year
	 */
	public static function YEAR($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);

		return (int) $PHPDateObject->format('Y');
	}	//	function YEAR()


	/**
	 * HOUROFDAY
	 *
	 * @param	mixed	$timeValue		Excel time serial value or a standard time string
	 * @return	int		Hour
	 */
	public static function HOUROFDAY($timeValue = 0) {
		$timeValue	= self::flattenSingleValue($timeValue);

		if (!is_numeric($timeValue)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				$testVal = strtok($timeValue,'/-: ');
				if (strlen($testVal) < strlen($timeValue)) {
					return self::$_errorCodes['value'];
				}
			}
			$timeValue = self::_getTimeValue($timeValue);
			if (is_string($timeValue)) {
				return self::$_errorCodes['value'];
			}
		}
		// Execute function
		if (is_real($timeValue)) {
			if ($timeValue >= 1) {
				$timeValue = fmod($timeValue,1);
			} elseif ($timeValue < 0.0) {
				return self::$_errorCodes['num'];
			}
			$timeValue = PHPExcel_Shared_Date::ExcelToPHP($timeValue);
		}
		return (int) date('G',$timeValue);
	}	//	function HOUROFDAY()


	/**
	 * MINUTEOFHOUR
	 *
	 * @param	long	$timeValue		Excel time serial value or a standard time string
	 * @return	int		Minute
	 */
	public static function MINUTEOFHOUR($timeValue = 0) {
		$timeValue = $timeTester	= self::flattenSingleValue($timeValue);

		if (!is_numeric($timeValue)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				$testVal = strtok($timeValue,'/-: ');
				if (strlen($testVal) < strlen($timeValue)) {
					return self::$_errorCodes['value'];
				}
			}
			$timeValue = self::_getTimeValue($timeValue);
			if (is_string($timeValue)) {
				return self::$_errorCodes['value'];
			}
		}
		// Execute function
		if (is_real($timeValue)) {
			if ($timeValue >= 1) {
				$timeValue = fmod($timeValue,1);
			} elseif ($timeValue < 0.0) {
				return self::$_errorCodes['num'];
			}
			$timeValue = PHPExcel_Shared_Date::ExcelToPHP($timeValue);
		}
		return (int) date('i',$timeValue);
	}	//	function MINUTEOFHOUR()


	/**
	 * SECONDOFMINUTE
	 *
	 * @param	long	$timeValue		Excel time serial value or a standard time string
	 * @return	int		Second
	 */
	public static function SECONDOFMINUTE($timeValue = 0) {
		$timeValue	= self::flattenSingleValue($timeValue);

		if (!is_numeric($timeValue)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				$testVal = strtok($timeValue,'/-: ');
				if (strlen($testVal) < strlen($timeValue)) {
					return self::$_errorCodes['value'];
				}
			}
			$timeValue = self::_getTimeValue($timeValue);
			if (is_string($timeValue)) {
				return self::$_errorCodes['value'];
			}
		}
		// Execute function
		if (is_real($timeValue)) {
			if ($timeValue >= 1) {
				$timeValue = fmod($timeValue,1);
			} elseif ($timeValue < 0.0) {
				return self::$_errorCodes['num'];
			}
			$timeValue = PHPExcel_Shared_Date::ExcelToPHP($timeValue);
		}
		return (int) date('s',$timeValue);
	}	//	function SECONDOFMINUTE()


	private static function _adjustDateByMonths($dateValue = 0, $adjustmentMonths = 0) {
		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);
		$oMonth = (int) $PHPDateObject->format('m');
		$oYear = (int) $PHPDateObject->format('Y');

		$adjustmentMonthsString = (string) $adjustmentMonths;
		if ($adjustmentMonths > 0) {
			$adjustmentMonthsString = '+'.$adjustmentMonths;
		}
		if ($adjustmentMonths != 0) {
			$PHPDateObject->modify($adjustmentMonthsString.' months');
		}
		$nMonth = (int) $PHPDateObject->format('m');
		$nYear = (int) $PHPDateObject->format('Y');

		$monthDiff = ($nMonth - $oMonth) + (($nYear - $oYear) * 12);
		if ($monthDiff != $adjustmentMonths) {
			$adjustDays = (int) $PHPDateObject->format('d');
			$adjustDaysString = '-'.$adjustDays.' days';
			$PHPDateObject->modify($adjustDaysString);
		}
		return $PHPDateObject;
	}	//	function _adjustDateByMonths()


	/**
	 * EDATE
	 *
	 * Returns the serial number that represents the date that is the indicated number of months before or after a specified date
	 * (the start_date). Use EDATE to calculate maturity dates or due dates that fall on the same day of the month as the date of issue.
	 *
	 * @param	long	$dateValue				Excel date serial value or a standard date string
	 * @param	int		$adjustmentMonths		Number of months to adjust by
	 * @return	long	Excel date serial value
	 */
	public static function EDATE($dateValue = 1, $adjustmentMonths = 0) {
		$dateValue			= self::flattenSingleValue($dateValue);
		$adjustmentMonths	= floor(self::flattenSingleValue($adjustmentMonths));

		if (!is_numeric($adjustmentMonths)) {
			return self::$_errorCodes['value'];
		}

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = self::_adjustDateByMonths($dateValue,$adjustmentMonths);

		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) PHPExcel_Shared_Date::PHPToExcel($PHPDateObject);
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP(PHPExcel_Shared_Date::PHPToExcel($PHPDateObject));
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return $PHPDateObject;
												  break;
		}
	}	//	function EDATE()


	/**
	 * EOMONTH
	 *
	 * Returns the serial number for the last day of the month that is the indicated number of months before or after start_date.
	 * Use EOMONTH to calculate maturity dates or due dates that fall on the last day of the month.
	 *
	 * @param	long	$dateValue			Excel date serial value or a standard date string
	 * @param	int		$adjustmentMonths	Number of months to adjust by
	 * @return	long	Excel date serial value
	 */
	public static function EOMONTH($dateValue = 1, $adjustmentMonths = 0) {
		$dateValue			= self::flattenSingleValue($dateValue);
		$adjustmentMonths	= floor(self::flattenSingleValue($adjustmentMonths));

		if (!is_numeric($adjustmentMonths)) {
			return self::$_errorCodes['value'];
		}

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = self::_adjustDateByMonths($dateValue,$adjustmentMonths+1);
		$adjustDays = (int) $PHPDateObject->format('d');
		$adjustDaysString = '-'.$adjustDays.' days';
		$PHPDateObject->modify($adjustDaysString);

		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) PHPExcel_Shared_Date::PHPToExcel($PHPDateObject);
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP(PHPExcel_Shared_Date::PHPToExcel($PHPDateObject));
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return $PHPDateObject;
												  break;
		}
	}	//	function EOMONTH()


	/**
	 *	TRUNC
	 *
	 *	Truncates value to the number of fractional digits by number_digits.
	 *
	 *	@param	float		$value
	 *	@param	int			$number_digits
	 *	@return	float		Truncated value
	 */
	public static function TRUNC($value = 0, $number_digits = 0) {
		$value			= self::flattenSingleValue($value);
		$number_digits	= self::flattenSingleValue($number_digits);

		// Validate parameters
		if ($number_digits < 0) {
			return self::$_errorCodes['value'];
		}

		// Truncate
		if ($number_digits > 0) {
			$value = $value * pow(10, $number_digits);
		}
		$value = intval($value);
		if ($number_digits > 0) {
			$value = $value / pow(10, $number_digits);
		}

		// Return
		return $value;
	}	//	function TRUNC()

	/**
	 *	POWER
	 *
	 *	Computes x raised to the power y.
	 *
	 *	@param	float		$x
	 *	@param	float		$y
	 *	@return	float
	 */
	public static function POWER($x = 0, $y = 2) {
		$x	= self::flattenSingleValue($x);
		$y	= self::flattenSingleValue($y);

		// Validate parameters
		if ($x == 0 && $y <= 0) {
			return self::$_errorCodes['divisionbyzero'];
		}

		// Return
		return pow($x, $y);
	}	//	function POWER()


	private static function _nbrConversionFormat($xVal,$places) {
		if (!is_null($places)) {
			if (strlen($xVal) <= $places) {
				return substr(str_pad($xVal,$places,'0',STR_PAD_LEFT),-10);
			} else {
				return self::$_errorCodes['num'];
			}
		}

		return substr($xVal,-10);
	}	//	function _nbrConversionFormat()


	/**
	 * BINTODEC
	 *
	 * Return a binary value as Decimal.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function BINTODEC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
			$x = floor($x);
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[01]/',$x,$out)) {
			return self::$_errorCodes['num'];
		}
		if (strlen($x) > 10) {
			return self::$_errorCodes['num'];
		} elseif (strlen($x) == 10) {
			//	Two's Complement
			$x = substr($x,-9);
			return '-'.(512-bindec($x));
		}
		return bindec($x);
	}	//	function BINTODEC()


	/**
	 * BINTOHEX
	 *
	 * Return a binary value as Hex.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function BINTOHEX($x, $places=null) {
		$x	= floor(self::flattenSingleValue($x));
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
			$x = floor($x);
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[01]/',$x,$out)) {
			return self::$_errorCodes['num'];
		}
		if (strlen($x) > 10) {
			return self::$_errorCodes['num'];
		} elseif (strlen($x) == 10) {
			//	Two's Complement
			return str_repeat('F',8).substr(strtoupper(dechex(bindec(substr($x,-9)))),-2);
		}
		$hexVal = (string) strtoupper(dechex(bindec($x)));

		return self::_nbrConversionFormat($hexVal,$places);
	}	//	function BINTOHEX()


	/**
	 * BINTOOCT
	 *
	 * Return a binary value as Octal.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function BINTOOCT($x, $places=null) {
		$x	= floor(self::flattenSingleValue($x));
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
			$x = floor($x);
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[01]/',$x,$out)) {
			return self::$_errorCodes['num'];
		}
		if (strlen($x) > 10) {
			return self::$_errorCodes['num'];
		} elseif (strlen($x) == 10) {
			//	Two's Complement
			return str_repeat('7',7).substr(strtoupper(decoct(bindec(substr($x,-9)))),-3);
		}
		$octVal = (string) decoct(bindec($x));

		return self::_nbrConversionFormat($octVal,$places);
	}	//	function BINTOOCT()


	/**
	 * DECTOBIN
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function DECTOBIN($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[-0123456789.]/',$x,$out)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) floor($x);
		$r = decbin($x);
		if (strlen($r) == 32) {
			//	Two's Complement
			$r = substr($r,-10);
		} elseif (strlen($r) > 11) {
			return self::$_errorCodes['num'];
		}

		return self::_nbrConversionFormat($r,$places);
	}	//	function DECTOBIN()


	/**
	 * DECTOOCT
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function DECTOOCT($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[-0123456789.]/',$x,$out)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) floor($x);
		$r = decoct($x);
		if (strlen($r) == 11) {
			//	Two's Complement
			$r = substr($r,-10);
		}

		return self::_nbrConversionFormat($r,$places);
	}	//	function DECTOOCT()


	/**
	 * DECTOHEX
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function DECTOHEX($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[-0123456789.]/',$x,$out)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) floor($x);
		$r = strtoupper(dechex($x));
		if (strlen($r) == 8) {
			//	Two's Complement
			$r = 'FF'.$r;
		}

		return self::_nbrConversionFormat($r,$places);
	}	//	function DECTOHEX()


	/**
	 * HEXTOBIN
	 *
	 * Return a hex value as binary.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function HEXTOBIN($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/',strtoupper($x),$out)) {
			return self::$_errorCodes['num'];
		}
		$binVal = decbin(hexdec($x));

		return substr(self::_nbrConversionFormat($binVal,$places),-10);
	}	//	function HEXTOBIN()


	/**
	 * HEXTOOCT
	 *
	 * Return a hex value as octal.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function HEXTOOCT($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/',strtoupper($x),$out)) {
			return self::$_errorCodes['num'];
		}
		$octVal = decoct(hexdec($x));

		return self::_nbrConversionFormat($octVal,$places);
	}	//	function HEXTOOCT()


	/**
	 * HEXTODEC
	 *
	 * Return a hex value as octal.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function HEXTODEC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/',strtoupper($x),$out)) {
			return self::$_errorCodes['num'];
		}
		return hexdec($x);
	}	//	function HEXTODEC()


	/**
	 * OCTTOBIN
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function OCTTOBIN($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (preg_match_all('/[01234567]/',$x,$out) != strlen($x)) {
			return self::$_errorCodes['num'];
		}
		$binVal = decbin(octdec($x));

		return self::_nbrConversionFormat($binVal,$places);
	}	//	function OCTTOBIN()


	/**
	 * OCTTODEC
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function OCTTODEC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (preg_match_all('/[01234567]/',$x,$out) != strlen($x)) {
			return self::$_errorCodes['num'];
		}
		return octdec($x);
	}	//	function OCTTODEC()


	/**
	 * OCTTOHEX
	 *
	 * Return an octal value as hex.
	 *
	 * @param	string		$x
	 * @return	string
	 */
	public static function OCTTOHEX($x, $places=null) {
		$x	= self::flattenSingleValue($x);
		$places	= self::flattenSingleValue($places);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (preg_match_all('/[01234567]/',$x,$out) != strlen($x)) {
			return self::$_errorCodes['num'];
		}
		$hexVal = strtoupper(dechex(octdec($x)));

		return self::_nbrConversionFormat($hexVal,$places);
	}	//	function OCTTOHEX()


	public static function _parseComplex($complexNumber) {
		$workString = (string) $complexNumber;

		$realNumber = $imaginary = 0;
		//	Extract the suffix, if there is one
		$suffix = substr($workString,-1);
		if (!is_numeric($suffix)) {
			$workString = substr($workString,0,-1);
		} else {
			$suffix = '';
		}

		//	Split the input into its Real and Imaginary components
		$leadingSign = 0;
		if (strlen($workString) > 0) {
			$leadingSign = (($workString{0} == '+') || ($workString{0} == '-')) ? 1 : 0;
		}
		$power = '';
		$realNumber = strtok($workString, '+-');
		if (strtoupper(substr($realNumber,-1)) == 'E') {
			$power = strtok('+-');
			++$leadingSign;
		}

		$realNumber = substr($workString,0,strlen($realNumber)+strlen($power)+$leadingSign);

		if ($suffix != '') {
			$imaginary = substr($workString,strlen($realNumber));

			if (($imaginary == '') && (($realNumber == '') || ($realNumber == '+') || ($realNumber == '-'))) {
				$imaginary = $realNumber.'1';
				$realNumber = '0';
			} else if ($imaginary == '') {
				$imaginary = $realNumber;
				$realNumber = '0';
			} elseif (($imaginary == '+') || ($imaginary == '-')) {
				$imaginary .= '1';
			}
		}

		$complexArray = array( 'real'		=> $realNumber,
							   'imaginary'	=> $imaginary,
							   'suffix'		=> $suffix
							 );

		return $complexArray;
	}	//	function _parseComplex()


	private static function _cleanComplex($complexNumber) {
		if ($complexNumber{0} == '+') $complexNumber = substr($complexNumber,1);
		if ($complexNumber{0} == '0') $complexNumber = substr($complexNumber,1);
		if ($complexNumber{0} == '.') $complexNumber = '0'.$complexNumber;
		if ($complexNumber{0} == '+') $complexNumber = substr($complexNumber,1);
		return $complexNumber;
	}


	/**
	 * COMPLEX
	 *
	 * returns a complex number of the form x + yi or x + yj.
	 *
	 * @param	float		$realNumber
	 * @param	float		$imaginary
	 * @param	string		$suffix
	 * @return	string
	 */
	public static function COMPLEX($realNumber=0.0, $imaginary=0.0, $suffix='i') {
		$realNumber	= self::flattenSingleValue($realNumber);
		$imaginary	= self::flattenSingleValue($imaginary);
		$suffix		= self::flattenSingleValue($suffix);

		if (((is_numeric($realNumber)) && (is_numeric($imaginary))) &&
			(($suffix == 'i') || ($suffix == 'j') || ($suffix == ''))) {
			if ($realNumber == 0.0) {
				if ($imaginary == 0.0) {
					return (string) '0';
				} elseif ($imaginary == 1.0) {
					return (string) $suffix;
				} elseif ($imaginary == -1.0) {
					return (string) '-'.$suffix;
				}
				return (string) $imaginary.$suffix;
			} elseif ($imaginary == 0.0) {
				return (string) $realNumber;
			} elseif ($imaginary == 1.0) {
				return (string) $realNumber.'+'.$suffix;
			} elseif ($imaginary == -1.0) {
				return (string) $realNumber.'-'.$suffix;
			}
			if ($imaginary > 0) { $imaginary = (string) '+'.$imaginary; }
			return (string) $realNumber.$imaginary.$suffix;
		}
		return self::$_errorCodes['value'];
	}	//	function COMPLEX()


	/**
	 * IMAGINARY
	 *
	 * Returns the imaginary coefficient of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	real
	 */
	public static function IMAGINARY($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}
		return $parsedComplex['imaginary'];
	}	//	function IMAGINARY()


	/**
	 * IMREAL
	 *
	 * Returns the real coefficient of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	real
	 */
	public static function IMREAL($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}
		return $parsedComplex['real'];
	}	//	function IMREAL()


	/**
	 * IMABS
	 *
	 * Returns the absolute value (modulus) of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	real
	 */
	public static function IMABS($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}
		return sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary']));
	}	//	function IMABS()


	/**
	 * IMARGUMENT
	 *
	 * Returns the argument theta of a complex number, i.e. the angle in radians from the real axis to the representation of the number in polar coordinates.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMARGUMENT($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['real'] == 0.0) {
			if ($parsedComplex['imaginary'] == 0.0) {
				return 0.0;
			} elseif($parsedComplex['imaginary'] < 0.0) {
				return pi() / -2;
			} else {
				return pi() / 2;
			}
		} elseif ($parsedComplex['real'] > 0.0) {
			return atan($parsedComplex['imaginary'] / $parsedComplex['real']);
		} elseif ($parsedComplex['imaginary'] < 0.0) {
			return 0 - (pi() - atan(abs($parsedComplex['imaginary']) / abs($parsedComplex['real'])));
		} else {
			return pi() - atan($parsedComplex['imaginary'] / abs($parsedComplex['real']));
		}
	}	//	function IMARGUMENT()


	/**
	 * IMCONJUGATE
	 *
	 * Returns the complex conjugate of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMCONJUGATE($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);

		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['imaginary'] == 0.0) {
			return $parsedComplex['real'];
		} else {
			return self::_cleanComplex(self::COMPLEX($parsedComplex['real'], 0 - $parsedComplex['imaginary'], $parsedComplex['suffix']));
		}
	}	//	function IMCONJUGATE()


	/**
	 * IMCOS
	 *
	 * Returns the cosine of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMCOS($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['imaginary'] == 0.0) {
			return cos($parsedComplex['real']);
		} else {
			return self::IMCONJUGATE(self::COMPLEX(cos($parsedComplex['real']) * cosh($parsedComplex['imaginary']),sin($parsedComplex['real']) * sinh($parsedComplex['imaginary']),$parsedComplex['suffix']));
		}
	}	//	function IMCOS()


	/**
	 * IMSIN
	 *
	 * Returns the sine of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMSIN($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['imaginary'] == 0.0) {
			return sin($parsedComplex['real']);
		} else {
			return self::COMPLEX(sin($parsedComplex['real']) * cosh($parsedComplex['imaginary']),cos($parsedComplex['real']) * sinh($parsedComplex['imaginary']),$parsedComplex['suffix']);
		}
	}	//	function IMSIN()


	/**
	 * IMSQRT
	 *
	 * Returns the square root of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMSQRT($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		$theta = self::IMARGUMENT($complexNumber);
		$d1 = cos($theta / 2);
		$d2 = sin($theta / 2);
		$r = sqrt(sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary'])));

		if ($parsedComplex['suffix'] == '') {
			return self::COMPLEX($d1 * $r,$d2 * $r);
		} else {
			return self::COMPLEX($d1 * $r,$d2 * $r,$parsedComplex['suffix']);
		}
	}	//	function IMSQRT()


	/**
	 * IMLN
	 *
	 * Returns the natural logarithm of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMLN($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return self::$_errorCodes['num'];
		}

		$logR = log(sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary'])));
		$t = self::IMARGUMENT($complexNumber);

		if ($parsedComplex['suffix'] == '') {
			return self::COMPLEX($logR,$t);
		} else {
			return self::COMPLEX($logR,$t,$parsedComplex['suffix']);
		}
	}	//	function IMLN()


	/**
	 * IMLOG10
	 *
	 * Returns the common logarithm (base 10) of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMLOG10($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return self::$_errorCodes['num'];
		} elseif (($parsedComplex['real'] > 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return log10($parsedComplex['real']);
		}

		return self::IMPRODUCT(log10(EULER),self::IMLN($complexNumber));
	}	//	function IMLOG10()


	/**
	 * IMLOG2
	 *
	 * Returns the common logarithm (base 10) of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMLOG2($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return self::$_errorCodes['num'];
		} elseif (($parsedComplex['real'] > 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return log($parsedComplex['real'],2);
		}

		return self::IMPRODUCT(log(EULER,2),self::IMLN($complexNumber));
	}	//	function IMLOG2()


	/**
	 * IMEXP
	 *
	 * Returns the exponential of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMEXP($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return '1';
		}

		$e = exp($parsedComplex['real']);
		$eX = $e * cos($parsedComplex['imaginary']);
		$eY = $e * sin($parsedComplex['imaginary']);

		if ($parsedComplex['suffix'] == '') {
			return self::COMPLEX($eX,$eY);
		} else {
			return self::COMPLEX($eX,$eY,$parsedComplex['suffix']);
		}
	}	//	function IMEXP()


	/**
	 * IMPOWER
	 *
	 * Returns a complex number in x + yi or x + yj text format raised to a power.
	 *
	 * @param	string		$complexNumber
	 * @return	string
	 */
	public static function IMPOWER($complexNumber,$realNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);
		$realNumber		= self::flattenSingleValue($realNumber);

		if (!is_numeric($realNumber)) {
			return self::$_errorCodes['value'];
		}

		$parsedComplex = self::_parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		$r = sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary']));
		$rPower = pow($r,$realNumber);
		$theta = self::IMARGUMENT($complexNumber) * $realNumber;
		if ($theta == 0) {
			return 1;
		} elseif ($parsedComplex['imaginary'] == 0.0) {
			return self::COMPLEX($rPower * cos($theta),$rPower * sin($theta),$parsedComplex['suffix']);
		} else {
			return self::COMPLEX($rPower * cos($theta),$rPower * sin($theta),$parsedComplex['suffix']);
		}
	}	//	function IMPOWER()


	/**
	 * IMDIV
	 *
	 * Returns the quotient of two complex numbers in x + yi or x + yj text format.
	 *
	 * @param	string		$complexDividend
	 * @param	string		$complexDivisor
	 * @return	real
	 */
	public static function IMDIV($complexDividend,$complexDivisor) {
		$complexDividend	= self::flattenSingleValue($complexDividend);
		$complexDivisor	= self::flattenSingleValue($complexDivisor);

		$parsedComplexDividend = self::_parseComplex($complexDividend);
		if (!is_array($parsedComplexDividend)) {
			return $parsedComplexDividend;
		}

		$parsedComplexDivisor = self::_parseComplex($complexDivisor);
		if (!is_array($parsedComplexDivisor)) {
			return $parsedComplexDividend;
		}

		if (($parsedComplexDividend['suffix'] != '') && ($parsedComplexDivisor['suffix'] != '') &&
			($parsedComplexDividend['suffix'] != $parsedComplexDivisor['suffix'])) {
			return self::$_errorCodes['num'];
		}
		if (($parsedComplexDividend['suffix'] != '') && ($parsedComplexDivisor['suffix'] == '')) {
			$parsedComplexDivisor['suffix'] = $parsedComplexDividend['suffix'];
		}

		$d1 = ($parsedComplexDividend['real'] * $parsedComplexDivisor['real']) + ($parsedComplexDividend['imaginary'] * $parsedComplexDivisor['imaginary']);
		$d2 = ($parsedComplexDividend['imaginary'] * $parsedComplexDivisor['real']) - ($parsedComplexDividend['real'] * $parsedComplexDivisor['imaginary']);
		$d3 = ($parsedComplexDivisor['real'] * $parsedComplexDivisor['real']) + ($parsedComplexDivisor['imaginary'] * $parsedComplexDivisor['imaginary']);

		$r = $d1/$d3;
		$i = $d2/$d3;

		if ($i > 0.0) {
			return self::_cleanComplex($r.'+'.$i.$parsedComplexDivisor['suffix']);
		} elseif ($i < 0.0) {
			return self::_cleanComplex($r.$i.$parsedComplexDivisor['suffix']);
		} else {
			return $r;
		}
	}	//	function IMDIV()


	/**
	 * IMSUB
	 *
	 * Returns the difference of two complex numbers in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber1
	 * @param	string		$complexNumber2
	 * @return	real
	 */
	public static function IMSUB($complexNumber1,$complexNumber2) {
		$complexNumber1	= self::flattenSingleValue($complexNumber1);
		$complexNumber2	= self::flattenSingleValue($complexNumber2);

		$parsedComplex1 = self::_parseComplex($complexNumber1);
		if (!is_array($parsedComplex1)) {
			return $parsedComplex1;
		}

		$parsedComplex2 = self::_parseComplex($complexNumber2);
		if (!is_array($parsedComplex2)) {
			return $parsedComplex2;
		}

		if ((($parsedComplex1['suffix'] != '') && ($parsedComplex2['suffix'] != '')) &&
			($parsedComplex1['suffix'] != $parsedComplex2['suffix'])) {
			return self::$_errorCodes['num'];
		} elseif (($parsedComplex1['suffix'] == '') && ($parsedComplex2['suffix'] != '')) {
			$parsedComplex1['suffix'] = $parsedComplex2['suffix'];
		}

		$d1 = $parsedComplex1['real'] - $parsedComplex2['real'];
		$d2 = $parsedComplex1['imaginary'] - $parsedComplex2['imaginary'];

		return self::COMPLEX($d1,$d2,$parsedComplex1['suffix']);
	}	//	function IMSUB()


	/**
	 * IMSUM
	 *
	 * Returns the sum of two or more complex numbers in x + yi or x + yj text format.
	 *
	 * @param	array of mixed		Data Series
	 * @return	real
	 */
	public static function IMSUM() {
		// Return value
		$returnValue = self::_parseComplex('0');
		$activeSuffix = '';

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			$parsedComplex = self::_parseComplex($arg);
			if (!is_array($parsedComplex)) {
				return $parsedComplex;
			}

			if ($activeSuffix == '') {
				$activeSuffix = $parsedComplex['suffix'];
			} elseif (($parsedComplex['suffix'] != '') && ($activeSuffix != $parsedComplex['suffix'])) {
				return self::$_errorCodes['value'];
			}

			$returnValue['real'] += $parsedComplex['real'];
			$returnValue['imaginary'] += $parsedComplex['imaginary'];
		}

		if ($returnValue['imaginary'] == 0.0) { $activeSuffix = ''; }
		return self::COMPLEX($returnValue['real'],$returnValue['imaginary'],$activeSuffix);
	}	//	function IMSUM()


	/**
	 * IMPRODUCT
	 *
	 * Returns the product of two or more complex numbers in x + yi or x + yj text format.
	 *
	 * @param	array of mixed		Data Series
	 * @return	real
	 */
	public static function IMPRODUCT() {
		// Return value
		$returnValue = self::_parseComplex('1');
		$activeSuffix = '';

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			$parsedComplex = self::_parseComplex($arg);
			if (!is_array($parsedComplex)) {
				return $parsedComplex;
			}
			$workValue = $returnValue;
			if (($parsedComplex['suffix'] != '') && ($activeSuffix == '')) {
				$activeSuffix = $parsedComplex['suffix'];
			} elseif (($parsedComplex['suffix'] != '') && ($activeSuffix != $parsedComplex['suffix'])) {
				return self::$_errorCodes['num'];
			}
			$returnValue['real'] = ($workValue['real'] * $parsedComplex['real']) - ($workValue['imaginary'] * $parsedComplex['imaginary']);
			$returnValue['imaginary'] = ($workValue['real'] * $parsedComplex['imaginary']) + ($workValue['imaginary'] * $parsedComplex['real']);
		}

		if ($returnValue['imaginary'] == 0.0) { $activeSuffix = ''; }
		return self::COMPLEX($returnValue['real'],$returnValue['imaginary'],$activeSuffix);
	}	//	function IMPRODUCT()


	private static $_conversionUnits = array( 'g'		=> array(	'Group'	=> 'Mass',			'Unit Name'	=> 'Gram',						'AllowPrefix'	=> True		),
											  'sg'		=> array(	'Group'	=> 'Mass',			'Unit Name'	=> 'Slug',						'AllowPrefix'	=> False	),
											  'lbm'		=> array(	'Group'	=> 'Mass',			'Unit Name'	=> 'Pound mass (avoirdupois)',	'AllowPrefix'	=> False	),
											  'u'		=> array(	'Group'	=> 'Mass',			'Unit Name'	=> 'U (atomic mass unit)',		'AllowPrefix'	=> True		),
											  'ozm'		=> array(	'Group'	=> 'Mass',			'Unit Name'	=> 'Ounce mass (avoirdupois)',	'AllowPrefix'	=> False	),
											  'm'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Meter',						'AllowPrefix'	=> True		),
											  'mi'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Statute mile',				'AllowPrefix'	=> False	),
											  'Nmi'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Nautical mile',				'AllowPrefix'	=> False	),
											  'in'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Inch',						'AllowPrefix'	=> False	),
											  'ft'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Foot',						'AllowPrefix'	=> False	),
											  'yd'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Yard',						'AllowPrefix'	=> False	),
											  'ang'		=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Angstrom',					'AllowPrefix'	=> True		),
											  'Pica'	=> array(	'Group'	=> 'Distance',		'Unit Name'	=> 'Pica (1/72 in)',			'AllowPrefix'	=> False	),
											  'yr'		=> array(	'Group'	=> 'Time',			'Unit Name'	=> 'Year',						'AllowPrefix'	=> False	),
											  'day'		=> array(	'Group'	=> 'Time',			'Unit Name'	=> 'Day',						'AllowPrefix'	=> False	),
											  'hr'		=> array(	'Group'	=> 'Time',			'Unit Name'	=> 'Hour',						'AllowPrefix'	=> False	),
											  'mn'		=> array(	'Group'	=> 'Time',			'Unit Name'	=> 'Minute',					'AllowPrefix'	=> False	),
											  'sec'		=> array(	'Group'	=> 'Time',			'Unit Name'	=> 'Second',					'AllowPrefix'	=> True		),
											  'Pa'		=> array(	'Group'	=> 'Pressure',		'Unit Name'	=> 'Pascal',					'AllowPrefix'	=> True		),
											  'p'		=> array(	'Group'	=> 'Pressure',		'Unit Name'	=> 'Pascal',					'AllowPrefix'	=> True		),
											  'atm'		=> array(	'Group'	=> 'Pressure',		'Unit Name'	=> 'Atmosphere',				'AllowPrefix'	=> True		),
											  'at'		=> array(	'Group'	=> 'Pressure',		'Unit Name'	=> 'Atmosphere',				'AllowPrefix'	=> True		),
											  'mmHg'	=> array(	'Group'	=> 'Pressure',		'Unit Name'	=> 'mm of Mercury',				'AllowPrefix'	=> True		),
											  'N'		=> array(	'Group'	=> 'Force',			'Unit Name'	=> 'Newton',					'AllowPrefix'	=> True		),
											  'dyn'		=> array(	'Group'	=> 'Force',			'Unit Name'	=> 'Dyne',						'AllowPrefix'	=> True		),
											  'dy'		=> array(	'Group'	=> 'Force',			'Unit Name'	=> 'Dyne',						'AllowPrefix'	=> True		),
											  'lbf'		=> array(	'Group'	=> 'Force',			'Unit Name'	=> 'Pound force',				'AllowPrefix'	=> False	),
											  'J'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Joule',						'AllowPrefix'	=> True		),
											  'e'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Erg',						'AllowPrefix'	=> True		),
											  'c'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Thermodynamic calorie',		'AllowPrefix'	=> True		),
											  'cal'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'IT calorie',				'AllowPrefix'	=> True		),
											  'eV'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Electron volt',				'AllowPrefix'	=> True		),
											  'ev'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Electron volt',				'AllowPrefix'	=> True		),
											  'HPh'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Horsepower-hour',			'AllowPrefix'	=> False	),
											  'hh'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Horsepower-hour',			'AllowPrefix'	=> False	),
											  'Wh'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Watt-hour',					'AllowPrefix'	=> True		),
											  'wh'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Watt-hour',					'AllowPrefix'	=> True		),
											  'flb'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'Foot-pound',				'AllowPrefix'	=> False	),
											  'BTU'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'BTU',						'AllowPrefix'	=> False	),
											  'btu'		=> array(	'Group'	=> 'Energy',		'Unit Name'	=> 'BTU',						'AllowPrefix'	=> False	),
											  'HP'		=> array(	'Group'	=> 'Power',			'Unit Name'	=> 'Horsepower',				'AllowPrefix'	=> False	),
											  'h'		=> array(	'Group'	=> 'Power',			'Unit Name'	=> 'Horsepower',				'AllowPrefix'	=> False	),
											  'W'		=> array(	'Group'	=> 'Power',			'Unit Name'	=> 'Watt',						'AllowPrefix'	=> True		),
											  'w'		=> array(	'Group'	=> 'Power',			'Unit Name'	=> 'Watt',						'AllowPrefix'	=> True		),
											  'T'		=> array(	'Group'	=> 'Magnetism',		'Unit Name'	=> 'Tesla',						'AllowPrefix'	=> True		),
											  'ga'		=> array(	'Group'	=> 'Magnetism',		'Unit Name'	=> 'Gauss',						'AllowPrefix'	=> True		),
											  'C'		=> array(	'Group'	=> 'Temperature',	'Unit Name'	=> 'Celsius',					'AllowPrefix'	=> False	),
											  'cel'		=> array(	'Group'	=> 'Temperature',	'Unit Name'	=> 'Celsius',					'AllowPrefix'	=> False	),
											  'F'		=> array(	'Group'	=> 'Temperature',	'Unit Name'	=> 'Fahrenheit',				'AllowPrefix'	=> False	),
											  'fah'		=> array(	'Group'	=> 'Temperature',	'Unit Name'	=> 'Fahrenheit',				'AllowPrefix'	=> False	),
											  'K'		=> array(	'Group'	=> 'Temperature',	'Unit Name'	=> 'Kelvin',					'AllowPrefix'	=> False	),
											  'kel'		=> array(	'Group'	=> 'Temperature',	'Unit Name'	=> 'Kelvin',					'AllowPrefix'	=> False	),
											  'tsp'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Teaspoon',					'AllowPrefix'	=> False	),
											  'tbs'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Tablespoon',				'AllowPrefix'	=> False	),
											  'oz'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Fluid Ounce',				'AllowPrefix'	=> False	),
											  'cup'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Cup',						'AllowPrefix'	=> False	),
											  'pt'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'U.S. Pint',					'AllowPrefix'	=> False	),
											  'us_pt'	=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'U.S. Pint',					'AllowPrefix'	=> False	),
											  'uk_pt'	=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'U.K. Pint',					'AllowPrefix'	=> False	),
											  'qt'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Quart',						'AllowPrefix'	=> False	),
											  'gal'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Gallon',					'AllowPrefix'	=> False	),
											  'l'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Litre',						'AllowPrefix'	=> True		),
											  'lt'		=> array(	'Group'	=> 'Liquid',		'Unit Name'	=> 'Litre',						'AllowPrefix'	=> True		)
											);

	private static $_conversionMultipliers = array(	'Y'	=> array(	'multiplier'	=> 1E24,	'name'	=> 'yotta'	),
													'Z'	=> array(	'multiplier'	=> 1E21,	'name'	=> 'zetta'	),
													'E'	=> array(	'multiplier'	=> 1E18,	'name'	=> 'exa'	),
													'P'	=> array(	'multiplier'	=> 1E15,	'name'	=> 'peta'	),
													'T'	=> array(	'multiplier'	=> 1E12,	'name'	=> 'tera'	),
													'G'	=> array(	'multiplier'	=> 1E9,		'name'	=> 'giga'	),
													'M'	=> array(	'multiplier'	=> 1E6,		'name'	=> 'mega'	),
													'k'	=> array(	'multiplier'	=> 1E3,		'name'	=> 'kilo'	),
													'h'	=> array(	'multiplier'	=> 1E2,		'name'	=> 'hecto'	),
													'e'	=> array(	'multiplier'	=> 1E1,		'name'	=> 'deka'	),
													'd'	=> array(	'multiplier'	=> 1E-1,	'name'	=> 'deci'	),
													'c'	=> array(	'multiplier'	=> 1E-2,	'name'	=> 'centi'	),
													'm'	=> array(	'multiplier'	=> 1E-3,	'name'	=> 'milli'	),
													'u'	=> array(	'multiplier'	=> 1E-6,	'name'	=> 'micro'	),
													'n'	=> array(	'multiplier'	=> 1E-9,	'name'	=> 'nano'	),
													'p'	=> array(	'multiplier'	=> 1E-12,	'name'	=> 'pico'	),
													'f'	=> array(	'multiplier'	=> 1E-15,	'name'	=> 'femto'	),
													'a'	=> array(	'multiplier'	=> 1E-18,	'name'	=> 'atto'	),
													'z'	=> array(	'multiplier'	=> 1E-21,	'name'	=> 'zepto'	),
													'y'	=> array(	'multiplier'	=> 1E-24,	'name'	=> 'yocto'	)
												 );

	private static $_unitConversions = array(	'Mass'		=> array(	'g'		=> array(	'g'		=> 1.0,
																							'sg'	=> 6.85220500053478E-05,
																							'lbm'	=> 2.20462291469134E-03,
																							'u'		=> 6.02217000000000E+23,
																							'ozm'	=> 3.52739718003627E-02
																						),
																		'sg'	=> array(	'g'		=> 1.45938424189287E+04,
																							'sg'	=> 1.0,
																							'lbm'	=> 3.21739194101647E+01,
																							'u'		=> 8.78866000000000E+27,
																							'ozm'	=> 5.14782785944229E+02
																						),
																		'lbm'	=> array(	'g'		=> 4.5359230974881148E+02,
																							'sg'	=> 3.10810749306493E-02,
																							'lbm'	=> 1.0,
																							'u'		=> 2.73161000000000E+26,
																							'ozm'	=> 1.60000023429410E+01
																						),
																		'u'		=> array(	'g'		=> 1.66053100460465E-24,
																							'sg'	=> 1.13782988532950E-28,
																							'lbm'	=> 3.66084470330684E-27,
																							'u'		=> 1.0,
																							'ozm'	=> 5.85735238300524E-26
																						),
																		'ozm'	=> array(	'g'		=> 2.83495152079732E+01,
																							'sg'	=> 1.94256689870811E-03,
																							'lbm'	=> 6.24999908478882E-02,
																							'u'		=> 1.70725600000000E+25,
																							'ozm'	=> 1.0
																						)
																	),
												'Distance'	=> array(	'm'		=> array(	'm'		=> 1.0,
																							'mi'	=> 6.21371192237334E-04,
																							'Nmi'	=> 5.39956803455724E-04,
																							'in'	=> 3.93700787401575E+01,
																							'ft'	=> 3.28083989501312E+00,
																							'yd'	=> 1.09361329797891E+00,
																							'ang'	=> 1.00000000000000E+10,
																							'Pica'	=> 2.83464566929116E+03
																						),
																		'mi'	=> array(	'm'		=> 1.60934400000000E+03,
																							'mi'	=> 1.0,
																							'Nmi'	=> 8.68976241900648E-01,
																							'in'	=> 6.33600000000000E+04,
																							'ft'	=> 5.28000000000000E+03,
																							'yd'	=> 1.76000000000000E+03,
																							'ang'	=> 1.60934400000000E+13,
																							'Pica'	=> 4.56191999999971E+06
																						),
																		'Nmi'	=> array(	'm'		=> 1.85200000000000E+03,
																							'mi'	=> 1.15077944802354E+00,
																							'Nmi'	=> 1.0,
																							'in'	=> 7.29133858267717E+04,
																							'ft'	=> 6.07611548556430E+03,
																							'yd'	=> 2.02537182785694E+03,
																							'ang'	=> 1.85200000000000E+13,
																							'Pica'	=> 5.24976377952723E+06
																						),
																		'in'	=> array(	'm'		=> 2.54000000000000E-02,
																							'mi'	=> 1.57828282828283E-05,
																							'Nmi'	=> 1.37149028077754E-05,
																							'in'	=> 1.0,
																							'ft'	=> 8.33333333333333E-02,
																							'yd'	=> 2.77777777686643E-02,
																							'ang'	=> 2.54000000000000E+08,
																							'Pica'	=> 7.19999999999955E+01
																						),
																		'ft'	=> array(	'm'		=> 3.04800000000000E-01,
																							'mi'	=> 1.89393939393939E-04,
																							'Nmi'	=> 1.64578833693305E-04,
																							'in'	=> 1.20000000000000E+01,
																							'ft'	=> 1.0,
																							'yd'	=> 3.33333333223972E-01,
																							'ang'	=> 3.04800000000000E+09,
																							'Pica'	=> 8.63999999999946E+02
																						),
																		'yd'	=> array(	'm'		=> 9.14400000300000E-01,
																							'mi'	=> 5.68181818368230E-04,
																							'Nmi'	=> 4.93736501241901E-04,
																							'in'	=> 3.60000000118110E+01,
																							'ft'	=> 3.00000000000000E+00,
																							'yd'	=> 1.0,
																							'ang'	=> 9.14400000300000E+09,
																							'Pica'	=> 2.59200000085023E+03
																						),
																		'ang'	=> array(	'm'		=> 1.00000000000000E-10,
																							'mi'	=> 6.21371192237334E-14,
																							'Nmi'	=> 5.39956803455724E-14,
																							'in'	=> 3.93700787401575E-09,
																							'ft'	=> 3.28083989501312E-10,
																							'yd'	=> 1.09361329797891E-10,
																							'ang'	=> 1.0,
																							'Pica'	=> 2.83464566929116E-07
																						),
																		'Pica'	=> array(	'm'		=> 3.52777777777800E-04,
																							'mi'	=> 2.19205948372629E-07,
																							'Nmi'	=> 1.90484761219114E-07,
																							'in'	=> 1.38888888888898E-02,
																							'ft'	=> 1.15740740740748E-03,
																							'yd'	=> 3.85802469009251E-04,
																							'ang'	=> 3.52777777777800E+06,
																							'Pica'	=> 1.0
																						)
																	),
												'Time'		=> array(	'yr'	=> array(	'yr'		=> 1.0,
																							'day'		=> 365.25,
																							'hr'		=> 8766.0,
																							'mn'		=> 525960.0,
																							'sec'		=> 31557600.0
																						),
																		'day'	=> array(	'yr'		=> 2.73785078713210E-03,
																							'day'		=> 1.0,
																							'hr'		=> 24.0,
																							'mn'		=> 1440.0,
																							'sec'		=> 86400.0
																						),
																		'hr'	=> array(	'yr'		=> 1.14077116130504E-04,
																							'day'		=> 4.16666666666667E-02,
																							'hr'		=> 1.0,
																							'mn'		=> 60.0,
																							'sec'		=> 3600.0
																						),
																		'mn'	=> array(	'yr'		=> 1.90128526884174E-06,
																							'day'		=> 6.94444444444444E-04,
																							'hr'		=> 1.66666666666667E-02,
																							'mn'		=> 1.0,
																							'sec'		=> 60.0
																						),
																		'sec'	=> array(	'yr'		=> 3.16880878140289E-08,
																							'day'		=> 1.15740740740741E-05,
																							'hr'		=> 2.77777777777778E-04,
																							'mn'		=> 1.66666666666667E-02,
																							'sec'		=> 1.0
																						)
																	),
												'Pressure'	=> array(	'Pa'	=> array(	'Pa'		=> 1.0,
																							'p'			=> 1.0,
																							'atm'		=> 9.86923299998193E-06,
																							'at'		=> 9.86923299998193E-06,
																							'mmHg'		=> 7.50061707998627E-03
																						),
																		'p'		=> array(	'Pa'		=> 1.0,
																							'p'			=> 1.0,
																							'atm'		=> 9.86923299998193E-06,
																							'at'		=> 9.86923299998193E-06,
																							'mmHg'		=> 7.50061707998627E-03
																						),
																		'atm'	=> array(	'Pa'		=> 1.01324996583000E+05,
																							'p'			=> 1.01324996583000E+05,
																							'atm'		=> 1.0,
																							'at'		=> 1.0,
																							'mmHg'		=> 760.0
																						),
																		'at'	=> array(	'Pa'		=> 1.01324996583000E+05,
																							'p'			=> 1.01324996583000E+05,
																							'atm'		=> 1.0,
																							'at'		=> 1.0,
																							'mmHg'		=> 760.0
																						),
																		'mmHg'	=> array(	'Pa'		=> 1.33322363925000E+02,
																							'p'			=> 1.33322363925000E+02,
																							'atm'		=> 1.31578947368421E-03,
																							'at'		=> 1.31578947368421E-03,
																							'mmHg'		=> 1.0
																						)
																	),
												'Force'		=> array(	'N'		=> array(	'N'			=> 1.0,
																							'dyn'		=> 1.0E+5,
																							'dy'		=> 1.0E+5,
																							'lbf'		=> 2.24808923655339E-01
																						),
																		'dyn'	=> array(	'N'			=> 1.0E-5,
																							'dyn'		=> 1.0,
																							'dy'		=> 1.0,
																							'lbf'		=> 2.24808923655339E-06
																						),
																		'dy'	=> array(	'N'			=> 1.0E-5,
																							'dyn'		=> 1.0,
																							'dy'		=> 1.0,
																							'lbf'		=> 2.24808923655339E-06
																						),
																		'lbf'	=> array(	'N'			=> 4.448222,
																							'dyn'		=> 4.448222E+5,
																							'dy'		=> 4.448222E+5,
																							'lbf'		=> 1.0
																						)
																	),
												'Energy'	=> array(	'J'		=> array(	'J'			=> 1.0,
																							'e'			=> 9.99999519343231E+06,
																							'c'			=> 2.39006249473467E-01,
																							'cal'		=> 2.38846190642017E-01,
																							'eV'		=> 6.24145700000000E+18,
																							'ev'		=> 6.24145700000000E+18,
																							'HPh'		=> 3.72506430801000E-07,
																							'hh'		=> 3.72506430801000E-07,
																							'Wh'		=> 2.77777916238711E-04,
																							'wh'		=> 2.77777916238711E-04,
																							'flb'		=> 2.37304222192651E+01,
																							'BTU'		=> 9.47815067349015E-04,
																							'btu'		=> 9.47815067349015E-04
																						),
																		'e'		=> array(	'J'			=> 1.00000048065700E-07,
																							'e'			=> 1.0,
																							'c'			=> 2.39006364353494E-08,
																							'cal'		=> 2.38846305445111E-08,
																							'eV'		=> 6.24146000000000E+11,
																							'ev'		=> 6.24146000000000E+11,
																							'HPh'		=> 3.72506609848824E-14,
																							'hh'		=> 3.72506609848824E-14,
																							'Wh'		=> 2.77778049754611E-11,
																							'wh'		=> 2.77778049754611E-11,
																							'flb'		=> 2.37304336254586E-06,
																							'BTU'		=> 9.47815522922962E-11,
																							'btu'		=> 9.47815522922962E-11
																						),
																		'c'		=> array(	'J'			=> 4.18399101363672E+00,
																							'e'			=> 4.18398900257312E+07,
																							'c'			=> 1.0,
																							'cal'		=> 9.99330315287563E-01,
																							'eV'		=> 2.61142000000000E+19,
																							'ev'		=> 2.61142000000000E+19,
																							'HPh'		=> 1.55856355899327E-06,
																							'hh'		=> 1.55856355899327E-06,
																							'Wh'		=> 1.16222030532950E-03,
																							'wh'		=> 1.16222030532950E-03,
																							'flb'		=> 9.92878733152102E+01,
																							'BTU'		=> 3.96564972437776E-03,
																							'btu'		=> 3.96564972437776E-03
																						),
																		'cal'	=> array(	'J'			=> 4.18679484613929E+00,
																							'e'			=> 4.18679283372801E+07,
																							'c'			=> 1.00067013349059E+00,
																							'cal'		=> 1.0,
																							'eV'		=> 2.61317000000000E+19,
																							'ev'		=> 2.61317000000000E+19,
																							'HPh'		=> 1.55960800463137E-06,
																							'hh'		=> 1.55960800463137E-06,
																							'Wh'		=> 1.16299914807955E-03,
																							'wh'		=> 1.16299914807955E-03,
																							'flb'		=> 9.93544094443283E+01,
																							'BTU'		=> 3.96830723907002E-03,
																							'btu'		=> 3.96830723907002E-03
																						),
																		'eV'	=> array(	'J'			=> 1.60219000146921E-19,
																							'e'			=> 1.60218923136574E-12,
																							'c'			=> 3.82933423195043E-20,
																							'cal'		=> 3.82676978535648E-20,
																							'eV'		=> 1.0,
																							'ev'		=> 1.0,
																							'HPh'		=> 5.96826078912344E-26,
																							'hh'		=> 5.96826078912344E-26,
																							'Wh'		=> 4.45053000026614E-23,
																							'wh'		=> 4.45053000026614E-23,
																							'flb'		=> 3.80206452103492E-18,
																							'BTU'		=> 1.51857982414846E-22,
																							'btu'		=> 1.51857982414846E-22
																						),
																		'ev'	=> array(	'J'			=> 1.60219000146921E-19,
																							'e'			=> 1.60218923136574E-12,
																							'c'			=> 3.82933423195043E-20,
																							'cal'		=> 3.82676978535648E-20,
																							'eV'		=> 1.0,
																							'ev'		=> 1.0,
																							'HPh'		=> 5.96826078912344E-26,
																							'hh'		=> 5.96826078912344E-26,
																							'Wh'		=> 4.45053000026614E-23,
																							'wh'		=> 4.45053000026614E-23,
																							'flb'		=> 3.80206452103492E-18,
																							'BTU'		=> 1.51857982414846E-22,
																							'btu'		=> 1.51857982414846E-22
																						),
																		'HPh'	=> array(	'J'			=> 2.68451741316170E+06,
																							'e'			=> 2.68451612283024E+13,
																							'c'			=> 6.41616438565991E+05,
																							'cal'		=> 6.41186757845835E+05,
																							'eV'		=> 1.67553000000000E+25,
																							'ev'		=> 1.67553000000000E+25,
																							'HPh'		=> 1.0,
																							'hh'		=> 1.0,
																							'Wh'		=> 7.45699653134593E+02,
																							'wh'		=> 7.45699653134593E+02,
																							'flb'		=> 6.37047316692964E+07,
																							'BTU'		=> 2.54442605275546E+03,
																							'btu'		=> 2.54442605275546E+03
																						),
																		'hh'	=> array(	'J'			=> 2.68451741316170E+06,
																							'e'			=> 2.68451612283024E+13,
																							'c'			=> 6.41616438565991E+05,
																							'cal'		=> 6.41186757845835E+05,
																							'eV'		=> 1.67553000000000E+25,
																							'ev'		=> 1.67553000000000E+25,
																							'HPh'		=> 1.0,
																							'hh'		=> 1.0,
																							'Wh'		=> 7.45699653134593E+02,
																							'wh'		=> 7.45699653134593E+02,
																							'flb'		=> 6.37047316692964E+07,
																							'BTU'		=> 2.54442605275546E+03,
																							'btu'		=> 2.54442605275546E+03
																						),
																		'Wh'	=> array(	'J'			=> 3.59999820554720E+03,
																							'e'			=> 3.59999647518369E+10,
																							'c'			=> 8.60422069219046E+02,
																							'cal'		=> 8.59845857713046E+02,
																							'eV'		=> 2.24692340000000E+22,
																							'ev'		=> 2.24692340000000E+22,
																							'HPh'		=> 1.34102248243839E-03,
																							'hh'		=> 1.34102248243839E-03,
																							'Wh'		=> 1.0,
																							'wh'		=> 1.0,
																							'flb'		=> 8.54294774062316E+04,
																							'BTU'		=> 3.41213254164705E+00,
																							'btu'		=> 3.41213254164705E+00
																						),
																		'wh'	=> array(	'J'			=> 3.59999820554720E+03,
																							'e'			=> 3.59999647518369E+10,
																							'c'			=> 8.60422069219046E+02,
																							'cal'		=> 8.59845857713046E+02,
																							'eV'		=> 2.24692340000000E+22,
																							'ev'		=> 2.24692340000000E+22,
																							'HPh'		=> 1.34102248243839E-03,
																							'hh'		=> 1.34102248243839E-03,
																							'Wh'		=> 1.0,
																							'wh'		=> 1.0,
																							'flb'		=> 8.54294774062316E+04,
																							'BTU'		=> 3.41213254164705E+00,
																							'btu'		=> 3.41213254164705E+00
																						),
																		'flb'	=> array(	'J'			=> 4.21400003236424E-02,
																							'e'			=> 4.21399800687660E+05,
																							'c'			=> 1.00717234301644E-02,
																							'cal'		=> 1.00649785509554E-02,
																							'eV'		=> 2.63015000000000E+17,
																							'ev'		=> 2.63015000000000E+17,
																							'HPh'		=> 1.56974211145130E-08,
																							'hh'		=> 1.56974211145130E-08,
																							'Wh'		=> 1.17055614802000E-05,
																							'wh'		=> 1.17055614802000E-05,
																							'flb'		=> 1.0,
																							'BTU'		=> 3.99409272448406E-05,
																							'btu'		=> 3.99409272448406E-05
																						),
																		'BTU'	=> array(	'J'			=> 1.05505813786749E+03,
																							'e'			=> 1.05505763074665E+10,
																							'c'			=> 2.52165488508168E+02,
																							'cal'		=> 2.51996617135510E+02,
																							'eV'		=> 6.58510000000000E+21,
																							'ev'		=> 6.58510000000000E+21,
																							'HPh'		=> 3.93015941224568E-04,
																							'hh'		=> 3.93015941224568E-04,
																							'Wh'		=> 2.93071851047526E-01,
																							'wh'		=> 2.93071851047526E-01,
																							'flb'		=> 2.50369750774671E+04,
																							'BTU'		=> 1.0,
																							'btu'		=> 1.0,
																						),
																		'btu'	=> array(	'J'			=> 1.05505813786749E+03,
																							'e'			=> 1.05505763074665E+10,
																							'c'			=> 2.52165488508168E+02,
																							'cal'		=> 2.51996617135510E+02,
																							'eV'		=> 6.58510000000000E+21,
																							'ev'		=> 6.58510000000000E+21,
																							'HPh'		=> 3.93015941224568E-04,
																							'hh'		=> 3.93015941224568E-04,
																							'Wh'		=> 2.93071851047526E-01,
																							'wh'		=> 2.93071851047526E-01,
																							'flb'		=> 2.50369750774671E+04,
																							'BTU'		=> 1.0,
																							'btu'		=> 1.0,
																						)
																	),
												'Power'		=> array(	'HP'	=> array(	'HP'		=> 1.0,
																							'h'			=> 1.0,
																							'W'			=> 7.45701000000000E+02,
																							'w'			=> 7.45701000000000E+02
																						),
																		'h'		=> array(	'HP'		=> 1.0,
																							'h'			=> 1.0,
																							'W'			=> 7.45701000000000E+02,
																							'w'			=> 7.45701000000000E+02
																						),
																		'W'		=> array(	'HP'		=> 1.34102006031908E-03,
																							'h'			=> 1.34102006031908E-03,
																							'W'			=> 1.0,
																							'w'			=> 1.0
																						),
																		'w'		=> array(	'HP'		=> 1.34102006031908E-03,
																							'h'			=> 1.34102006031908E-03,
																							'W'			=> 1.0,
																							'w'			=> 1.0
																						)
																	),
												'Magnetism'	=> array(	'T'		=> array(	'T'			=> 1.0,
																							'ga'		=> 10000.0
																						),
																		'ga'	=> array(	'T'			=> 0.0001,
																							'ga'		=> 1.0
																						)
																	),
												'Liquid'	=> array(	'tsp'	=> array(	'tsp'		=> 1.0,
																							'tbs'		=> 3.33333333333333E-01,
																							'oz'		=> 1.66666666666667E-01,
																							'cup'		=> 2.08333333333333E-02,
																							'pt'		=> 1.04166666666667E-02,
																							'us_pt'		=> 1.04166666666667E-02,
																							'uk_pt'		=> 8.67558516821960E-03,
																							'qt'		=> 5.20833333333333E-03,
																							'gal'		=> 1.30208333333333E-03,
																							'l'			=> 4.92999408400710E-03,
																							'lt'		=> 4.92999408400710E-03
																						),
																		'tbs'	=> array(	'tsp'		=> 3.00000000000000E+00,
																							'tbs'		=> 1.0,
																							'oz'		=> 5.00000000000000E-01,
																							'cup'		=> 6.25000000000000E-02,
																							'pt'		=> 3.12500000000000E-02,
																							'us_pt'		=> 3.12500000000000E-02,
																							'uk_pt'		=> 2.60267555046588E-02,
																							'qt'		=> 1.56250000000000E-02,
																							'gal'		=> 3.90625000000000E-03,
																							'l'			=> 1.47899822520213E-02,
																							'lt'		=> 1.47899822520213E-02
																						),
																		'oz'	=> array(	'tsp'		=> 6.00000000000000E+00,
																							'tbs'		=> 2.00000000000000E+00,
																							'oz'		=> 1.0,
																							'cup'		=> 1.25000000000000E-01,
																							'pt'		=> 6.25000000000000E-02,
																							'us_pt'		=> 6.25000000000000E-02,
																							'uk_pt'		=> 5.20535110093176E-02,
																							'qt'		=> 3.12500000000000E-02,
																							'gal'		=> 7.81250000000000E-03,
																							'l'			=> 2.95799645040426E-02,
																							'lt'		=> 2.95799645040426E-02
																						),
																		'cup'	=> array(	'tsp'		=> 4.80000000000000E+01,
																							'tbs'		=> 1.60000000000000E+01,
																							'oz'		=> 8.00000000000000E+00,
																							'cup'		=> 1.0,
																							'pt'		=> 5.00000000000000E-01,
																							'us_pt'		=> 5.00000000000000E-01,
																							'uk_pt'		=> 4.16428088074541E-01,
																							'qt'		=> 2.50000000000000E-01,
																							'gal'		=> 6.25000000000000E-02,
																							'l'			=> 2.36639716032341E-01,
																							'lt'		=> 2.36639716032341E-01
																						),
																		'pt'	=> array(	'tsp'		=> 9.60000000000000E+01,
																							'tbs'		=> 3.20000000000000E+01,
																							'oz'		=> 1.60000000000000E+01,
																							'cup'		=> 2.00000000000000E+00,
																							'pt'		=> 1.0,
																							'us_pt'		=> 1.0,
																							'uk_pt'		=> 8.32856176149081E-01,
																							'qt'		=> 5.00000000000000E-01,
																							'gal'		=> 1.25000000000000E-01,
																							'l'			=> 4.73279432064682E-01,
																							'lt'		=> 4.73279432064682E-01
																						),
																		'us_pt'	=> array(	'tsp'		=> 9.60000000000000E+01,
																							'tbs'		=> 3.20000000000000E+01,
																							'oz'		=> 1.60000000000000E+01,
																							'cup'		=> 2.00000000000000E+00,
																							'pt'		=> 1.0,
																							'us_pt'		=> 1.0,
																							'uk_pt'		=> 8.32856176149081E-01,
																							'qt'		=> 5.00000000000000E-01,
																							'gal'		=> 1.25000000000000E-01,
																							'l'			=> 4.73279432064682E-01,
																							'lt'		=> 4.73279432064682E-01
																						),
																		'uk_pt'	=> array(	'tsp'		=> 1.15266000000000E+02,
																							'tbs'		=> 3.84220000000000E+01,
																							'oz'		=> 1.92110000000000E+01,
																							'cup'		=> 2.40137500000000E+00,
																							'pt'		=> 1.20068750000000E+00,
																							'us_pt'		=> 1.20068750000000E+00,
																							'uk_pt'		=> 1.0,
																							'qt'		=> 6.00343750000000E-01,
																							'gal'		=> 1.50085937500000E-01,
																							'l'			=> 5.68260698087162E-01,
																							'lt'		=> 5.68260698087162E-01
																						),
																		'qt'	=> array(	'tsp'		=> 1.92000000000000E+02,
																							'tbs'		=> 6.40000000000000E+01,
																							'oz'		=> 3.20000000000000E+01,
																							'cup'		=> 4.00000000000000E+00,
																							'pt'		=> 2.00000000000000E+00,
																							'us_pt'		=> 2.00000000000000E+00,
																							'uk_pt'		=> 1.66571235229816E+00,
																							'qt'		=> 1.0,
																							'gal'		=> 2.50000000000000E-01,
																							'l'			=> 9.46558864129363E-01,
																							'lt'		=> 9.46558864129363E-01
																						),
																		'gal'	=> array(	'tsp'		=> 7.68000000000000E+02,
																							'tbs'		=> 2.56000000000000E+02,
																							'oz'		=> 1.28000000000000E+02,
																							'cup'		=> 1.60000000000000E+01,
																							'pt'		=> 8.00000000000000E+00,
																							'us_pt'		=> 8.00000000000000E+00,
																							'uk_pt'		=> 6.66284940919265E+00,
																							'qt'		=> 4.00000000000000E+00,
																							'gal'		=> 1.0,
																							'l'			=> 3.78623545651745E+00,
																							'lt'		=> 3.78623545651745E+00
																						),
																		'l'		=> array(	'tsp'		=> 2.02840000000000E+02,
																							'tbs'		=> 6.76133333333333E+01,
																							'oz'		=> 3.38066666666667E+01,
																							'cup'		=> 4.22583333333333E+00,
																							'pt'		=> 2.11291666666667E+00,
																							'us_pt'		=> 2.11291666666667E+00,
																							'uk_pt'		=> 1.75975569552166E+00,
																							'qt'		=> 1.05645833333333E+00,
																							'gal'		=> 2.64114583333333E-01,
																							'l'			=> 1.0,
																							'lt'		=> 1.0
																						),
																		'lt'	=> array(	'tsp'		=> 2.02840000000000E+02,
																							'tbs'		=> 6.76133333333333E+01,
																							'oz'		=> 3.38066666666667E+01,
																							'cup'		=> 4.22583333333333E+00,
																							'pt'		=> 2.11291666666667E+00,
																							'us_pt'		=> 2.11291666666667E+00,
																							'uk_pt'		=> 1.75975569552166E+00,
																							'qt'		=> 1.05645833333333E+00,
																							'gal'		=> 2.64114583333333E-01,
																							'l'			=> 1.0,
																							'lt'		=> 1.0
																						)
																	)
											);


	/**
	 * getConversionGroups
	 *
	 * @return	array
	 */
	public static function getConversionGroups() {
		$conversionGroups = array();
		foreach(self::$_conversionUnits as $conversionUnit) {
			$conversionGroups[] = $conversionUnit['Group'];
		}
		return array_merge(array_unique($conversionGroups));
	}	//	function getConversionGroups()


	/**
	 * getConversionGroupUnits
	 *
	 * @return	array
	 */
	public static function getConversionGroupUnits($group = NULL) {
		$conversionGroups = array();
		foreach(self::$_conversionUnits as $conversionUnit => $conversionGroup) {
			if ((is_null($group)) || ($conversionGroup['Group'] == $group)) {
				$conversionGroups[$conversionGroup['Group']][] = $conversionUnit;
			}
		}
		return $conversionGroups;
	}	//	function getConversionGroupUnits()


	/**
	 * getConversionGroupUnitDetails
	 *
	 * @return	array
	 */
	public static function getConversionGroupUnitDetails($group = NULL) {
		$conversionGroups = array();
		foreach(self::$_conversionUnits as $conversionUnit => $conversionGroup) {
			if ((is_null($group)) || ($conversionGroup['Group'] == $group)) {
				$conversionGroups[$conversionGroup['Group']][] = array(	'unit'			=> $conversionUnit,
																		'description'	=> $conversionGroup['Unit Name']
																	  );
			}
		}
		return $conversionGroups;
	}	//	function getConversionGroupUnitDetails()


	/**
	 * getConversionGroups
	 *
	 * @return	array
	 */
	public static function getConversionMultipliers() {
		return self::$_conversionMultipliers;
	}	//	function getConversionGroups()


	/**
	 * CONVERTUOM
	 *
	 * @param	float		$value
	 * @param	string		$fromUOM
	 * @param	string		$toUOM
	 * @return	float
	 */
	public static function CONVERTUOM($value, $fromUOM, $toUOM) {
		$value		= self::flattenSingleValue($value);
		$fromUOM	= self::flattenSingleValue($fromUOM);
		$toUOM		= self::flattenSingleValue($toUOM);

		if (!is_numeric($value)) {
			return self::$_errorCodes['value'];
		}
		$fromMultiplier = 1;
		if (isset(self::$_conversionUnits[$fromUOM])) {
			$unitGroup1 = self::$_conversionUnits[$fromUOM]['Group'];
		} else {
			$fromMultiplier = substr($fromUOM,0,1);
			$fromUOM = substr($fromUOM,1);
			if (isset(self::$_conversionMultipliers[$fromMultiplier])) {
				$fromMultiplier = self::$_conversionMultipliers[$fromMultiplier]['multiplier'];
			} else {
				return self::$_errorCodes['na'];
			}
			if ((isset(self::$_conversionUnits[$fromUOM])) && (self::$_conversionUnits[$fromUOM]['AllowPrefix'])) {
				$unitGroup1 = self::$_conversionUnits[$fromUOM]['Group'];
			} else {
				return self::$_errorCodes['na'];
			}
		}
		$value *= $fromMultiplier;

		$toMultiplier = 1;
		if (isset(self::$_conversionUnits[$toUOM])) {
			$unitGroup2 = self::$_conversionUnits[$toUOM]['Group'];
		} else {
			$toMultiplier = substr($toUOM,0,1);
			$toUOM = substr($toUOM,1);
			if (isset(self::$_conversionMultipliers[$toMultiplier])) {
				$toMultiplier = self::$_conversionMultipliers[$toMultiplier]['multiplier'];
			} else {
				return self::$_errorCodes['na'];
			}
			if ((isset(self::$_conversionUnits[$toUOM])) && (self::$_conversionUnits[$toUOM]['AllowPrefix'])) {
				$unitGroup2 = self::$_conversionUnits[$toUOM]['Group'];
			} else {
				return self::$_errorCodes['na'];
			}
		}
		if ($unitGroup1 != $unitGroup2) {
			return self::$_errorCodes['na'];
		}

		if ($fromUOM == $toUOM) {
			return 1.0;
		} elseif ($unitGroup1 == 'Temperature') {
			if (($fromUOM == 'F') || ($fromUOM == 'fah')) {
				if (($toUOM == 'F') || ($toUOM == 'fah')) {
					return 1.0;
				} else {
					$value = (($value - 32) / 1.8);
					if (($toUOM == 'K') || ($toUOM == 'kel')) {
						$value += 273.15;
					}
					return $value;
				}
			} elseif ((($fromUOM == 'K') || ($fromUOM == 'kel')) &&
					  (($toUOM == 'K') || ($toUOM == 'kel'))) {
						return 1.0;
			} elseif ((($fromUOM == 'C') || ($fromUOM == 'cel')) &&
					  (($toUOM == 'C') || ($toUOM == 'cel'))) {
					return 1.0;
			}
			if (($toUOM == 'F') || ($toUOM == 'fah')) {
				if (($fromUOM == 'K') || ($fromUOM == 'kel')) {
					$value -= 273.15;
				}
				return ($value * 1.8) + 32;
			}
			if (($toUOM == 'C') || ($toUOM == 'cel')) {
				return $value - 273.15;
			}
			return $value + 273.15;
		}
		return ($value * self::$_unitConversions[$unitGroup1][$fromUOM][$toUOM]) / $toMultiplier;
	}	//	function CONVERTUOM()


	/**
	 * BESSELI
	 *
	 * Returns the modified Bessel function, which is equivalent to the Bessel function evaluated for purely imaginary arguments
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return	int
	 */
	public static function BESSELI($x, $n) {
		$x	= self::flattenSingleValue($x);
		$n	= floor(self::flattenSingleValue($n));

		if ((is_numeric($x)) && (is_numeric($n))) {
			if ($n < 0) {
				return self::$_errorCodes['num'];
			}
			$f_2_PI = 2 * pi();

			if (abs($x) <= 30) {
				$fTerm = pow($x / 2, $n) / self::FACT($n);
				$nK = 1;
				$fResult = $fTerm;
				$fSqrX = pow($x,2) / 4;
				do {
					$fTerm *= $fSqrX;
					$fTerm /= ($nK * ($nK + $n));
					$fResult += $fTerm;
				} while ((abs($fTerm) > 1e-10) && (++$nK < 100));
			} else {
				$fXAbs = abs($x);
				$fResult = exp($fXAbs) / sqrt($f_2_PI * $fXAbs);
				if (($n && 1) && ($x < 0)) {
					$fResult = -$fResult;
				}
			}
			return $fResult;
		}
		return self::$_errorCodes['value'];
	}	//	function BESSELI()


	/**
	 * BESSELJ
	 *
	 * Returns the Bessel function
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return	int
	 */
	public static function BESSELJ($x, $n) {
		$x	= self::flattenSingleValue($x);
		$n	= floor(self::flattenSingleValue($n));

		if ((is_numeric($x)) && (is_numeric($n))) {
			if ($n < 0) {
				return self::$_errorCodes['num'];
			}
			$f_2_DIV_PI = 2 / pi();
			$f_PI_DIV_2 = pi() / 2;
			$f_PI_DIV_4 = pi() / 4;

			$fResult = 0;
			if (abs($x) <= 30) {
				$fTerm = pow($x / 2, $n) / self::FACT($n);
				$nK = 1;
				$fResult = $fTerm;
				$fSqrX = pow($x,2) / -4;
				do {
					$fTerm *= $fSqrX;
					$fTerm /= ($nK * ($nK + $n));
					$fResult += $fTerm;
				} while ((abs($fTerm) > 1e-10) && (++$nK < 100));
			} else {
				$fXAbs = abs($x);
				$fResult = sqrt($f_2_DIV_PI / $fXAbs) * cos($fXAbs - $n * $f_PI_DIV_2 - $f_PI_DIV_4);
				if (($n && 1) && ($x < 0)) {
					$fResult = -$fResult;
				}
			}
			return $fResult;
		}
		return self::$_errorCodes['value'];
	}	//	function BESSELJ()


	private static function _Besselk0($fNum) {
		if ($fNum <= 2) {
			$fNum2 = $fNum * 0.5;
			$y = pow($fNum2,2);
			$fRet = -log($fNum2) * self::BESSELI($fNum, 0) +
					(-0.57721566 + $y * (0.42278420 + $y * (0.23069756 + $y * (0.3488590e-1 + $y * (0.262698e-2 + $y *
					(0.10750e-3 + $y * 0.74e-5))))));
		} else {
			$y = 2 / $fNum;
			$fRet = exp(-$fNum) / sqrt($fNum) *
					(1.25331414 + $y * (-0.7832358e-1 + $y * (0.2189568e-1 + $y * (-0.1062446e-1 + $y *
					(0.587872e-2 + $y * (-0.251540e-2 + $y * 0.53208e-3))))));
		}
		return $fRet;
	}	//	function _Besselk0()


	private static function _Besselk1($fNum) {
		if ($fNum <= 2) {
			$fNum2 = $fNum * 0.5;
			$y = pow($fNum2,2);
			$fRet = log($fNum2) * self::BESSELI($fNum, 1) +
					(1 + $y * (0.15443144 + $y * (-0.67278579 + $y * (-0.18156897 + $y * (-0.1919402e-1 + $y *
					(-0.110404e-2 + $y * (-0.4686e-4))))))) / $fNum;
		} else {
			$y = 2 / $fNum;
			$fRet = exp(-$fNum) / sqrt($fNum) *
					(1.25331414 + $y * (0.23498619 + $y * (-0.3655620e-1 + $y * (0.1504268e-1 + $y * (-0.780353e-2 + $y *
					(0.325614e-2 + $y * (-0.68245e-3)))))));
		}
		return $fRet;
	}	//	function _Besselk1()


	/**
	 * BESSELK
	 *
	 * Returns the modified Bessel function, which is equivalent to the Bessel functions evaluated for purely imaginary arguments.
	 *
	 * @param	float		$x
	 * @param	float		$ord
	 * @return	float
	 */
	public static function BESSELK($x, $ord) {
		$x		= self::flattenSingleValue($x);
		$ord	= floor(self::flattenSingleValue($ord));

		if ((is_numeric($x)) && (is_numeric($ord))) {
			if ($ord < 0) {
				return self::$_errorCodes['num'];
			}

			switch($ord) {
				case 0 :	return self::_Besselk0($x);
							break;
				case 1 :	return self::_Besselk1($x);
							break;
				default :	$fTox	= 2 / $x;
							$fBkm	= self::_Besselk0($x);
							$fBk	= self::_Besselk1($x);
							for ($n = 1; $n < $ord; ++$n) {
								$fBkp	= $fBkm + $n * $fTox * $fBk;
								$fBkm	= $fBk;
								$fBk	= $fBkp;
							}
			}
			return $fBk;
		}
		return self::$_errorCodes['value'];
	}	//	function BESSELK()


	private static function _Bessely0($fNum) {
		if ($fNum < 8.0) {
			$y = pow($fNum,2);
			$f1 = -2957821389.0 + $y * (7062834065.0 + $y * (-512359803.6 + $y * (10879881.29 + $y * (-86327.92757 + $y * 228.4622733))));
			$f2 = 40076544269.0 + $y * (745249964.8 + $y * (7189466.438 + $y * (47447.26470 + $y * (226.1030244 + $y))));
			$fRet = $f1 / $f2 + 0.636619772 * self::BESSELJ($fNum, 0) * log($fNum);
		} else {
			$z = 8.0 / $fNum;
			$y = pow($z,2);
			$xx = $fNum - 0.785398164;
			$f1 = 1 + $y * (-0.1098628627e-2 + $y * (0.2734510407e-4 + $y * (-0.2073370639e-5 + $y * 0.2093887211e-6)));
			$f2 = -0.1562499995e-1 + $y * (0.1430488765e-3 + $y * (-0.6911147651e-5 + $y * (0.7621095161e-6 + $y * (-0.934945152e-7))));
			$fRet = sqrt(0.636619772 / $fNum) * (sin($xx) * $f1 + $z * cos($xx) * $f2);
		}
		return $fRet;
	}	//	function _Bessely0()


	private static function _Bessely1($fNum) {
		if ($fNum < 8.0) {
			$y = pow($fNum,2);
			$f1 = $fNum * (-0.4900604943e13 + $y * (0.1275274390e13 + $y * (-0.5153438139e11 + $y * (0.7349264551e9 + $y *
				(-0.4237922726e7 + $y * 0.8511937935e4)))));
			$f2 = 0.2499580570e14 + $y * (0.4244419664e12 + $y * (0.3733650367e10 + $y * (0.2245904002e8 + $y *
				(0.1020426050e6 + $y * (0.3549632885e3 + $y)))));
			$fRet = $f1 / $f2 + 0.636619772 * ( self::BESSELJ($fNum, 1) * log($fNum) - 1 / $fNum);
		} else {
			$z = 8.0 / $fNum;
			$y = pow($z,2);
			$xx = $fNum - 2.356194491;
			$f1 = 1 + $y * (0.183105e-2 + $y * (-0.3516396496e-4 + $y * (0.2457520174e-5 + $y * (-0.240337019e6))));
			$f2 = 0.04687499995 + $y * (-0.2002690873e-3 + $y * (0.8449199096e-5 + $y * (-0.88228987e-6 + $y * 0.105787412e-6)));
			$fRet = sqrt(0.636619772 / $fNum) * (sin($xx) * $f1 + $z * cos($xx) * $f2);
			#i12430# ...but this seems to work much better.
//			$fRet = sqrt(0.636619772 / $fNum) * sin($fNum - 2.356194491);
		}
		return $fRet;
	}	//	function _Bessely1()


	/**
	 * BESSELY
	 *
	 * Returns the Bessel function, which is also called the Weber function or the Neumann function.
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return	int
	 */
	public static function BESSELY($x, $ord) {
		$x		= self::flattenSingleValue($x);
		$ord	= floor(self::flattenSingleValue($ord));

		if ((is_numeric($x)) && (is_numeric($ord))) {
			if ($ord < 0) {
				return self::$_errorCodes['num'];
			}

			switch($ord) {
				case 0 :	return self::_Bessely0($x);
							break;
				case 1 :	return self::_Bessely1($x);
							break;
				default:	$fTox	= 2 / $x;
							$fBym	= self::_Bessely0($x);
							$fBy	= self::_Bessely1($x);
							for ($n = 1; $n < $ord; ++$n) {
								$fByp	= $n * $fTox * $fBy - $fBym;
								$fBym	= $fBy;
								$fBy	= $fByp;
							}
			}
			return $fBy;
		}
		return self::$_errorCodes['value'];
	}	//	function BESSELY()


	/**
	 * DELTA
	 *
	 * Tests whether two values are equal. Returns 1 if number1 = number2; returns 0 otherwise.
	 *
	 * @param	float		$a
	 * @param	float		$b
	 * @return	int
	 */
	public static function DELTA($a, $b=0) {
		$a	= self::flattenSingleValue($a);
		$b	= self::flattenSingleValue($b);

		return (int) ($a == $b);
	}	//	function DELTA()


	/**
	 * GESTEP
	 *
	 * Returns 1 if number = step; returns 0 (zero) otherwise
	 *
	 * @param	float		$number
	 * @param	float		$step
	 * @return	int
	 */
	public static function GESTEP($number, $step=0) {
		$number	= self::flattenSingleValue($number);
		$step	= self::flattenSingleValue($step);

		return (int) ($number >= $step);
	}	//	function GESTEP()


	//
	//	Private method to calculate the erf value
	//
	private static $_two_sqrtpi = 1.128379167095512574;
	private static $_rel_error = 1E-15;

	private static function _erfVal($x) {
		if (abs($x) > 2.2) {
			return 1 - self::_erfcVal($x);
		}
		$sum = $term = $x;
		$xsqr = pow($x,2);
		$j = 1;
		do {
			$term *= $xsqr / $j;
			$sum -= $term / (2 * $j + 1);
			++$j;
			$term *= $xsqr / $j;
			$sum += $term / (2 * $j + 1);
			++$j;
			if ($sum == 0) {
				break;
			}
		} while (abs($term / $sum) > self::$_rel_error);
		return self::$_two_sqrtpi * $sum;
	}	//	function _erfVal()


	/**
	 * ERF
	 *
	 * Returns the error function integrated between lower_limit and upper_limit
	 *
	 * @param	float		$lower	lower bound for integrating ERF
	 * @param	float		$upper	upper bound for integrating ERF.
	 *								If omitted, ERF integrates between zero and lower_limit
	 * @return	int
	 */
	public static function ERF($lower, $upper = null) {
		$lower	= self::flattenSingleValue($lower);
		$upper	= self::flattenSingleValue($upper);

		if (is_numeric($lower)) {
			if ($lower < 0) {
				return self::$_errorCodes['num'];
			}
			if (is_null($upper)) {
				return self::_erfVal($lower);
			}
			if (is_numeric($upper)) {
				if ($upper < 0) {
					return self::$_errorCodes['num'];
				}
				return self::_erfVal($upper) - self::_erfVal($lower);
			}
		}
		return self::$_errorCodes['value'];
	}	//	function ERF()


	//
	//	Private method to calculate the erfc value
	//
	private static $_one_sqrtpi = 0.564189583547756287;

	private static function _erfcVal($x) {
		if (abs($x) < 2.2) {
			return 1 - self::_erfVal($x);
		}
		if ($x < 0) {
			return 2 - self::erfc(-$x);
		}
		$a = $n = 1;
		$b = $c = $x;
		$d = pow($x,2) + 0.5;
		$q1 = $q2 = $b / $d;
		$t = 0;
		do {
			$t = $a * $n + $b * $x;
			$a = $b;
			$b = $t;
			$t = $c * $n + $d * $x;
			$c = $d;
			$d = $t;
			$n += 0.5;
			$q1 = $q2;
			$q2 = $b / $d;
		} while ((abs($q1 - $q2) / $q2) > self::$_rel_error);
		return self::$_one_sqrtpi * exp(-$x * $x) * $q2;
	}	//	function _erfcVal()


	/**
	 * ERFC
	 *
	 * Returns the complementary ERF function integrated between x and infinity
	 *
	 * @param	float		$x		The lower bound for integrating ERF
	 * @return	int
	 */
	public static function ERFC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_numeric($x)) {
			if ($x < 0) {
				return self::$_errorCodes['num'];
			}
			return self::_erfcVal($x);
		}
		return self::$_errorCodes['value'];
	}	//	function ERFC()


	/**
	 *	LOWERCASE
	 *
	 *	Converts a string value to upper case.
	 *
	 *	@param	string		$mixedCaseString
	 *	@return	string
	 */
	public static function LOWERCASE($mixedCaseString) {
		$mixedCaseString	= self::flattenSingleValue($mixedCaseString);

		if (function_exists('mb_convert_case')) {
			return mb_convert_case($mixedCaseString, MB_CASE_LOWER, 'UTF-8');
		} else {
			return strtoupper($mixedCaseString);
		}
	}	//	function LOWERCASE()


	/**
	 *	UPPERCASE
	 *
	 *	Converts a string value to upper case.
	 *
	 *	@param	string		$mixedCaseString
	 *	@return	string
	 */
	public static function UPPERCASE($mixedCaseString) {
		$mixedCaseString	= self::flattenSingleValue($mixedCaseString);

		if (function_exists('mb_convert_case')) {
			return mb_convert_case($mixedCaseString, MB_CASE_UPPER, 'UTF-8');
		} else {
			return strtoupper($mixedCaseString);
		}
	}	//	function UPPERCASE()


	/**
	 *	PROPERCASE
	 *
	 *	Converts a string value to upper case.
	 *
	 *	@param	string		$mixedCaseString
	 *	@return	string
	 */
	public static function PROPERCASE($mixedCaseString) {
		$mixedCaseString	= self::flattenSingleValue($mixedCaseString);

		if (function_exists('mb_convert_case')) {
			return mb_convert_case($mixedCaseString, MB_CASE_TITLE, 'UTF-8');
		} else {
			return ucwords($mixedCaseString);
		}
	}	//	function PROPERCASE()


	/**
	 *	DOLLAR
	 *
	 *	This function converts a number to text using currency format, with the decimals rounded to the specified place.
	 *	The format used is $#,##0.00_);($#,##0.00)..
	 *
	 *	@param	float	$value			The value to format
	 *	@param	int		$decimals		The number of digits to display to the right of the decimal point.
	 *									If decimals is negative, number is rounded to the left of the decimal point.
	 *									If you omit decimals, it is assumed to be 2
	 *	@return	string
	 */
	public static function DOLLAR($value = 0, $decimals = 2) {
		$value		= self::flattenSingleValue($value);
		$decimals	= self::flattenSingleValue($decimals);

		// Validate parameters
		if (!is_numeric($value) || !is_numeric($decimals)) {
			return self::$_errorCodes['num'];
		}
		$decimals = floor($decimals);

		if ($decimals > 0) {
			return money_format('%.'.$decimals.'n',$value);
		} else {
			$round = pow(10,abs($decimals));
			if ($value < 0) { $round = 0-$round; }
			$value = self::MROUND($value,$round);
			//	The implementation of money_format used if the standard PHP function is not available can't handle decimal places of 0,
			//		so we display to 1 dp and chop off that character and the decimal separator using substr
			return substr(money_format('%.1n',$value),0,-2);
		}
	}	//	function DOLLAR()


	/**
	 * DOLLARDE
	 *
	 * Converts a dollar price expressed as an integer part and a fraction part into a dollar price expressed as a decimal number.
	 * Fractional dollar numbers are sometimes used for security prices.
	 *
	 * @param	float	$fractional_dollar	Fractional Dollar
	 * @param	int		$fraction			Fraction
	 * @return	float
	 */
	public static function DOLLARDE($fractional_dollar = Null, $fraction = 0) {
		$fractional_dollar	= self::flattenSingleValue($fractional_dollar);
		$fraction			= (int)self::flattenSingleValue($fraction);

		// Validate parameters
		if (is_null($fractional_dollar) || $fraction < 0) {
			return self::$_errorCodes['num'];
		}
		if ($fraction == 0) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$dollars = floor($fractional_dollar);
		$cents = fmod($fractional_dollar,1);
		$cents /= $fraction;
		$cents *= pow(10,ceil(log10($fraction)));
		return $dollars + $cents;
	}	//	function DOLLARDE()


	/**
	 * DOLLARFR
	 *
	 * Converts a dollar price expressed as a decimal number into a dollar price expressed as a fraction.
	 * Fractional dollar numbers are sometimes used for security prices.
	 *
	 * @param	float	$decimal_dollar		Decimal Dollar
	 * @param	int		$fraction			Fraction
	 * @return	float
	 */
	public static function DOLLARFR($decimal_dollar = Null, $fraction = 0) {
		$decimal_dollar	= self::flattenSingleValue($decimal_dollar);
		$fraction		= (int)self::flattenSingleValue($fraction);

		// Validate parameters
		if (is_null($decimal_dollar) || $fraction < 0) {
			return self::$_errorCodes['num'];
		}
		if ($fraction == 0) {
			return self::$_errorCodes['divisionbyzero'];
		}

		$dollars = floor($decimal_dollar);
		$cents = fmod($decimal_dollar,1);
		$cents *= $fraction;
		$cents *= pow(10,-ceil(log10($fraction)));
		return $dollars + $cents;
	}	//	function DOLLARFR()


	/**
	 * EFFECT
	 *
	 * Returns the effective interest rate given the nominal rate and the number of compounding payments per year.
	 *
	 * @param	float	$nominal_rate		Nominal interest rate
	 * @param	int		$npery				Number of compounding payments per year
	 * @return	float
	 */
	public static function EFFECT($nominal_rate = 0, $npery = 0) {
		$nominal_rate	= self::flattenSingleValue($nominal_rate);
		$npery			= (int)self::flattenSingleValue($npery);

		// Validate parameters
		if ($nominal_rate <= 0 || $npery < 1) {
			return self::$_errorCodes['num'];
		}

		return pow((1 + $nominal_rate / $npery), $npery) - 1;
	}	//	function EFFECT()


	/**
	 * NOMINAL
	 *
	 * Returns the nominal interest rate given the effective rate and the number of compounding payments per year.
	 *
	 * @param	float	$effect_rate	Effective interest rate
	 * @param	int		$npery			Number of compounding payments per year
	 * @return	float
	 */
	public static function NOMINAL($effect_rate = 0, $npery = 0) {
		$effect_rate	= self::flattenSingleValue($effect_rate);
		$npery			= (int)self::flattenSingleValue($npery);

		// Validate parameters
		if ($effect_rate <= 0 || $npery < 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		return $npery * (pow($effect_rate + 1, 1 / $npery) - 1);
	}	//	function NOMINAL()


	/**
	 * PV
	 *
	 * Returns the Present Value of a cash flow with constant payments and interest rate (annuities).
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$nper	Number of periods
	 * @param	float	$pmt	Periodic payment (annuity)
	 * @param	float	$fv		Future Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function PV($rate = 0, $nper = 0, $pmt = 0, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= self::flattenSingleValue($nper);
		$pmt	= self::flattenSingleValue($pmt);
		$fv		= self::flattenSingleValue($fv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return (-$pmt * (1 + $rate * $type) * ((pow(1 + $rate, $nper) - 1) / $rate) - $fv) / pow(1 + $rate, $nper);
		} else {
			return -$fv - $pmt * $nper;
		}
	}	//	function PV()


	/**
	 * FV
	 *
	 * Returns the Future Value of a cash flow with constant payments and interest rate (annuities).
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$nper	Number of periods
	 * @param	float	$pmt	Periodic payment (annuity)
	 * @param	float	$pv		Present Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function FV($rate = 0, $nper = 0, $pmt = 0, $pv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= self::flattenSingleValue($nper);
		$pmt	= self::flattenSingleValue($pmt);
		$pv		= self::flattenSingleValue($pv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return -$pv * pow(1 + $rate, $nper) - $pmt * (1 + $rate * $type) * (pow(1 + $rate, $nper) - 1) / $rate;
		} else {
			return -$pv - $pmt * $nper;
		}
	}	//	function FV()


	/**
	 * PMT
	 *
	 * Returns the constant payment (annuity) for a cash flow with a constant interest rate.
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$nper	Number of periods
	 * @param	float	$pv		Present Value
	 * @param	float	$fv		Future Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function PMT($rate = 0, $nper = 0, $pv = 0, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= self::flattenSingleValue($nper);
		$pv		= self::flattenSingleValue($pv);
		$fv		= self::flattenSingleValue($fv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return (-$fv - $pv * pow(1 + $rate, $nper)) / (1 + $rate * $type) / ((pow(1 + $rate, $nper) - 1) / $rate);
		} else {
			return (-$pv - $fv) / $nper;
		}
	}	//	function PMT()


	/**
	 * NPER
	 *
	 * Returns the number of periods for a cash flow with constant periodic payments (annuities), and interest rate.
	 *
	 *	@param	float	$rate	Interest rate per period
	 *	@param	int		$pmt	Periodic payment (annuity)
	 *	@param	float	$pv		Present Value
	 *	@param	float	$fv		Future Value
	 *	@param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 *	@return	float
	 */
	public static function NPER($rate = 0, $pmt = 0, $pv = 0, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$pmt	= self::flattenSingleValue($pmt);
		$pv		= self::flattenSingleValue($pv);
		$fv		= self::flattenSingleValue($fv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			if ($pmt == 0 && $pv == 0) {
				return self::$_errorCodes['num'];
			}
			return log(($pmt * (1 + $rate * $type) / $rate - $fv) / ($pv + $pmt * (1 + $rate * $type) / $rate)) / log(1 + $rate);
		} else {
			if ($pmt == 0) {
				return self::$_errorCodes['num'];
			}
			return (-$pv -$fv) / $pmt;
		}
	}	//	function NPER()


	private static function _interestAndPrincipal($rate=0, $per=0, $nper=0, $pv=0, $fv=0, $type=0) {
		$pmt = self::PMT($rate, $nper, $pv, $fv, $type);
		$capital = $pv;
		for ($i = 1; $i<= $per; ++$i) {
			$interest = ($type && $i == 1)? 0 : -$capital * $rate;
			$principal = $pmt - $interest;
			$capital += $principal;
		}
		return array($interest, $principal);
	}	//	function _interestAndPrincipal()


	/**
	 *	IPMT
	 *
	 *	Returns the interest payment for a given period for an investment based on periodic, constant payments and a constant interest rate.
	 *
	 *	@param	float	$rate	Interest rate per period
	 *	@param	int		$per	Period for which we want to find the interest
	 *	@param	int		$nper	Number of periods
	 *	@param	float	$pv		Present Value
	 *	@param	float	$fv		Future Value
	 *	@param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 *	@return	float
	 */
	public static function IPMT($rate, $per, $nper, $pv, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$per	= (int) self::flattenSingleValue($per);
		$nper	= (int) self::flattenSingleValue($nper);
		$pv		= self::flattenSingleValue($pv);
		$fv		= self::flattenSingleValue($fv);
		$type	= (int) self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}
		if ($per <= 0 || $per > $nper) {
			return self::$_errorCodes['value'];
		}

		// Calculate
		$interestAndPrincipal = self::_interestAndPrincipal($rate, $per, $nper, $pv, $fv, $type);
		return $interestAndPrincipal[0];
	}	//	function IPMT()


	/**
	 *	CUMIPMT
	 *
	 *	Returns the cumulative interest paid on a loan between start_period and end_period.
	 *
	 *	@param	float	$rate	Interest rate per period
	 *	@param	int		$nper	Number of periods
	 *	@param	float	$pv		Present Value
	 *	@param	int		start	The first period in the calculation.
	 *								Payment periods are numbered beginning with 1.
	 *	@param	int		end		The last period in the calculation.
	 *	@param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 *	@return	float
	 */
	public static function CUMIPMT($rate, $nper, $pv, $start, $end, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= (int) self::flattenSingleValue($nper);
		$pv		= self::flattenSingleValue($pv);
		$start	= (int) self::flattenSingleValue($start);
		$end	= (int) self::flattenSingleValue($end);
		$type	= (int) self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}
		if ($start < 1 || $start > $end) {
			return self::$_errorCodes['value'];
		}

		// Calculate
		$interest = 0;
		for ($per = $start; $per <= $end; ++$per) {
			$interest += self::IPMT($rate, $per, $nper, $pv, 0, $type);
		}

		return $interest;
	}	//	function CUMIPMT()


	/**
	 *	PPMT
	 *
	 *	Returns the interest payment for a given period for an investment based on periodic, constant payments and a constant interest rate.
	 *
	 *	@param	float	$rate	Interest rate per period
	 *	@param	int		$per	Period for which we want to find the interest
	 *	@param	int		$nper	Number of periods
	 *	@param	float	$pv		Present Value
	 *	@param	float	$fv		Future Value
	 *	@param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 *	@return	float
	 */
	public static function PPMT($rate, $per, $nper, $pv, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$per	= (int) self::flattenSingleValue($per);
		$nper	= (int) self::flattenSingleValue($nper);
		$pv		= self::flattenSingleValue($pv);
		$fv		= self::flattenSingleValue($fv);
		$type	= (int) self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}
		if ($per <= 0 || $per > $nper) {
			return self::$_errorCodes['value'];
		}

		// Calculate
		$interestAndPrincipal = self::_interestAndPrincipal($rate, $per, $nper, $pv, $fv, $type);
		return $interestAndPrincipal[1];
	}	//	function PPMT()


	/**
	 *	CUMPRINC
	 *
	 *	Returns the cumulative principal paid on a loan between start_period and end_period.
	 *
	 *	@param	float	$rate	Interest rate per period
	 *	@param	int		$nper	Number of periods
	 *	@param	float	$pv		Present Value
	 *	@param	int		start	The first period in the calculation.
	 *								Payment periods are numbered beginning with 1.
	 *	@param	int		end		The last period in the calculation.
	 *	@param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 *	@return	float
	 */
	public static function CUMPRINC($rate, $nper, $pv, $start, $end, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= (int) self::flattenSingleValue($nper);
		$pv		= self::flattenSingleValue($pv);
		$start	= (int) self::flattenSingleValue($start);
		$end	= (int) self::flattenSingleValue($end);
		$type	= (int) self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}
		if ($start < 1 || $start > $end) {
			return self::$_errorCodes['value'];
		}

		// Calculate
		$principal = 0;
		for ($per = $start; $per <= $end; ++$per) {
			$principal += self::PPMT($rate, $per, $nper, $pv, 0, $type);
		}

		return $principal;
	}	//	function CUMPRINC()


	/**
	 * NPV
	 *
	 * Returns the Net Present Value of a cash flow series given a discount rate.
	 *
	 * @param	float	Discount interest rate
	 * @param	array	Cash flow series
	 * @return	float
	 */
	public static function NPV() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$rate = array_shift($aArgs);
		for ($i = 1; $i <= count($aArgs); ++$i) {
			// Is it a numeric value?
			if (is_numeric($aArgs[$i - 1])) {
				$returnValue += $aArgs[$i - 1] / pow(1 + $rate, $i);
			}
		}

		// Return
		return $returnValue;
	}	//	function NPV()


	/**
	 *	DB
	 *
	 *	Returns the depreciation of an asset for a specified period using the fixed-declining balance method.
	 *	This form of depreciation is used if you want to get a higher depreciation value at the beginning of the depreciation
	 *		(as opposed to linear depreciation). The depreciation value is reduced with every depreciation period by the
	 *		depreciation already deducted from the initial cost.
	 *
	 *	@param	float	cost		Initial cost of the asset.
	 *	@param	float	salvage		Value at the end of the depreciation. (Sometimes called the salvage value of the asset)
	 *	@param	int		life		Number of periods over which the asset is depreciated. (Sometimes called the useful life of the asset)
	 *	@param	int		period		The period for which you want to calculate the depreciation. Period must use the same units as life.
	 *	@param	float	month		Number of months in the first year. If month is omitted, it defaults to 12.
	 *	@return	float
	 */
	public static function DB($cost, $salvage, $life, $period, $month=12) {
		$cost		= (float) self::flattenSingleValue($cost);
		$salvage	= (float) self::flattenSingleValue($salvage);
		$life		= (int) self::flattenSingleValue($life);
		$period		= (int) self::flattenSingleValue($period);
		$month		= (int) self::flattenSingleValue($month);

		//	Validate
		if ((is_numeric($cost)) && (is_numeric($salvage)) && (is_numeric($life)) && (is_numeric($period)) && (is_numeric($month))) {
			if ($cost == 0) {
				return 0.0;
			} elseif (($cost < 0) || (($salvage / $cost) < 0) || ($life <= 0) || ($period < 1) || ($month < 1)) {
				return self::$_errorCodes['num'];
			}
			//	Set Fixed Depreciation Rate
			$fixedDepreciationRate = 1 - pow(($salvage / $cost), (1 / $life));
			$fixedDepreciationRate = round($fixedDepreciationRate, 3);

			//	Loop through each period calculating the depreciation
			$previousDepreciation = 0;
			for ($per = 1; $per <= $period; ++$per) {
				if ($per == 1) {
					$depreciation = $cost * $fixedDepreciationRate * $month / 12;
				} elseif ($per == ($life + 1)) {
					$depreciation = ($cost - $previousDepreciation) * $fixedDepreciationRate * (12 - $month) / 12;
				} else {
					$depreciation = ($cost - $previousDepreciation) * $fixedDepreciationRate;
				}
				$previousDepreciation += $depreciation;
			}
			return $depreciation;
		}
		return self::$_errorCodes['value'];
	}	//	function DB()


	/**
	 *	DDB
	 *
	 *	Returns the depreciation of an asset for a specified period using the double-declining balance method or some other method you specify.
	 *
	 *	@param	float	cost		Initial cost of the asset.
	 *	@param	float	salvage		Value at the end of the depreciation. (Sometimes called the salvage value of the asset)
	 *	@param	int		life		Number of periods over which the asset is depreciated. (Sometimes called the useful life of the asset)
	 *	@param	int		period		The period for which you want to calculate the depreciation. Period must use the same units as life.
	 *	@param	float	factor		The rate at which the balance declines.
	 *								If factor is omitted, it is assumed to be 2 (the double-declining balance method).
	 *	@return	float
	 */
	public static function DDB($cost, $salvage, $life, $period, $factor=2.0) {
		$cost		= (float) self::flattenSingleValue($cost);
		$salvage	= (float) self::flattenSingleValue($salvage);
		$life		= (int) self::flattenSingleValue($life);
		$period		= (int) self::flattenSingleValue($period);
		$factor		= (float) self::flattenSingleValue($factor);

		//	Validate
		if ((is_numeric($cost)) && (is_numeric($salvage)) && (is_numeric($life)) && (is_numeric($period)) && (is_numeric($factor))) {
			if (($cost <= 0) || (($salvage / $cost) < 0) || ($life <= 0) || ($period < 1) || ($factor <= 0.0) || ($period > $life)) {
				return self::$_errorCodes['num'];
			}
			//	Set Fixed Depreciation Rate
			$fixedDepreciationRate = 1 - pow(($salvage / $cost), (1 / $life));
			$fixedDepreciationRate = round($fixedDepreciationRate, 3);

			//	Loop through each period calculating the depreciation
			$previousDepreciation = 0;
			for ($per = 1; $per <= $period; ++$per) {
				$depreciation = min( ($cost - $previousDepreciation) * ($factor / $life), ($cost - $salvage - $previousDepreciation) );
				$previousDepreciation += $depreciation;
			}
			return $depreciation;
		}
		return self::$_errorCodes['value'];
	}	//	function DDB()


	private static function _daysPerYear($year,$basis) {
		switch ($basis) {
			case 0 :
			case 2 :
			case 4 :
				$daysPerYear = 360;
				break;
			case 3 :
				$daysPerYear = 365;
				break;
			case 1 :
				if (self::_isLeapYear(self::YEAR($year))) {
					$daysPerYear = 366;
				} else {
					$daysPerYear = 365;
				}
				break;
			default	:
				return self::$_errorCodes['num'];
		}
		return $daysPerYear;
	}	//	function _daysPerYear()


	/**
	 *	ACCRINT
	 *
	 *	Returns the discount rate for a security.
	 *
	 *	@param	mixed	issue		The security's issue date.
	 *	@param	mixed	firstinter	The security's first interest date.
	 *	@param	mixed	settlement	The security's settlement date.
	 *	@param	float	rate		The security's annual coupon rate.
	 *	@param	float	par			The security's par value.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function ACCRINT($issue, $firstinter, $settlement, $rate, $par=1000, $frequency=1, $basis=0) {
		$issue		= self::flattenSingleValue($issue);
		$firstinter	= self::flattenSingleValue($firstinter);
		$settlement	= self::flattenSingleValue($settlement);
		$rate		= (float) self::flattenSingleValue($rate);
		$par		= (is_null($par))	? 1000	: (float) self::flattenSingleValue($par);
		$frequency	= (is_null($frequency))	? 1	: (int) self::flattenSingleValue($frequency);
		$basis		= (is_null($basis))	? 0		: (int) self::flattenSingleValue($basis);

		//	Validate
		if ((is_numeric($rate)) && (is_numeric($par))) {
			if (($rate <= 0) || ($par <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysBetweenIssueAndSettlement = self::YEARFRAC($issue, $settlement, $basis);
			if (!is_numeric($daysBetweenIssueAndSettlement)) {
				return $daysBetweenIssueAndSettlement;
			}
			$daysPerYear = self::_daysPerYear(self::YEAR($issue),$basis);
			if (!is_numeric($daysPerYear)) {
				return $daysPerYear;
			}
			$daysBetweenIssueAndSettlement *= $daysPerYear;

			return $par * $rate * ($daysBetweenIssueAndSettlement / $daysPerYear);
		}
		return self::$_errorCodes['value'];
	}	//	function ACCRINT()


	/**
	 *	ACCRINTM
	 *
	 *	Returns the discount rate for a security.
	 *
	 *	@param	mixed	issue		The security's issue date.
	 *	@param	mixed	settlement	The security's settlement date.
	 *	@param	float	rate		The security's annual coupon rate.
	 *	@param	float	par			The security's par value.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function ACCRINTM($issue, $settlement, $rate, $par=1000, $basis=0) {
		$issue		= self::flattenSingleValue($issue);
		$settlement	= self::flattenSingleValue($settlement);
		$rate		= (float) self::flattenSingleValue($rate);
		$par		= (is_null($par))	? 1000	: (float) self::flattenSingleValue($par);
		$basis		= (is_null($basis))	? 0		: (int) self::flattenSingleValue($basis);

		//	Validate
		if ((is_numeric($rate)) && (is_numeric($par))) {
			if (($rate <= 0) || ($par <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysBetweenIssueAndSettlement = self::YEARFRAC($issue, $settlement, $basis);
			if (!is_numeric($daysBetweenIssueAndSettlement)) {
				return $daysBetweenIssueAndSettlement;
			}
			$daysPerYear = self::_daysPerYear(self::YEAR($issue),$basis);
			if (!is_numeric($daysPerYear)) {
				return $daysPerYear;
			}
			$daysBetweenIssueAndSettlement *= $daysPerYear;

			return $par * $rate * ($daysBetweenIssueAndSettlement / $daysPerYear);
		}
		return self::$_errorCodes['value'];
	}	//	function ACCRINTM()


	public static function AMORDEGRC($cost, $purchased, $firstPeriod, $salvage, $period, $rate, $basis=0) {
	}	//	function AMORDEGRC()


	public static function AMORLINC($cost, $purchased, $firstPeriod, $salvage, $period, $rate, $basis=0) {
	}	//	function AMORLINC()


	/**
	 *	DISC
	 *
	 *	Returns the discount rate for a security.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	int		price		The security's price per $100 face value.
	 *	@param	int		redemption	the security's redemption value per $100 face value.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function DISC($settlement, $maturity, $price, $redemption, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$price		= (float) self::flattenSingleValue($price);
		$redemption	= (float) self::flattenSingleValue($redemption);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if ((is_numeric($price)) && (is_numeric($redemption)) && (is_numeric($basis))) {
			if (($price <= 0) || ($redemption <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity, $basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}

			return ((1 - $price / $redemption) / $daysBetweenSettlementAndMaturity);
		}
		return self::$_errorCodes['value'];
	}	//	function DISC()


	/**
	 *	PRICEDISC
	 *
	 *	Returns the price per $100 face value of a discounted security.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	int		discount	The security's discount rate.
	 *	@param	int		redemption	The security's redemption value per $100 face value.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function PRICEDISC($settlement, $maturity, $discount, $redemption, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$discount	= (float) self::flattenSingleValue($discount);
		$redemption	= (float) self::flattenSingleValue($redemption);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if ((is_numeric($discount)) && (is_numeric($redemption)) && (is_numeric($basis))) {
			if (($discount <= 0) || ($redemption <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity, $basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}

			return $redemption * (1 - $discount * $daysBetweenSettlementAndMaturity);
		}
		return self::$_errorCodes['value'];
	}	//	function PRICEDISC()


	/**
	 *	PRICEMAT
	 *
	 *	Returns the price per $100 face value of a security that pays interest at maturity.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security's settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	mixed	issue		The security's issue date.
	 *	@param	int		rate		The security's interest rate at date of issue.
	 *	@param	int		yield		The security's annual yield.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function PRICEMAT($settlement, $maturity, $issue, $rate, $yield, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$issue		= self::flattenSingleValue($issue);
		$rate		= self::flattenSingleValue($rate);
		$yield		= self::flattenSingleValue($yield);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if (is_numeric($rate) && is_numeric($yield)) {
			if (($rate <= 0) || ($yield <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysPerYear = self::_daysPerYear(self::YEAR($settlement),$basis);
			if (!is_numeric($daysPerYear)) {
				return $daysPerYear;
			}
			$daysBetweenIssueAndSettlement = self::YEARFRAC($issue, $settlement, $basis);
			if (!is_numeric($daysBetweenIssueAndSettlement)) {
				return $daysBetweenIssueAndSettlement;
			}
			$daysBetweenIssueAndSettlement *= $daysPerYear;
			$daysBetweenIssueAndMaturity = self::YEARFRAC($issue, $maturity, $basis);
			if (!is_numeric($daysBetweenIssueAndMaturity)) {
				return $daysBetweenIssueAndMaturity;
			}
			$daysBetweenIssueAndMaturity *= $daysPerYear;
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity, $basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}
			$daysBetweenSettlementAndMaturity *= $daysPerYear;

			return ((100 + (($daysBetweenIssueAndMaturity / $daysPerYear) * $rate * 100)) /
				   (1 + (($daysBetweenSettlementAndMaturity / $daysPerYear) * $yield)) -
				   (($daysBetweenIssueAndSettlement / $daysPerYear) * $rate * 100));
		}
		return self::$_errorCodes['value'];
	}	//	function PRICEMAT()


	/**
	 *	RECEIVED
	 *
	 *	Returns the price per $100 face value of a discounted security.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	int		investment	The amount invested in the security.
	 *	@param	int		discount	The security's discount rate.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function RECEIVED($settlement, $maturity, $investment, $discount, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$investment	= (float) self::flattenSingleValue($investment);
		$discount	= (float) self::flattenSingleValue($discount);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if ((is_numeric($investment)) && (is_numeric($discount)) && (is_numeric($basis))) {
			if (($investment <= 0) || ($discount <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity, $basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}

			return $investment / ( 1 - ($discount * $daysBetweenSettlementAndMaturity));
		}
		return self::$_errorCodes['value'];
	}	//	function RECEIVED()


	/**
	 *	INTRATE
	 *
	 *	Returns the interest rate for a fully invested security.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	int		investment	The amount invested in the security.
	 *	@param	int		redemption	The amount to be received at maturity.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function INTRATE($settlement, $maturity, $investment, $redemption, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$investment	= (float) self::flattenSingleValue($investment);
		$redemption	= (float) self::flattenSingleValue($redemption);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if ((is_numeric($investment)) && (is_numeric($redemption)) && (is_numeric($basis))) {
			if (($investment <= 0) || ($redemption <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity, $basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}

			return (($redemption / $investment) - 1) / ($daysBetweenSettlementAndMaturity);
		}
		return self::$_errorCodes['value'];
	}	//	function INTRATE()


	/**
	 *	TBILLEQ
	 *
	 *	Returns the bond-equivalent yield for a Treasury bill.
	 *
	 *	@param	mixed	settlement	The Treasury bill's settlement date.
	 *								The Treasury bill's settlement date is the date after the issue date when the Treasury bill is traded to the buyer.
	 *	@param	mixed	maturity	The Treasury bill's maturity date.
	 *								The maturity date is the date when the Treasury bill expires.
	 *	@param	int		discount	The Treasury bill's discount rate.
	 *	@return	float
	 */
	public static function TBILLEQ($settlement, $maturity, $discount) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$discount	= self::flattenSingleValue($discount);

		//	Use TBILLPRICE for validation
		$testValue = self::TBILLPRICE($settlement, $maturity, $discount);
		if (is_string($testValue)) {
			return $testValue;
		}

		if (is_string($maturity = self::_getDateValue($maturity))) {
			return self::$_errorCodes['value'];
		}
		++$maturity;

		$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity);
		$daysBetweenSettlementAndMaturity *= 360;

		return (365 * $discount) / (360 - ($discount * ($daysBetweenSettlementAndMaturity)));
	}	//	function TBILLEQ()


	/**
	 *	TBILLPRICE
	 *
	 *	Returns the yield for a Treasury bill.
	 *
	 *	@param	mixed	settlement	The Treasury bill's settlement date.
	 *								The Treasury bill's settlement date is the date after the issue date when the Treasury bill is traded to the buyer.
	 *	@param	mixed	maturity	The Treasury bill's maturity date.
	 *								The maturity date is the date when the Treasury bill expires.
	 *	@param	int		discount	The Treasury bill's discount rate.
	 *	@return	float
	 */
	public static function TBILLPRICE($settlement, $maturity, $discount) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$discount	= self::flattenSingleValue($discount);

		if (is_string($maturity = self::_getDateValue($maturity))) {
			return self::$_errorCodes['value'];
		}
		++$maturity;

		//	Validate
		if (is_numeric($discount)) {
			if ($discount <= 0) {
				return self::$_errorCodes['num'];
			}

			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}
			$daysBetweenSettlementAndMaturity *= 360;
			if ($daysBetweenSettlementAndMaturity > 360) {
				return self::$_errorCodes['num'];
			}

			$price = 100 * (1 - (($discount * $daysBetweenSettlementAndMaturity) / 360));
			if ($price <= 0) {
				return self::$_errorCodes['num'];
			}
			return $price;
		}
		return self::$_errorCodes['value'];
	}	//	function TBILLPRICE()


	/**
	 *	TBILLYIELD
	 *
	 *	Returns the yield for a Treasury bill.
	 *
	 *	@param	mixed	settlement	The Treasury bill's settlement date.
	 *								The Treasury bill's settlement date is the date after the issue date when the Treasury bill is traded to the buyer.
	 *	@param	mixed	maturity	The Treasury bill's maturity date.
	 *								The maturity date is the date when the Treasury bill expires.
	 *	@param	int		price		The Treasury bill's price per $100 face value.
	 *	@return	float
	 */
	public static function TBILLYIELD($settlement, $maturity, $price) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$price		= self::flattenSingleValue($price);

		//	Validate
		if (is_numeric($price)) {
			if ($price <= 0) {
				return self::$_errorCodes['num'];
			}

			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}

			$daysBetweenSettlementAndMaturity *= 360;
//			Sometimes we need to add 1, sometimes not. I haven't yet worked out the rule which determines this.
			++$daysBetweenSettlementAndMaturity;
			if ($daysBetweenSettlementAndMaturity > 360) {
				return self::$_errorCodes['num'];
			}

			return ((100 - $price) / $price) * (360 / $daysBetweenSettlementAndMaturity);
		}
		return self::$_errorCodes['value'];
	}	//	function TBILLYIELD()


	/**
	 * SLN
	 *
	 * Returns the straight-line depreciation of an asset for one period
	 *
	 * @param	cost		Initial cost of the asset
	 * @param	salvage		Value at the end of the depreciation
	 * @param	life		Number of periods over which the asset is depreciated
	 * @return	float
	 */
	public static function SLN($cost, $salvage, $life) {
		$cost		= self::flattenSingleValue($cost);
		$salvage	= self::flattenSingleValue($salvage);
		$life		= self::flattenSingleValue($life);

		// Calculate
		if ((is_numeric($cost)) && (is_numeric($salvage)) && (is_numeric($life))) {
			if ($life < 0) {
				return self::$_errorCodes['num'];
			}
			return ($cost - $salvage) / $life;
		}
		return self::$_errorCodes['value'];
	}	//	function SLN()


	/**
	 *	YIELDMAT
	 *
	 *	Returns the annual yield of a security that pays interest at maturity.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security's settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	mixed	issue		The security's issue date.
	 *	@param	int		rate		The security's interest rate at date of issue.
	 *	@param	int		price		The security's price per $100 face value.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function YIELDMAT($settlement, $maturity, $issue, $rate, $price, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$issue		= self::flattenSingleValue($issue);
		$rate		= self::flattenSingleValue($rate);
		$price		= self::flattenSingleValue($price);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if (is_numeric($rate) && is_numeric($price)) {
			if (($rate <= 0) || ($price <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysPerYear = self::_daysPerYear(self::YEAR($settlement),$basis);
			if (!is_numeric($daysPerYear)) {
				return $daysPerYear;
			}
			$daysBetweenIssueAndSettlement = self::YEARFRAC($issue, $settlement, $basis);
			if (!is_numeric($daysBetweenIssueAndSettlement)) {
				return $daysBetweenIssueAndSettlement;
			}
			$daysBetweenIssueAndSettlement *= $daysPerYear;
			$daysBetweenIssueAndMaturity = self::YEARFRAC($issue, $maturity, $basis);
			if (!is_numeric($daysBetweenIssueAndMaturity)) {
				return $daysBetweenIssueAndMaturity;
			}
			$daysBetweenIssueAndMaturity *= $daysPerYear;
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity, $basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}
			$daysBetweenSettlementAndMaturity *= $daysPerYear;

			return ((1 + (($daysBetweenIssueAndMaturity / $daysPerYear) * $rate) - (($price / 100) + (($daysBetweenIssueAndSettlement / $daysPerYear) * $rate))) /
				   (($price / 100) + (($daysBetweenIssueAndSettlement / $daysPerYear) * $rate))) *
				   ($daysPerYear / $daysBetweenSettlementAndMaturity);
		}
		return self::$_errorCodes['value'];
	}	//	function YIELDMAT()


	/**
	 *	YIELDDISC
	 *
	 *	Returns the annual yield of a security that pays interest at maturity.
	 *
	 *	@param	mixed	settlement	The security's settlement date.
	 *								The security's settlement date is the date after the issue date when the security is traded to the buyer.
	 *	@param	mixed	maturity	The security's maturity date.
	 *								The maturity date is the date when the security expires.
	 *	@param	int		price		The security's price per $100 face value.
	 *	@param	int		redemption	The security's redemption value per $100 face value.
	 *	@param	int		basis		The type of day count to use.
	 *										0 or omitted	US (NASD) 30/360
	 *										1				Actual/actual
	 *										2				Actual/360
	 *										3				Actual/365
	 *										4				European 30/360
	 *	@return	float
	 */
	public static function YIELDDISC($settlement, $maturity, $price, $redemption, $basis=0) {
		$settlement	= self::flattenSingleValue($settlement);
		$maturity	= self::flattenSingleValue($maturity);
		$price		= self::flattenSingleValue($price);
		$redemption	= self::flattenSingleValue($redemption);
		$basis		= (int) self::flattenSingleValue($basis);

		//	Validate
		if (is_numeric($price) && is_numeric($redemption)) {
			if (($price <= 0) || ($redemption <= 0)) {
				return self::$_errorCodes['num'];
			}
			$daysPerYear = self::_daysPerYear(self::YEAR($settlement),$basis);
			if (!is_numeric($daysPerYear)) {
				return $daysPerYear;
			}
			$daysBetweenSettlementAndMaturity = self::YEARFRAC($settlement, $maturity,$basis);
			if (!is_numeric($daysBetweenSettlementAndMaturity)) {
				return $daysBetweenSettlementAndMaturity;
			}
			$daysBetweenSettlementAndMaturity *= $daysPerYear;

			return (($redemption - $price) / $price) * ($daysPerYear / $daysBetweenSettlementAndMaturity);
		}
		return self::$_errorCodes['value'];
	}	//	function YIELDDISC()


	/**
	 * CELL_ADDRESS
	 *
	 * Returns the straight-line depreciation of an asset for one period
	 *
	 * @param	row			Row number to use in the cell reference
	 * @param	column		Column number to use in the cell reference
	 * @param	relativity	Flag indicating the type of reference to return
	 * @param	sheetText	Name of worksheet to use
	 * @return	string
	 */
	public static function CELL_ADDRESS($row, $column, $relativity=1, $referenceStyle=True, $sheetText='') {
		$row		= self::flattenSingleValue($row);
		$column		= self::flattenSingleValue($column);
		$relativity	= self::flattenSingleValue($relativity);
		$sheetText	= self::flattenSingleValue($sheetText);

		if ($sheetText > '') {
			if (strpos($sheetText,' ') !== False) { $sheetText = "'".$sheetText."'"; }
			$sheetText .='!';
		}
		if ((!is_bool($referenceStyle)) || $referenceStyle) {
			$rowRelative = $columnRelative = '$';
			$column = PHPExcel_Cell::stringFromColumnIndex($column-1);
			if (($relativity == 2) || ($relativity == 4)) { $columnRelative = ''; }
			if (($relativity == 3) || ($relativity == 4)) { $rowRelative = ''; }
			return $sheetText.$columnRelative.$column.$rowRelative.$row;
		} else {
			if (($relativity == 2) || ($relativity == 4)) { $column = '['.$column.']'; }
			if (($relativity == 3) || ($relativity == 4)) { $row = '['.$row.']'; }
			return $sheetText.'R'.$row.'C'.$column;
		}
	}	//	function CELL_ADDRESS()


	public static function COLUMN($cellAddress=Null) {
		if (is_null($cellAddress) || $cellAddress === '') {
			return 0;
		}

		foreach($cellAddress as $columnKey => $value) {
			return PHPExcel_Cell::columnIndexFromString($columnKey);
		}
	}	//	function COLUMN()


	public static function ROW($cellAddress=Null) {
		if ($cellAddress === Null) {
			return 0;
		}

		foreach($cellAddress as $columnKey => $rowValue) {
			foreach($rowValue as $rowKey => $cellValue) {
				return $rowKey;
			}
		}
	}	//	function ROW()


	public static function OFFSET($cellAddress=Null,$rows=0,$columns=0,$height=null,$width=null) {
		if ($cellAddress == Null) {
			return 0;
		}

		foreach($cellAddress as $startColumnKey => $rowValue) {
			$startColumnIndex = PHPExcel_Cell::columnIndexFromString($startColumnKey);
			foreach($rowValue as $startRowKey => $cellValue) {
				break 2;
			}
		}

		foreach($cellAddress as $endColumnKey => $rowValue) {
			foreach($rowValue as $endRowKey => $cellValue) {
			}
		}
		$endColumnIndex = PHPExcel_Cell::columnIndexFromString($endColumnKey);

		$startColumnIndex += --$columns;
		$startRowKey += $rows;

		if ($width == null) {
			$endColumnIndex += $columns -1;
		} else {
			$endColumnIndex = $startColumnIndex + $width;
		}
		if ($height == null) {
			$endRowKey += $rows;
		} else {
			$endRowKey = $startRowKey + $height -1;
		}

		if (($startColumnIndex < 0) || ($startRowKey <= 0)) {
			return self::$_errorCodes['reference'];
		}

		$startColumnKey = PHPExcel_Cell::stringFromColumnIndex($startColumnIndex);
		$endColumnKey = PHPExcel_Cell::stringFromColumnIndex($endColumnIndex);

		$startCell = $startColumnKey.$startRowKey;
		$endCell = $endColumnKey.$endRowKey;

		if ($startCell == $endCell) {
			return $startColumnKey.$startRowKey;
		} else {
			return $startColumnKey.$startRowKey.':'.$endColumnKey.$endRowKey;
		}
	}	//	function OFFSET()


	public static function CHOOSE() {
		$chooseArgs = func_get_args();
		$chosenEntry = self::flattenSingleValue(array_shift($chooseArgs));
		$entryCount = count($chooseArgs) - 1;

		if ((is_numeric($chosenEntry)) && (!is_bool($chosenEntry))) {
			--$chosenEntry;
		} else {
			return self::$_errorCodes['value'];
		}
		$chosenEntry = floor($chosenEntry);
		if (($chosenEntry <= 0) || ($chosenEntry > $entryCount)) {
			return self::$_errorCodes['value'];
		}

		if (is_array($chooseArgs[$chosenEntry])) {
			return self::flattenArray($chooseArgs[$chosenEntry]);
		} else {
			return $chooseArgs[$chosenEntry];
		}
	}	//	function CHOOSE()


	/**
	 * MATCH
	 * The MATCH function searches for a specified item in a range of cells
	 * @param	lookup_value	The value that you want to match in lookup_array
	 * @param	lookup_array	The range of cells being searched
	 * @param	match_type		The number -1, 0, or 1. -1 means above, 0 means exact match, 1 means below. If match_type is 1 or -1, the list has to be ordered.
	 * @return	integer		the relative position of the found item
	 */
	public static function MATCH($lookup_value, $lookup_array, $match_type=1) {

		// flatten the lookup_array
		$lookup_array = self::flattenArray($lookup_array);

		// flatten lookup_value since it may be a cell reference to a value or the value itself
		$lookup_value = self::flattenSingleValue($lookup_value);

		// MATCH is not case sensitive
		$lookup_value = strtolower($lookup_value);

		/*
		echo "--------------------<br>looking for $lookup_value in <br>";
		print_r($lookup_array);
		echo "<br>";
		//return 1;
		/**/

		// **
		// check inputs
		// **
		// lookup_value type has to be number, text, or logical values
		if (!is_numeric($lookup_value) && !is_string($lookup_value) && !is_bool($lookup_value)){
			// error: lookup_array should contain only number, text, or logical values
			//echo "error: lookup_array should contain only number, text, or logical values<br>";
			return self::$_errorCodes['na'];
		}

		// match_type is 0, 1 or -1
		if ($match_type!==0 && $match_type!==-1 && $match_type!==1){
			// error: wrong value for match_type
			//echo "error: wrong value for match_type<br>";
			return self::$_errorCodes['na'];
		}

		// lookup_array should not be empty
		if (sizeof($lookup_array)<=0){
			// error: empty range
			//echo "error: empty range ".sizeof($lookup_array)."<br>";
			return self::$_errorCodes['na'];
		}

		// lookup_array should contain only number, text, or logical values
		for ($i=0;$i<sizeof($lookup_array);++$i){
			// check the type of the value
			if (!is_numeric($lookup_array[$i]) && !is_string($lookup_array[$i]) && !is_bool($lookup_array[$i])){
				// error: lookup_array should contain only number, text, or logical values
				//echo "error: lookup_array should contain only number, text, or logical values<br>";
				return self::$_errorCodes['na'];
			}
			// convert tpo lowercase
			if (is_string($lookup_array[$i]))
				$lookup_array[$i] = strtolower($lookup_array[$i]);
		}

		// if match_type is 1 or -1, the list has to be ordered
		if($match_type==1 || $match_type==-1){
			// **
			// iniitialization
			// store the last value
			$iLastValue=$lookup_array[0];
			// **
			// loop on the cells
			for ($i=0;$i<sizeof($lookup_array);++$i){
				// check ascending order
				if(($match_type==1 && $lookup_array[$i]<$iLastValue)
					// OR check descending order
					|| ($match_type==-1 && $lookup_array[$i]>$iLastValue)){
					// error: list is not ordered correctly
					//echo "error: list is not ordered correctly<br>";
					return self::$_errorCodes['na'];
				}
			}
		}
		// **
		// find the match
		// **
		// loop on the cells
		for ($i=0; $i < sizeof($lookup_array); ++$i){
			// if match_type is 0 <=> find the first value that is exactly equal to lookup_value
			if ($match_type==0 && $lookup_array[$i]==$lookup_value){
				// this is the exact match
				return $i+1;
			}
			// if match_type is -1 <=> find the smallest value that is greater than or equal to lookup_value
			if ($match_type==-1 && $lookup_array[$i] < $lookup_value){
				if ($i<1){
					// 1st cell was allready smaller than the lookup_value
					break;
				}
				else
					// the previous cell was the match
					return $i;
			}
			// if match_type is 1 <=> find the largest value that is less than or equal to lookup_value
			if ($match_type==1 && $lookup_array[$i] > $lookup_value){
				if ($i<1){
					// 1st cell was allready bigger than the lookup_value
					break;
				}
				else
					// the previous cell was the match
					return $i;
			}
		}
		// unsuccessful in finding a match, return #N/A error value
		//echo "unsuccessful in finding a match<br>";
		return self::$_errorCodes['na'];
	}	//	function MATCH()


	/**
	 * Uses an index to choose a value from a reference or array
	 * implemented: Return the value of a specified cell or array of cells	Array form
	 * not implemented: Return a reference to specified cells	Reference form
	 *
	 * @param	range_array	a range of cells or an array constant
	 * @param	row_num		selects the row in array from which to return a value. If row_num is omitted, column_num is required.
	 * @param	column_num	selects the column in array from which to return a value. If column_num is omitted, row_num is required.
	 */
	public static function INDEX($arrayValues,$rowNum = 0,$columnNum = 0) {

		if (($rowNum < 0) || ($columnNum < 0)) {
			return self::$_errorCodes['value'];
		}

		$rowKeys = array_keys($arrayValues);
		$columnKeys = array_keys($arrayValues[$rowKeys[0]]);
		if ($columnNum > count($columnKeys)) {
			return self::$_errorCodes['value'];
		} elseif ($columnNum == 0) {
			if ($rowNum == 0) {
				return $arrayValues;
			}
			$rowNum = $rowKeys[--$rowNum];
			$returnArray = array();
			foreach($arrayValues as $arrayColumn) {
				$returnArray[] = $arrayColumn[$rowNum];
			}
			return $returnArray;
		}
		$columnNum = $columnKeys[--$columnNum];
		if ($rowNum > count($rowKeys)) {
			return self::$_errorCodes['value'];
		} elseif ($rowNum == 0) {
			return $arrayValues[$columnNum];
		}
		$rowNum = $rowKeys[--$rowNum];

		return $arrayValues[$rowNum][$columnNum];
	}	//	function INDEX()


	/**
	 * SYD
	 *
	 * Returns the sum-of-years' digits depreciation of an asset for a specified period.
	 *
	 * @param	cost		Initial cost of the asset
	 * @param	salvage		Value at the end of the depreciation
	 * @param	life		Number of periods over which the asset is depreciated
	 * @param	period		Period
	 * @return	float
	 */
	public static function SYD($cost, $salvage, $life, $period) {
		$cost		= self::flattenSingleValue($cost);
		$salvage	= self::flattenSingleValue($salvage);
		$life		= self::flattenSingleValue($life);
		$period		= self::flattenSingleValue($period);

		// Calculate
		if ((is_numeric($cost)) && (is_numeric($salvage)) && (is_numeric($life)) && (is_numeric($period))) {
			if (($life < 1) || ($salvage < $life) || ($period > $life)) {
				return self::$_errorCodes['num'];
			}
			return (($cost - $salvage) * ($life - $period + 1) * 2) / ($life * ($life + 1));
		}
		return self::$_errorCodes['value'];
	}	//	function SYD()


	/**
	 * TRANSPOSE
	 *
	 * @param	array	$matrixData	A matrix of values
	 * @return	array
	 *
	 * Unlike the Excel TRANSPOSE function, which will only work on a single row or column, this function will transpose a full matrix.
	 */
	public static function TRANSPOSE($matrixData) {
		$returnMatrix = array();
		if (!is_array($matrixData)) { $matrixData = array(array($matrixData)); }

		$column = 0;
		foreach($matrixData as $matrixRow) {
			$row = 0;
			foreach($matrixRow as $matrixCell) {
				$returnMatrix[$column][$row] = $matrixCell;
				++$row;
			}
			++$column;
		}
		return $returnMatrix;
	}	//	function TRANSPOSE()


	/**
	 * MMULT
	 *
	 * @param	array	$matrixData1	A matrix of values
	 * @param	array	$matrixData2	A matrix of values
	 * @return	array
	 */
	public static function MMULT($matrixData1,$matrixData2) {
		$matrixAData = $matrixBData = array();
		if (!is_array($matrixData1)) { $matrixData1 = array(array($matrixData1)); }
		if (!is_array($matrixData2)) { $matrixData2 = array(array($matrixData2)); }

		$rowA = 0;
		foreach($matrixData1 as $matrixRow) {
			$columnA = 0;
			foreach($matrixRow as $matrixCell) {
				if ((is_string($matrixCell)) || ($matrixCell === null)) {
					return self::$_errorCodes['value'];
				}
				$matrixAData[$rowA][$columnA] = $matrixCell;
				++$columnA;
			}
			++$rowA;
		}
		try {
			$matrixA = new Matrix($matrixAData);
			$rowB = 0;
			foreach($matrixData2 as $matrixRow) {
				$columnB = 0;
				foreach($matrixRow as $matrixCell) {
					if ((is_string($matrixCell)) || ($matrixCell === null)) {
						return self::$_errorCodes['value'];
					}
					$matrixBData[$rowB][$columnB] = $matrixCell;
					++$columnB;
				}
				++$rowB;
			}
			$matrixB = new Matrix($matrixBData);

			if (($rowA != $columnB) || ($rowB != $columnA)) {
				return self::$_errorCodes['value'];
			}

			return $matrixA->times($matrixB)->getArray();
		} catch (Exception $ex) {
			return self::$_errorCodes['value'];
		}
	}	//	function MMULT()


	/**
	 * MINVERSE
	 *
	 * @param	array	$matrixValues	A matrix of values
	 * @return	array
	 */
	public static function MINVERSE($matrixValues) {
		$matrixData = array();
		if (!is_array($matrixValues)) { $matrixValues = array(array($matrixValues)); }

		$row = $maxColumn = 0;
		foreach($matrixValues as $matrixRow) {
			$column = 0;
			foreach($matrixRow as $matrixCell) {
				if ((is_string($matrixCell)) || ($matrixCell === null)) {
					return self::$_errorCodes['value'];
				}
				$matrixData[$column][$row] = $matrixCell;
				++$column;
			}
			if ($column > $maxColumn) { $maxColumn = $column; }
			++$row;
		}
		if ($row != $maxColumn) { return self::$_errorCodes['value']; }

		try {
			$matrix = new Matrix($matrixData);
			return $matrix->inverse()->getArray();
		} catch (Exception $ex) {
			return self::$_errorCodes['value'];
		}
	}	//	function MINVERSE()


	/**
	 * MDETERM
	 *
	 * @param	array	$matrixValues	A matrix of values
	 * @return	float
	 */
	public static function MDETERM($matrixValues) {
		$matrixData = array();
		if (!is_array($matrixValues)) { $matrixValues = array(array($matrixValues)); }

		$row = $maxColumn = 0;
		foreach($matrixValues as $matrixRow) {
			$column = 0;
			foreach($matrixRow as $matrixCell) {
				if ((is_string($matrixCell)) || ($matrixCell === null)) {
					return self::$_errorCodes['value'];
				}
				$matrixData[$column][$row] = $matrixCell;
				++$column;
			}
			if ($column > $maxColumn) { $maxColumn = $column; }
			++$row;
		}
		if ($row != $maxColumn) { return self::$_errorCodes['value']; }

		try {
			$matrix = new Matrix($matrixData);
			return $matrix->det();
		} catch (Exception $ex) {
			return self::$_errorCodes['value'];
		}
	}	//	function MDETERM()


	/**
	 * SUMX2MY2
	 *
	 * @param	mixed	$value	Value to check
	 * @return	float
	 */
	public static function SUMX2MY2($matrixData1,$matrixData2) {
		$array1 = self::flattenArray($matrixData1);
		$array2 = self::flattenArray($matrixData2);
		$count1 = count($array1);
		$count2 = count($array2);
		if ($count1 < $count2) {
			$count = $count1;
		} else {
			$count = $count2;
		}

		$result = 0;
		for ($i = 0; $i < $count; ++$i) {
			if (((is_numeric($array1[$i])) && (!is_string($array1[$i]))) &&
				((is_numeric($array2[$i])) && (!is_string($array2[$i])))) {
				$result += ($array1[$i] * $array1[$i]) - ($array2[$i] * $array2[$i]);
			}
		}

		return $result;
	}	//	function SUMX2MY2()


	/**
	 * SUMX2PY2
	 *
	 * @param	mixed	$value	Value to check
	 * @return	float
	 */
	public static function SUMX2PY2($matrixData1,$matrixData2) {
		$array1 = self::flattenArray($matrixData1);
		$array2 = self::flattenArray($matrixData2);
		$count1 = count($array1);
		$count2 = count($array2);
		if ($count1 < $count2) {
			$count = $count1;
		} else {
			$count = $count2;
		}

		$result = 0;
		for ($i = 0; $i < $count; ++$i) {
			if (((is_numeric($array1[$i])) && (!is_string($array1[$i]))) &&
				((is_numeric($array2[$i])) && (!is_string($array2[$i])))) {
				$result += ($array1[$i] * $array1[$i]) + ($array2[$i] * $array2[$i]);
			}
		}

		return $result;
	}	//	function SUMX2PY2()


	/**
	 * SUMXMY2
	 *
	 * @param	mixed	$value	Value to check
	 * @return	float
	 */
	public static function SUMXMY2($matrixData1,$matrixData2) {
		$array1 = self::flattenArray($matrixData1);
		$array2 = self::flattenArray($matrixData2);
		$count1 = count($array1);
		$count2 = count($array2);
		if ($count1 < $count2) {
			$count = $count1;
		} else {
			$count = $count2;
		}

		$result = 0;
		for ($i = 0; $i < $count; ++$i) {
			if (((is_numeric($array1[$i])) && (!is_string($array1[$i]))) &&
				((is_numeric($array2[$i])) && (!is_string($array2[$i])))) {
				$result += ($array1[$i] - $array2[$i]) * ($array1[$i] - $array2[$i]);
			}
		}

		return $result;
	}	//	function SUMXMY2()


	/**
	* VLOOKUP
	* The VLOOKUP function searches for value in the left-most column of lookup_array and returns the value in the same row based on the index_number.
	* @param	lookup_value	The value that you want to match in lookup_array
	* @param	lookup_array	The range of cells being searched
	* @param	index_number	The column number in table_array from which the matching value must be returned. The first column is 1.
	* @param	not_exact_match	Determines if you are looking for an exact match based on lookup_value.
	* @return	mixed			The value of the found cell
	*/
	public static function VLOOKUP($lookup_value, $lookup_array, $index_number, $not_exact_match=true) {
		// index_number must be greater than or equal to 1
		if ($index_number < 1) {
			return self::$_errorCodes['value'];
		}

		// index_number must be less than or equal to the number of columns in lookup_array
		if ($index_number > count($lookup_array)) {
			return self::$_errorCodes['reference'];
		}

		// re-index lookup_array with numeric keys starting at 1
		array_unshift($lookup_array, array());
		$lookup_array = array_slice(array_values($lookup_array), 1, count($lookup_array), true);

		// look for an exact match
		$row_number = array_search($lookup_value, $lookup_array[1]);

		// if an exact match is required, we have what we need to return an appropriate response
		if ($not_exact_match == false) {
			if ($row_number === false) {
				return self::$_errorCodes['na'];
			} else {
				return $lookup_array[$index_number][$row_number];
			}
		}

		// TODO: The VLOOKUP spec in Excel states that, at this point, we should search for
		// the highest value that is less than lookup_value. However, documentation on how string
		// values should be treated here is sparse.
		return self::$_errorCodes['na'];
	}	//	function VLOOKUP()

	/**
	* LOOKUP
	* The LOOKUP function searches for value either from a one-row or one-column range or from an array.
	* @param	lookup_value	The value that you want to match in lookup_array
	* @param	lookup_vector	The range of cells being searched
	* @param	result_vector	The column from which the matching value must be returned
	* @return	mixed			The value of the found cell
	*/
	public static function LOOKUP($lookup_value, $lookup_vector, $result_vector=null) {

		// check for LOOKUP Syntax (view Excel documentation)
		if( is_null($result_vector) )
		{
		// TODO: Syntax 2 (array)
		} else {
		// Syntax 1 (vector)
			// get key (column or row) of lookup_vector
			$kl = key($lookup_vector);
			// check if lookup_value exists in lookup_vector
			if( in_array($lookup_value, $lookup_vector[$kl]) )
			{
			// FOUND IT! Get key of lookup_vector
				$k_res = array_search($lookup_value, $lookup_vector[$kl]);
			} else {
			// value NOT FOUND
				// Get the smallest value in lookup_vector
				// The LOOKUP spec in Excel states --> IMPORTANT - The values in lookup_vector must be placed in ascending order!
				$ksv = key($lookup_vector[$kl]);
				$smallest_value = $lookup_vector[$kl][$ksv];
				// If lookup_value is smaller than the smallest value in lookup_vector, LOOKUP gives the #N/A error value.
				if( $lookup_value < $smallest_value )
				{
					return self::$_errorCodes['na'];
				} else {
					// If LOOKUP can't find the lookup_value, it matches the largest value in lookup_vector that is less than or equal to lookup_value.
					// IMPORTANT : In Excel Documentation is not clear what happen if lookup_value is text!
					foreach( $lookup_vector[$kl] AS $kk => $value )
					{
						if( $lookup_value >= $value )
						{
							$k_res = $kk;
						}
					}
				}
			}

			// Returns a value from the same position in result_vector
			// get key (column or row) of result_vector
			$kr = key($result_vector);
			if( isset($result_vector[$kr][$k_res]) )
			{
				return $result_vector[$kr][$k_res];
			} else {
				// TODO: In Excel Documentation is not clear what happen here...
			}
		}
 	}	//	function LOOKUP()


	/**
	 * Flatten multidemensional array
	 *
	 * @param	array	$array	Array to be flattened
	 * @return	array	Flattened array
	 */
	public static function flattenArray($array) {
		if(!is_array ( $array ) ){
			$array = array ( $array );
		}

		$arrayValues = array();

		foreach ($array as $value) {
			if (is_scalar($value)) {
				$arrayValues[] = self::flattenSingleValue($value);
			} elseif (is_array($value)) {
				$arrayValues = array_merge($arrayValues, self::flattenArray($value));
			} else {
				$arrayValues[] = $value;
			}
		}

		return $arrayValues;
	}	//	function flattenArray()


	/**
	 * Convert an array with one element to a flat value
	 *
	 * @param	mixed		$value		Array or flat value
	 * @return	mixed
	 */
	public static function flattenSingleValue($value = '') {
		if (is_array($value)) {
			$value = self::flattenSingleValue(array_pop($value));
		}
		return $value;
	}	//	function flattenSingleValue()

}	//	class PHPExcel_Calculation_Functions


//
//	There are a few mathematical functions that aren't available on all versions of PHP for all platforms
//	These functions aren't available in Windows implementations of PHP prior to version 5.3.0
//	So we test if they do exist for this version of PHP/operating platform; and if not we create them
//
if (!function_exists('acosh')) {
	function acosh($x) {
		return 2 * log(sqrt(($x + 1) / 2) + sqrt(($x - 1) / 2));
	}	//	function acosh()
}

if (!function_exists('asinh')) {
	function asinh($x) {
		return log($x + sqrt(1 + $x * $x));
	}	//	function asinh()
}

if (!function_exists('atanh')) {
	function atanh($x) {
		return (log(1 + $x) - log(1 - $x)) / 2;
	}	//	function atanh()
}

if (!function_exists('money_format')) {
	function money_format($format, $number) {
		$regex = array( '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?(?:#([0-9]+))?',
						 '(?:\.([0-9]+))?([in%])/'
					  );
		$regex = implode('', $regex);
		if (setlocale(LC_MONETARY, null) == '') {
			setlocale(LC_MONETARY, '');
		}
		$locale = localeconv();
		$number = floatval($number);
		if (!preg_match($regex, $format, $fmatch)) {
			trigger_error("No format specified or invalid format", E_USER_WARNING);
			return $number;
		}
		$flags = array( 'fillchar'	=> preg_match('/\=(.)/', $fmatch[1], $match) ? $match[1] : ' ',
						'nogroup'	=> preg_match('/\^/', $fmatch[1]) > 0,
						'usesignal'	=> preg_match('/\+|\(/', $fmatch[1], $match) ? $match[0] : '+',
						'nosimbol'	=> preg_match('/\!/', $fmatch[1]) > 0,
						'isleft'	=> preg_match('/\-/', $fmatch[1]) > 0
					  );
		$width	= trim($fmatch[2]) ? (int)$fmatch[2] : 0;
		$left	= trim($fmatch[3]) ? (int)$fmatch[3] : 0;
		$right	= trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
		$conversion = $fmatch[5];
		$positive = true;
		if ($number < 0) {
			$positive = false;
			$number *= -1;
		}
		$letter = $positive ? 'p' : 'n';
		$prefix = $suffix = $cprefix = $csuffix = $signal = '';
		if (!$positive) {
			$signal = $locale['negative_sign'];
			switch (true) {
				case $locale['n_sign_posn'] == 0 || $flags['usesignal'] == '(':
					$prefix = '(';
					$suffix = ')';
					break;
				case $locale['n_sign_posn'] == 1:
					$prefix = $signal;
					break;
				case $locale['n_sign_posn'] == 2:
					$suffix = $signal;
					break;
				case $locale['n_sign_posn'] == 3:
					$cprefix = $signal;
					break;
				case $locale['n_sign_posn'] == 4:
					$csuffix = $signal;
					break;
			}
		}
		if (!$flags['nosimbol']) {
			$currency = $cprefix;
			$currency .= ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']);
			$currency .= $csuffix;
			$currency = iconv('ISO-8859-1','UTF-8',$currency);
		} else {
			$currency = '';
		}
		$space = $locale["{$letter}_sep_by_space"] ? ' ' : '';

		$number = number_format($number, $right, $locale['mon_decimal_point'], $flags['nogroup'] ? '' : $locale['mon_thousands_sep'] );
		$number = explode($locale['mon_decimal_point'], $number);

		$n = strlen($prefix) + strlen($currency);
		if ($left > 0 && $left > $n) {
			if ($flags['isleft']) {
				$number[0] .= str_repeat($flags['fillchar'], $left - $n);
			} else {
				$number[0] = str_repeat($flags['fillchar'], $left - $n) . $number[0];
			}
		}
		$number = implode($locale['mon_decimal_point'], $number);
		if ($locale["{$letter}_cs_precedes"]) {
			$number = $prefix . $currency . $space . $number . $suffix;
		} else {
			$number = $prefix . $number . $space . $currency . $suffix;
		}
		if ($width > 0) {
			$number = str_pad($number, $width, $flags['fillchar'], $flags['isleft'] ? STR_PAD_RIGHT : STR_PAD_LEFT);
		}
		$format = str_replace($fmatch[0], $number, $format);
		return $format;
	}	//	function money_format()
}
