<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Types;

/** @covers \PhpMyAdmin\Types */
class TypesTest extends AbstractTestCase
{
    protected Types $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
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
            ['IS NULL', 'IS NOT NULL', "= ''", "!= ''"],
            $this->object->getUnaryOperators(),
        );
    }

    /**
     * Test for getNullOperators
     */
    public function testGetNullOperators(): void
    {
        $this->assertEquals(
            ['IS NULL', 'IS NOT NULL'],
            $this->object->getNullOperators(),
        );
    }

    /**
     * Test for getEnumOperators
     */
    public function testGetEnumOperators(): void
    {
        $this->assertEquals(
            ['=', '!='],
            $this->object->getEnumOperators(),
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
                'NOT LIKE %...%',
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
            $this->object->getTextOperators(),
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
                'NOT LIKE %...%',
                'IN (...)',
                'NOT IN (...)',
                'BETWEEN',
                'NOT BETWEEN',
            ],
            $this->object->getNumberOperators(),
        );
    }

    /**
     * Test for getUUIDOperators
     */
    public function testGetUUIDOperators(): void
    {
        $this->assertEquals(
            ['=', '!=', 'LIKE', 'LIKE %...%', 'NOT LIKE', 'NOT LIKE %...%', 'IN (...)', 'NOT IN (...)'],
            $this->object->getUUIDOperators(),
        );
    }

    /**
     * Test for getting type operators
     *
     * @param string          $type   Type of field
     * @param bool            $null   Whether field can be NULL
     * @param string|string[] $output Expected output
     *
     * @dataProvider providerForGetTypeOperators
     */
    public function testGetTypeOperators(string $type, bool $null, string|array $output): void
    {
        $this->assertEquals(
            $output,
            $this->object->getTypeOperators($type, $null),
        );
    }

    /**
     * data provider for testGetTypeOperators
     *
     * @return array<array{string, bool, string|string[]}>
     */
    public static function providerForGetTypeOperators(): array
    {
        return [
            ['enum', false, ['=', '!=']],
            [
                'CHAR',
                true,
                [
                    'LIKE',
                    'LIKE %...%',
                    'NOT LIKE',
                    'NOT LIKE %...%',
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
            ],
            [
                'int',
                false,
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
                    'NOT LIKE %...%',
                    'IN (...)',
                    'NOT IN (...)',
                    'BETWEEN',
                    'NOT BETWEEN',
                ],
            ],
            [
                'UUID',
                false,
                ['=', '!=', 'LIKE', 'LIKE %...%', 'NOT LIKE', 'NOT LIKE %...%', 'IN (...)', 'NOT IN (...)'],
            ],
            [
                'UUID',
                true,
                [
                    '=',
                    '!=',
                    'LIKE',
                    'LIKE %...%',
                    'NOT LIKE',
                    'NOT LIKE %...%',
                    'IN (...)',
                    'NOT IN (...)',
                    'IS NULL',
                    'IS NOT NULL',
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
        string $output,
    ): void {
        $this->assertEquals(
            $output,
            $this->object->getTypeOperatorsHtml($type, $null, $selectedOperator),
        );
    }

    /**
     * Provider for testGetTypeOperatorsHtml
     *
     * @return array<array{string, bool, string, string}>
     */
    public static function providerForTestGetTypeOperatorsHtml(): array
    {
        return [
            ['enum', false, '=', '<option value="=" selected="selected">=</option><option value="!=">!=</option>'],
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
            $this->object->getTypeDescription($type),
        );
    }

    /**
     * Test for getTypeDescription with unknown value
     */
    public function testGetUnknownTypeDescription(): void
    {
        $this->assertEquals(
            '',
            $this->object->getTypeDescription('UNKNOWN'),
        );
    }

    /**
     * Provider for testGetTypeDescription
     *
     * @return array<array{string}>
     */
    public static function providerForTestGetTypeDescription(): array
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
            ['JSON'],
            ['INET6'],
            ['UUID'],
        ];
    }

    /**
     * @param string   $class  The class to get function list.
     * @param string[] $output Expected function list
     *
     * @dataProvider providerFortTestGetFunctionsClass
     */
    public function testGetFunctionsClass(string $class, array $output): void
    {
        $this->assertEquals(
            $output,
            $this->object->getFunctionsClass($class),
        );
    }

    /** @return array<array{string, string[]}> */
    public static function providerFortTestGetFunctionsClass(): array
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
                    'SHA2',
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
                    'ABS',
                    'ACOS',
                    'ASCII',
                    'ASIN',
                    'ATAN',
                    'BIT_LENGTH',
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
                    'INET6_ATON',
                    'INET_ATON',
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
                    'TO_SECONDS',
                    'TIME_TO_SEC',
                    'UNCOMPRESSED_LENGTH',
                    'UNIX_TIMESTAMP',
                    'UUID_SHORT',
                    'WEEK',
                    'WEEKDAY',
                    'WEEKOFYEAR',
                    'YEARWEEK',
                ],
            ],
            ['UNKNOWN', []],
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
                'SHA2',
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
            $this->object->getFunctions('enum'),
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
                'SHA2',
                'SIGN',
                'SIN',
                'SOUNDEX',
                'SPACE',
                'SQRT',
                'ST_GeomCollFromText',
                'ST_GeomCollFromWKB',
                'ST_GeomFromText',
                'ST_GeomFromWKB',
                'ST_LineFromText',
                'ST_LineFromWKB',
                'ST_MLineFromText',
                'ST_MLineFromWKB',
                'ST_MPointFromText',
                'ST_MPointFromWKB',
                'ST_MPolyFromText',
                'ST_MPolyFromWKB',
                'ST_PointFromText',
                'ST_PointFromWKB',
                'ST_PolyFromText',
                'ST_PolyFromWKB',
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
            $this->object->getAllFunctions(),
        );
    }

    /**
     * Test for getAttributes
     */
    public function testGetAttributes(): void
    {
        $this->assertEquals(
            ['', 'BINARY', 'UNSIGNED', 'UNSIGNED ZEROFILL', 'on update CURRENT_TIMESTAMP'],
            $this->object->getAttributes(),
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
                'Numeric' => [
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
                'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                'String' => [
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
                'Spatial' => [
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
            $this->object->getColumns(),
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
            $this->object->getTypeClass($type),
        );
    }

    /**
     * Data provider for type testing
     *
     * @return array<array{string, string}>
     */
    public static function providerFortTestGetTypeClass(): array
    {
        return [
            ['SERIAL', 'NUMBER'],
            ['YEAR', 'DATE'],
            ['GEOMETRYCOLLECTION', 'SPATIAL'],
            ['SET', 'CHAR'],
            ['JSON', 'JSON'],
            ['UUID', 'UUID'],
            ['UNKNOWN', ''],
        ];
    }
}
