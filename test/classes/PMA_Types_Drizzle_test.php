<?php
/**
 * Tests for Types.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Types.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';

/**
 * Tests for Types.class.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_Types_Drizzle_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMA_Types
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_Types_Drizzle();
    }

    /**
     * Test for getTypeDescription
     *
     * @param string $type   The data type to get a description.
     * @param string $output Expected string
     *
     * @return void
     * @dataProvider providerForTestGetTypeDescription
     */
    public function testGetTypeDescription($type, $output)
    {
        $this->assertEquals(
            $output,
            $this->object->getTypeDescription($type)
        );
    }

    /**
     * Provider for testGetTypeDescription
     *
     * @return array
     */
    public function providerForTestGetTypeDescription()
    {
        return array(
            array(
                'INTEGER',
                'A 4-byte integer, range is -2,147,483,648 to 2,147,483,647'
            ),
            array(
                'BIGINT',
                'An 8-byte integer, range is -9,223,372,036,854,775,808 to 9,223,'
                . '372,036,854,775,807'
            ),
            array(
                'DECIMAL',
                'A fixed-point number (M, D) - the maximum number of digits (M) '
                . 'is 65 (default 10), the maximum number of decimals (D) is 30 '
                . '(default 0)'
            ),
            array(
                'DOUBLE',
                'A system\'s default double-precision floating-point number'
            ),
            array(
                'BOOLEAN',
                'True or false'
            ),
            array(
                'SERIAL',
                'An alias for BIGINT NOT NULL AUTO_INCREMENT UNIQUE'
            ),
            array(
                'UUID',
                'Stores a Universally Unique Identifier (UUID)'
            ),
            array(
                'DATE',
                'A date, supported range is 0001-01-01 to 9999-12-31'
            ),
            array(
                'DATETIME',
                'A date and time combination, supported range is 0001-01-01 00:00:0 '
                . 'to 9999-12-31 23:59:59'
            ),
            array(
                'TIMESTAMP',
                'A timestamp, range is \'0001-01-01 00:00:00\' UTC to \'9999-12-31 '
                . '23:59:59\' UTC; TIMESTAMP(6) can store microseconds'
            ),
            array(
                'TIME',
                'A time, range is 00:00:00 to 23:59:59'
            ),
            array(
                'VARCHAR',
                'A variable-length (0-16,383) string, the effective maximum length '
                . 'is subject to the maximum row size'
            ),
            array(
                'TEXT',
                'A TEXT column with a maximum length of 65,535 (2^16 - 1) characters'
                . ', stored with a two-byte prefix indicating the length of the valu'
                . 'e in bytes'
            ),
            array(
                'VARBINARY',
                'A variable-length (0-65,535) string, uses binary collation for all '
                . 'comparisons'
            ),
            array(
                'BLOB',
                'A BLOB column with a maximum length of 65,535 (2^16 - 1) bytes, '
                . 'stored with a two-byte prefix indicating the length of the value'
            ),
            array(
                'ENUM',
                'An enumeration, chosen from the list of defined values'
            ),
            array(
                'UNKNOWN',
                ''
            )
        );
    }

    /**
     * Test for getTypeClass
     *
     * @param string $type   Type to test
     * @param string $output Expected result
     *
     * @return void
     * @dataProvider providerFortTestGetTypeClass
     */
    public function testGetTypeClass($type, $output)
    {
        $this->assertEquals(
            $output,
            $this->object->getTypeClass($type)
        );
    }

    /**
     * Data provider
     *
     * @return array Test-data
     */
    public function providerFortTestGetTypeClass()
    {
        return array(
            array(
                'SERIAL',
                'NUMBER'
            ),
            array(
                'TIME',
                'DATE'
            ),
            array(
                'ENUM',
                'CHAR'
            ),
            array(
                'UUID',
                'UUID'
            ),
            array(
                'UNKNOWN',
                ''
            )
        );
    }

    /**
     * Test for getFunctionsClass
     *
     * @param string $class  The class to get function list.
     * @param array  $output Expected result
     *
     * @return void
     * @dataProvider providerFortTestGetFunctionsClass
     */
    public function testGetFunctionsClass($class, $output)
    {

        $this->assertEquals(
            $output,
            $this->object->getFunctionsClass($class)
        );
    }

    /**
     * Provider for testGetFunctionsClass
     *
     * @return array
     */
    public function providerFortTestGetFunctionsClass()
    {
        return array(
            array(
                'UUID',
                array(
                    'UUID'
                )
            ),
            array(
                'DATE',
                array(
                    'CURRENT_DATE',
                    'CURRENT_TIME',
                    'DATE',
                    'FROM_DAYS',
                    'FROM_UNIXTIME',
                    'LAST_DAY',
                    'NOW',
                    'SYSDATE',
                    'TIMESTAMP',
                    'UTC_DATE',
                    'UTC_TIME',
                    'UTC_TIMESTAMP',
                    'YEAR',
                )
            ),
            array(
                'NUMBER',
                array(
                    'ABS',
                    'ACOS',
                    'ASCII',
                    'ASIN',
                    'ATAN',
                    'BIT_COUNT',
                    'CEILING',
                    'CHAR_LENGTH',
                    'CONNECTION_ID',
                    'COS',
                    'COT',
                    'CRC32',
                    'DAYOFMONTH',
                    'DAYOFWEEK',
                    'DAYOFYEAR',
                    'DEGREES',
                    'EXP',
                    'FLOOR',
                    'HOUR',
                    'LENGTH',
                    'LN',
                    'LOG',
                    'LOG2',
                    'LOG10',
                    'MICROSECOND',
                    'MINUTE',
                    'MONTH',
                    'OCT',
                    'ORD',
                    'PI',
                    'QUARTER',
                    'RADIANS',
                    'RAND',
                    'ROUND',
                    'SECOND',
                    'SIGN',
                    'SIN',
                    'SQRT',
                    'TAN',
                    'TO_DAYS',
                    'TIME_TO_SEC',
                    'UNCOMPRESSED_LENGTH',
                    'UNIX_TIMESTAMP',
                    //'WEEK', // same as TIME
                    'WEEKDAY',
                    'WEEKOFYEAR',
                    'YEARWEEK',
                )
            ),
            array(
                'CHAR',
                array(
                    0 => 'BIN',
                    1 => 'CHAR',
                    2 => 'COMPRESS',
                    3 => 'CURRENT_USER',
                    4 => 'DATABASE',
                    5 => 'DAYNAME',
                    6 => 'HEX',
                    7 => 'LOAD_FILE',
                    8 => 'LOWER',
                    9 => 'LTRIM',
                    10 => 'MD5',
                    11 => 'MONTHNAME',
                    12 => 'QUOTE',
                    13 => 'REVERSE',
                    14 => 'ROT13',
                    15 => 'RTRIM',
                    16 => 'SCHEMA',
                    17 => 'SPACE',
                    18 => 'TRIM',
                    19 => 'UNCOMPRESS',
                    20 => 'UNHEX',
                    21 => 'UPPER',
                    22 => 'USER',
                    23 => 'UUID',
                    24 => 'VERSION',
                )
            ),
            array(
                'UNKNOWN',
                array()
            )
        );
    }

    /**
     * Test for getAttributes
     *
     * @return void
     */
    public function testGetAttributes()
    {
        $this->assertEquals(
            array(
                '',
                'on update CURRENT_TIMESTAMP',
            ),
            $this->object->getAttributes()
        );
    }

    /**
     * Test for getColumns
     *
     * @return void
     */
    public function testGetColumns()
    {
        $this->assertEquals(
            array(
                0 => 'INT',
                1 => 'VARCHAR',
                2 => 'TEXT',
                3 => 'DATE',
                'Numeric' => array (
                    'INTEGER',
                    'BIGINT',
                    '-',
                    'DECIMAL',
                    'DOUBLE',
                    '-',
                    'BOOLEAN',
                    'SERIAL',
                    'UUID',
                ),
                'Date and time' => array (
                    'DATE',
                    'DATETIME',
                    'TIMESTAMP',
                    'TIME',
                ),
                'String' => array (
                    'VARCHAR',
                    'TEXT',
                    '-',
                    'VARBINARY',
                    'BLOB',
                    '-',
                    'ENUM',
                ),
            ),
            $this->object->getColumns()
        );
    }
}
