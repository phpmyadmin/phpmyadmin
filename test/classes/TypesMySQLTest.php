<?php
/**
 * Tests for Types.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\TypesMySQL;

require_once 'test/PMATestCase.php';

/**
 * Testcase for MySQL types handling.
 *
 * @package PhpMyAdmin-test
 */
class TypesMySQLTest extends PMATestCase
{
    /**
     * @var PMA\libraries\Types
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
        $this->object = new TypesMySQL();
    }

    /**
     * Test for getTypeDescription
     *
     * @param string $type The data type to get a description.
     *
     * @return void
     *
     * @dataProvider providerForTestGetTypeDescription
     */
    public function testGetTypeDescription($type)
    {
        $this->assertNotEquals(
            '',
            $this->object->getTypeDescription($type)
        );
    }

    /**
     * Test for getTypeDescription with unknown value
     *
     * @return void
     */
    public function testGetUnknownTypeDescription()
    {
        $this->assertEquals(
            '',
            $this->object->getTypeDescription('UNKNOWN')
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
            array('TINYINT'),
            array('SMALLINT'),
            array('MEDIUMINT'),
            array('INT'),
            array('BIGINT'),
            array('DECIMAL'),
            array('FLOAT'),
            array('DOUBLE'),
            array('REAL'),
            array('BIT'),
            array('BOOLEAN'),
            array('SERIAL'),
            array('DATE'),
            array('DATETIME'),
            array('TIMESTAMP'),
            array('TIME'),
            array('YEAR'),
            array('CHAR'),
            array('VARCHAR'),
            array('TINYTEXT'),
            array('TEXT'),
            array('MEDIUMTEXT'),
            array('LONGTEXT'),
            array('BINARY'),
            array('VARBINARY'),
            array('TINYBLOB'),
            array('MEDIUMBLOB'),
            array('BLOB'),
            array('LONGBLOB'),
            array('ENUM'),
            array('SET'),
            array('GEOMETRY'),
            array('POINT'),
            array('LINESTRING'),
            array('POLYGON'),
            array('MULTIPOINT'),
            array('MULTILINESTRING'),
            array('MULTIPOLYGON'),
            array('GEOMETRYCOLLECTION'),
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
                    'AES_DECRYPT',
                    'AES_ENCRYPT',
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
                    'INET6_NTOA',
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
                    '20' => 'INET6_ATON',
                    '21' => 'INET_ATON',
                    '22' => 'LENGTH',
                    '23' => 'LN',
                    '24' => 'LOG',
                    '25' => 'LOG2',
                    '26' => 'LOG10',
                    '27' => 'MICROSECOND',
                    '28' => 'MINUTE',
                    '29' => 'MONTH',
                    '30' => 'OCT',
                    '31' => 'ORD',
                    '32' => 'PI',
                    '33' => 'QUARTER',
                    '34' => 'RADIANS',
                    '35' => 'RAND',
                    '36' => 'ROUND',
                    '37' => 'SECOND',
                    '38' => 'SIGN',
                    '39' => 'SIN',
                    '40' => 'SQRT',
                    '41' => 'TAN',
                    '42' => 'TO_DAYS',
                    '43' => 'TO_SECONDS',
                    '44' => 'TIME_TO_SEC',
                    '45' => 'UNCOMPRESSED_LENGTH',
                    '46' => 'UNIX_TIMESTAMP',
                    '47' => 'UUID_SHORT',
                    '48' => 'WEEK',
                    '49' => 'WEEKDAY',
                    '50' => 'WEEKOFYEAR',
                    '51' => 'YEARWEEK'
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
                ),
                'JSON' => array(
                    'JSON'
                )
            ),
            $this->object->getColumns()
        );
    }
}
