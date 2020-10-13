<?php
/**
 * Tests for Types.php
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Types;

/**
 * Testcase for MySQL types handling.
 */
class TypesTest extends AbstractTestCase
{
    /** @var Types */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new Types($GLOBALS['dbi']);
    }

    /**
     * Test for isUnaryOperator
     */
    public function testUnary(): void
    {
        $this->assertTrue($this->object->isUnaryOperator('IS NULL'));
        $this->assertFalse($this->object->isUnaryOperator('='));
    }

    /**
     * Test for getUnaryOperators
     */
    public function testGetUnaryOperators(): void
    {
        $this->assertEquals(
            [
                'IS NULL',
                'IS NOT NULL',
                "= ''",
                "!= ''",
            ],
            $this->object->getUnaryOperators()
        );
    }

    /**
     * Test for getNullOperators
     */
    public function testGetNullOperators(): void
    {
        $this->assertEquals(
            [
                'IS NULL',
                'IS NOT NULL',
            ],
            $this->object->getNullOperators()
        );
    }

    /**
     * Test for getEnumOperators
     */
    public function testGetEnumOperators(): void
    {
        $this->assertEquals(
            [
                '=',
                '!=',
            ],
            $this->object->getEnumOperators()
        );
    }

    /**
     * Test for getTextOperators
     */
    public function testgetTextOperators(): void
    {
        $this->assertEquals(
            [
                'LIKE',
                'LIKE %...%',
                'NOT LIKE',
                '=',
                '!=',
                'REGEXP',
                'REGEXP ^...$',
                'NOT REGEXP',
                "= ''",
                "!= ''",
                'IN (...)',
                'NOT IN (...)',
                'BETWEEN',
                'NOT BETWEEN',
            ],
            $this->object->getTextOperators()
        );
    }

    /**
     * Test for getNumberOperators
     */
    public function testGetNumberOperators(): void
    {
        $this->assertEquals(
            [
                '=',
                '>',
                '>=',
                '<',
                '<=',
                '!=',
                'LIKE',
                'LIKE %...%',
                'NOT LIKE',
                'IN (...)',
                'NOT IN (...)',
                'BETWEEN',
                'NOT BETWEEN',
            ],
            $this->object->getNumberOperators()
        );
    }

    /**
     * Test for getting type operators
     *
     * @param string       $type   Type of field
     * @param bool         $null   Whether field can be NULL
     * @param string|array $output Expected output
     *
     * @dataProvider providerForGetTypeOperators
     */
    public function testGetTypeOperators(string $type, bool $null, $output): void
    {
        $this->assertEquals(
            $output,
            $this->object->getTypeOperators($type, $null)
        );
    }

    /**
     * data provider for testGetTypeOperators
     *
     * @return array data for testGetTypeOperators
     */
    public function providerForGetTypeOperators(): array
    {
        return [
            [
                'enum',
                false,
                [
                    '=',
                    '!=',
                ],
            ],
            [
                'CHAR',
                true,
                [
                    'LIKE',
                    'LIKE %...%',
                    'NOT LIKE',
                    '=',
                    '!=',
                    'REGEXP',
                    'REGEXP ^...$',
                    'NOT REGEXP',
                    '= \'\'',
                    '!= \'\'',
                    'IN (...)',
                    'NOT IN (...)',
                    'BETWEEN',
                    'NOT BETWEEN',
                    'IS NULL',
                    'IS NOT NULL',
                ],
                [
                    'int',
                    false,
                    [
                        '=',
                        '!=',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for getTypeOperatorsHtml
     *
     * @param string $type             Type of field
     * @param bool   $null             Whether field can be NULL
     * @param string $selectedOperator Option to be selected
     * @param string $output           Expected output
     *
     * @dataProvider providerForTestGetTypeOperatorsHtml
     */
    public function testGetTypeOperatorsHtml(
        string $type,
        bool $null,
        string $selectedOperator,
        string $output
    ): void {
        $this->assertEquals(
            $output,
            $this->object->getTypeOperatorsHtml($type, $null, $selectedOperator)
        );
    }

    /**
     * Provider for testGetTypeOperatorsHtml
     *
     * @return array test data for getTypeOperatorsHtml
     */
    public function providerForTestGetTypeOperatorsHtml(): array
    {
        return [
            [
                'enum',
                false,
                '=',
                '<option value="=" selected="selected">=</option>'
                . '<option value="!=">!=</option>',
            ],
        ];
    }

    /**
     * Test for getTypeDescription
     *
     * @param string $type The data type to get a description.
     *
     * @dataProvider providerForTestGetTypeDescription
     */
    public function testGetTypeDescription(string $type): void
    {
        $this->assertNotEquals(
            '',
            $this->object->getTypeDescription($type)
        );
    }

    /**
     * Test for getTypeDescription with unknown value
     */
    public function testGetUnknownTypeDescription(): void
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
    public function providerForTestGetTypeDescription(): array
    {
        return [
            ['TINYINT'],
            ['SMALLINT'],
            ['MEDIUMINT'],
            ['INT'],
            ['BIGINT'],
            ['DECIMAL'],
            ['FLOAT'],
            ['DOUBLE'],
            ['REAL'],
            ['BIT'],
            ['BOOLEAN'],
            ['SERIAL'],
            ['DATE'],
            ['DATETIME'],
            ['TIMESTAMP'],
            ['TIME'],
            ['YEAR'],
            ['CHAR'],
            ['VARCHAR'],
            ['TINYTEXT'],
            ['TEXT'],
            ['MEDIUMTEXT'],
            ['LONGTEXT'],
            ['BINARY'],
            ['VARBINARY'],
            ['TINYBLOB'],
            ['MEDIUMBLOB'],
            ['BLOB'],
            ['LONGBLOB'],
            ['ENUM'],
            ['SET'],
            ['GEOMETRY'],
            ['POINT'],
            ['LINESTRING'],
            ['POLYGON'],
            ['MULTIPOINT'],
            ['MULTILINESTRING'],
            ['MULTIPOLYGON'],
            ['GEOMETRYCOLLECTION'],
        ];
    }

    /**
     * @param string $class  The class to get function list.
     * @param array  $output Expected function list
     *
     * @dataProvider providerFortTestGetFunctionsClass
     */
    public function testGetFunctionsClass(string $class, array $output): void
    {
        $this->assertEquals(
            $output,
            $this->object->getFunctionsClass($class)
        );
    }

    /**
     * Data provider for testing function lists
     */
    public function providerFortTestGetFunctionsClass(): array
    {
        return [
            [
                'CHAR',
                [
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
                ],
            ],
            [
                'DATE',
                [
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
                ],
            ],
            [
                'SPATIAL',
                [
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',

                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',

                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                ],
            ],
            [
                'NUMBER',
                [
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
                    '51' => 'YEARWEEK',
                ],
            ],
            [
                'UNKNOWN',
                [],
            ],
        ];
    }

    /**
     * Test for getFunctions
     */
    public function testGetFunctions(): void
    {
        $this->assertEquals(
            [
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
            ],
            $this->object->getFunctions('enum')
        );
    }

    /**
     * Test for getAllFunctions
     */
    public function testGetAllFunctions(): void
    {
        $this->assertEquals(
            [
                'ABS',
                'ACOS',
                'AES_DECRYPT',
                'AES_ENCRYPT',
                'ASCII',
                'ASIN',
                'ATAN',
                'BIN',
                'BIT_COUNT',
                'BIT_LENGTH',
                'CEILING',
                'CHAR',
                'CHAR_LENGTH',
                'COMPRESS',
                'CONNECTION_ID',
                'COS',
                'COT',
                'CRC32',
                'CURRENT_DATE',
                'CURRENT_TIME',
                'CURRENT_USER',
                'DATABASE',
                'DATE',
                'DAYNAME',
                'DAYOFMONTH',
                'DAYOFWEEK',
                'DAYOFYEAR',
                'DEGREES',
                'DES_DECRYPT',
                'DES_ENCRYPT',
                'ENCRYPT',
                'EXP',
                'FLOOR',
                'FROM_DAYS',
                'FROM_UNIXTIME',
                'HEX',
                'HOUR',
                'INET6_ATON',
                'INET6_NTOA',
                'INET_ATON',
                'INET_NTOA',
                'LAST_DAY',
                'LENGTH',
                'LN',
                'LOAD_FILE',
                'LOG',
                'LOG10',
                'LOG2',
                'LOWER',
                'LTRIM',
                'MD5',
                'MICROSECOND',
                'MINUTE',
                'MONTH',
                'MONTHNAME',
                'NOW',
                'OCT',
                'OLD_PASSWORD',
                'ORD',
                'PASSWORD',
                'PI',
                'QUARTER',
                'QUOTE',
                'RADIANS',
                'RAND',
                'REVERSE',
                'ROUND',
                'RTRIM',
                'SECOND',
                'SEC_TO_TIME',
                'SHA1',
                'SIGN',
                'SIN',
                'SOUNDEX',
                'SPACE',
                'SQRT',
                'SYSDATE',
                'TAN',
                'TIME',
                'TIMESTAMP',
                'TIME_TO_SEC',
                'TO_DAYS',
                'TO_SECONDS',
                'TRIM',
                'UNCOMPRESS',
                'UNCOMPRESSED_LENGTH',
                'UNHEX',
                'UNIX_TIMESTAMP',
                'UPPER',
                'USER',
                'UTC_DATE',
                'UTC_TIME',
                'UTC_TIMESTAMP',
                'UUID',
                'UUID_SHORT',
                'VERSION',
                'WEEK',
                'WEEKDAY',
                'WEEKOFYEAR',
                'YEAR',
                'YEARWEEK',
            ],
            $this->object->getAllFunctions()
        );
    }

    /**
     * Test for getAttributes
     */
    public function testGetAttributes(): void
    {
        $this->assertEquals(
            [
                '',
                'BINARY',
                'UNSIGNED',
                'UNSIGNED ZEROFILL',
                'on update CURRENT_TIMESTAMP',
            ],
            $this->object->getAttributes()
        );
    }

    /**
     * Test for getColumns
     */
    public function testGetColumns(): void
    {
        $this->assertEquals(
            [
                0 => 'INT',
                1 => 'VARCHAR',
                2 => 'TEXT',
                3 => 'DATE',
                'Numeric' =>  [
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
                ],
                'Date and time' =>  [
                    'DATE',
                    'DATETIME',
                    'TIMESTAMP',
                    'TIME',
                    'YEAR',
                ],
                'String' =>  [
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
                    'BLOB',
                    'MEDIUMBLOB',
                    'LONGBLOB',
                    '-',
                    'ENUM',
                    'SET',
                ],
                'Spatial' =>  [
                    'GEOMETRY',
                    'POINT',
                    'LINESTRING',
                    'POLYGON',
                    'MULTIPOINT',
                    'MULTILINESTRING',
                    'MULTIPOLYGON',
                    'GEOMETRYCOLLECTION',
                ],
                'JSON' => ['JSON'],
            ],
            $this->object->getColumns()
        );
    }

    /**
     * @param string $type   Type to check
     * @param string $output Expected result
     *
     * @dataProvider providerFortTestGetTypeClass
     */
    public function testGetTypeClass(string $type, string $output): void
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
    public function providerFortTestGetTypeClass(): array
    {
        return [
            [
                'SERIAL',
                'NUMBER',
            ],
            [
                'YEAR',
                'DATE',
            ],
            [
                'GEOMETRYCOLLECTION',
                'SPATIAL',
            ],
            [
                'SET',
                'CHAR',
            ],
            [
                'UNKNOWN',
                '',
            ],
        ];
    }
}
