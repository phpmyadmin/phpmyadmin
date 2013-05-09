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

/**
 * Testcase for MySQL types handling.
 *
 * @package PhpMyAdmin-test
 */
class PMA_Types_MySQL_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_Types_MySQL();
    }

    /**
     * Test for getTypeDescription
     *
     * @param string $type   The data type to get a description.
     * @param string $output Expected output
     *
     * @return void
     *
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
                'TINYINT',
                'A 1-byte integer, signed range is -128 to 127, unsigned range is 0 to 255'
            ),
            array(
                'SMALLINT',
                'A 2-byte integer, signed range is -32,768 to 32,767, unsigned range is 0 to 65,535'
            ),
            array(
                'MEDIUMINT',
                'A 3-byte integer, signed range is -8,388,608 to 8,388,607, unsigned range is 0 to 16,777,215'
            ),
            array(
                'INT',
                'A 4-byte integer, signed range is -2,147,483,648 to 2,147,483,647, unsigned range is 0 to 4,294,967,295.'
            ),
            array(
                'BIGINT',
                'An 8-byte integer, signed range is -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807, unsigned range is 0 to 18,446,744,073,709,551,615'
            ),
            array(
                'DECIMAL',
                'A fixed-point number (M, D) - the maximum number of digits (M) is 65 (default 10), the maximum number of decimals (D) is 30 (default 0)'
            ),
            array(
                'FLOAT',
                'A small floating-point number, allowable values are -3.402823466E+38 to -1.175494351E-38, 0, and 1.175494351E-38 to 3.402823466E+38'
            ),
            array(
                'DOUBLE',
                'A double-precision floating-point number, allowable values are -1.7976931348623157E+308 to -2.2250738585072014E-308, 0, and 2.2250738585072014E-308 to 1.7976931348623157E+308'
            ),
            array(
                'REAL',
                'Synonym for DOUBLE (exception: in REAL_AS_FLOAT SQL mode it is a synonym for FLOAT)'
            ),
            array(
                'BIT',
                'A bit-field type (M), storing M of bits per value (default is 1, maximum is 64)'
            ),
            array(
                'BOOLEAN',
                'A synonym for TINYINT(1), a value of zero is considered false, nonzero values are considered true'
            ),
            array(
                'SERIAL',
                'An alias for BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE'
            ),
            array(
                'DATE',
                'A date, supported range is 1000-01-01 to 9999-12-31'
            ),
            array(
                'DATETIME',
                'A date and time combination, supported range is 1000-01-01 00:00:00 to 9999-12-31 23:59:59'
            ),
            array(
                'TIMESTAMP',
                'A timestamp, range is 1970-01-01 00:00:01 UTC to 2038-01-09 03:14:07 UTC, stored as the number of seconds since the epoch (1970-01-01 00:00:00 UTC)'
            ),
            array(
                'TIME',
                'A time, range is -838:59:59 to 838:59:59'
            ),
            array(
                'YEAR',
                'A year in four-digit (4, default) or two-digit (2) format, the allowable values are 70 (1970) to 69 (2069) or 1901 to 2155 and 0000'
            ),
            array(
                'CHAR',
                'A fixed-length (0-255, default 1) string that is always right-padded with spaces to the specified length when stored'
            ),
            array(
                'VARCHAR',
                'A variable-length (0-65,535) string, the effective maximum length is subject to the maximum row size'
            ),
            array(
                'TINYTEXT',
                'A TEXT column with a maximum length of 255 (2^8 - 1) characters, stored with a one-byte prefix indicating the length of the value in bytes'
            ),
            array(
                'TEXT',
                'A TEXT column with a maximum length of 65,535 (2^16 - 1) characters, stored with a two-byte prefix indicating the length of the value in bytes'
            ),
            array(
                'MEDIUMTEXT',
                'A TEXT column with a maximum length of 16,777,215 (2^24 - 1) characters, stored with a three-byte prefix indicating the length of the value in bytes'
            ),
            array(
                'LONGTEXT',
                'A TEXT column with a maximum length of 4,294,967,295 or 4GiB (2^32 - 1) characters, stored with a four-byte prefix indicating the length of the value in bytes'
            ),
            array(
                'BINARY',
                'Similar to the CHAR type, but stores binary byte strings rather than non-binary character strings'
            ),
            array(
                'VARBINARY',
                'Similar to the VARCHAR type, but stores binary byte strings rather than non-binary character strings'
            ),
            array(
                'TINYBLOB',
                'A BLOB column with a maximum length of 255 (2^8 - 1) bytes, stored with a one-byte prefix indicating the length of the value'
            ),
            array(
                'MEDIUMBLOB',
                'A BLOB column with a maximum length of 16,777,215 (2^24 - 1) bytes, stored with a three-byte prefix indicating the length of the value'
            ),
            array(
                'BLOB',
                'A BLOB column with a maximum length of 65,535 (2^16 - 1) bytes, stored with a two-byte prefix indicating the length of the value'
            ),
            array(
                'LONGBLOB',
                'A BLOB column with a maximum length of 4,294,967,295 or 4GiB (2^32 - 1) bytes, stored with a four-byte prefix indicating the length of the value'
            ),
            array(
                'ENUM',
                'An enumeration, chosen from the list of up to 65,535 values or the special \'\' error value'
            ),
            array(
                'SET',
                'A single value chosen from a set of up to 64 members'
            ),
            array(
                'GEOMETRY',
                'A type that can store a geometry of any type'
            ),
            array(
                'POINT',
                'A point in 2-dimensional space'
            ),
            array(
                'LINESTRING',
                'A curve with linear interpolation between points'
            ),
            array(
                'POLYGON',
                'A polygon'
            ),
            array(
                'MULTIPOINT',
                'A collection of points'
            ),
            array(
                'MULTILINESTRING',
                'A collection of curves with linear interpolation between points'
            ),
            array(
                'MULTIPOLYGON',
                'A collection of polygons'
            ),
            array(
                'GEOMETRYCOLLECTION',
                'A collection of geometry objects of any type'
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
     * @param string $type   Type to check
     * @param string $output Expected result
     *
     * @return void
     *
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
     * Data provider for type testing
     *
     * @return array for testing type detection
     */
    public function providerFortTestGetTypeClass()
    {
        return array(
            array(
                'SERIAL',
                'NUMBER'
            ),
            array(
                'YEAR',
                'DATE'
            ),
            array(
                'GEOMETRYCOLLECTION',
                'SPATIAL'
            ),
            array(
                'SET',
                'CHAR'
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
     * @param array  $output Expected function list
     *
     * @return void
     *
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
     * Data provider for testing function lists
     *
     * @return array with test data
     */
    public function providerFortTestGetFunctionsClass()
    {
        return array(
            array(
                'CHAR',
                array(
                    'BIN',
                    'CHAR',
                    'COMPRESS',
                    'CURRENT_USER',
                    'DATABASE',
                    'DAYNAME',
                    'DES_DECRYPT',
                    'DES_ENCRYPT',
                    'ENCRYPT',
                    'HEX',
                    'INET_NTOA',
                    'LOAD_FILE',
                    'LOWER',
                    'LTRIM',
                    'MD5',
                    'MONTHNAME',
                    'OLD_PASSWORD',
                    'PASSWORD',
                    'QUOTE',
                    'REVERSE',
                    'RTRIM',
                    'SHA1',
                    'SOUNDEX',
                    'SPACE',
                    'TRIM',
                    'UNCOMPRESS',
                    'UNHEX',
                    'UPPER',
                    'USER',
                    'UUID',
                    'VERSION',
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
                    'SEC_TO_TIME',
                    'SYSDATE',
                    'TIME',
                    'TIMESTAMP',
                    'UTC_DATE',
                    'UTC_TIME',
                    'UTC_TIMESTAMP',
                    'YEAR',
                )
            ),
            array(
                'SPATIAL',
                array(
                    'GeomFromText',
                    'GeomFromWKB',

                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',

                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                )
            ),
            array(
                'NUMBER',
                array(
                    '0' => 'ABS',
                    '1' => 'ACOS',
                    '2' => 'ASCII',
                    '3' => 'ASIN',
                    '4' => 'ATAN',
                    '5' => 'BIT_LENGTH',
                    '6' => 'BIT_COUNT',
                    '7' => 'CEILING',
                    '8' => 'CHAR_LENGTH',
                    '9' => 'CONNECTION_ID',
                    '10' => 'COS',
                    '11' => 'COT',
                    '12' => 'CRC32',
                    '13' => 'DAYOFMONTH',
                    '14' => 'DAYOFWEEK',
                    '15' => 'DAYOFYEAR',
                    '16' => 'DEGREES',
                    '17' => 'EXP',
                    '18' => 'FLOOR',
                    '19' => 'HOUR',
                    '20' => 'INET_ATON',
                    '21' => 'LENGTH',
                    '22' => 'LN',
                    '23' => 'LOG',
                    '24' => 'LOG2',
                    '25' => 'LOG10',
                    '26' => 'MICROSECOND',
                    '27' => 'MINUTE',
                    '28' => 'MONTH',
                    '29' => 'OCT',
                    '30' => 'ORD',
                    '31' => 'PI',
                    '32' => 'QUARTER',
                    '33' => 'RADIANS',
                    '34' => 'RAND',
                    '35' => 'ROUND',
                    '36' => 'SECOND',
                    '37' => 'SIGN',
                    '38' => 'SIN',
                    '39' => 'SQRT',
                    '40' => 'TAN',
                    '41' => 'TO_DAYS',
                    '42' => 'TO_SECONDS',
                    '43' => 'TIME_TO_SEC',
                    '44' => 'UNCOMPRESSED_LENGTH',
                    '45' => 'UNIX_TIMESTAMP',
                    '46' => 'UUID_SHORT',
                    '47' => 'WEEK',
                    '48' => 'WEEKDAY',
                    '49' => 'WEEKOFYEAR',
                    '50' => 'YEARWEEK'
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
                'BINARY',
                'UNSIGNED',
                'UNSIGNED ZEROFILL',
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
                    'TINYINT',
                    'SMALLINT',
                    'MEDIUMINT',
                    'INT',
                    'BIGINT',
                    '-',
                    'DECIMAL',
                    'FLOAT',
                    'DOUBLE',
                    'REAL',
                    '-',
                    'BIT',
                    'BOOLEAN',
                    'SERIAL',
                ),
                'Date and time' => array (
                    'DATE',
                    'DATETIME',
                    'TIMESTAMP',
                    'TIME',
                    'YEAR',
                ),
                'String' => array (
                    'CHAR',
                    'VARCHAR',
                    '-',
                    'TINYTEXT',
                    'TEXT',
                    'MEDIUMTEXT',
                    'LONGTEXT',
                    '-',
                    'BINARY',
                    'VARBINARY',
                    '-',
                    'TINYBLOB',
                    'MEDIUMBLOB',
                    'BLOB',
                    'LONGBLOB',
                    '-',
                    'ENUM',
                    'SET',
                ),
                'Spatial' => array (
                    'GEOMETRY',
                    'POINT',
                    'LINESTRING',
                    'POLYGON',
                    'MULTIPOINT',
                    'MULTILINESTRING',
                    'MULTIPOLYGON',
                    'GEOMETRYCOLLECTION',
                )
            ),
            $this->object->getColumns()
        );
    }
}
