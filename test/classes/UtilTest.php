<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;
use const LC_ALL;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function file_exists;
use function floatval;
use function hex2bin;
use function htmlspecialchars;
use function ini_get;
use function ini_set;
use function str_repeat;
use function str_replace;
use function strlen;
use function trim;

class UtilTest extends AbstractTestCase
{
    /**
     * init data for the test
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::setTheme();
        parent::loadDefaultConfig();
    }

    /**
     * Test for createGISData
     */
    public function testCreateGISDataOldMysql(): void
    {
        $this->assertEquals(
            'abc',
            Util::createGISData('abc', 50500)
        );
        $this->assertEquals(
            "GeomFromText('POINT()',10)",
            Util::createGISData("'POINT()',10", 50500)
        );
    }

    /**
     * Test for createGISData
     */
    public function testCreateGISDataNewMysql(): void
    {
        $this->assertEquals(
            'abc',
            Util::createGISData('abc', 50600)
        );
        $this->assertEquals(
            "ST_GeomFromText('POINT()',10)",
            Util::createGISData("'POINT()',10", 50600)
        );
    }

    /**
     * Test for listPHPExtensions
     *
     * @requires extension mysqli
     * @requires extension curl
     * @requires extension mbstring
     */
    public function testListPHPExtensions(): void
    {
        $this->assertSame(
            [
                'mysqli',
                'curl',
                'mbstring',
            ],
            Util::listPHPExtensions()
        );
    }

    /**
     * Test for private getConditionValue
     */
    public function testGetConditionValue(): void
    {
        $this->assertSame(
            ['IS NULL', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    null,// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['IS NULL', 'CONCAT(`table`.`orgname`)'],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    null,// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    'CONCAT(`table`.`orgname`)',// condition
                ]
            )
        );
        $this->assertSame(
            ['= 123456', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    123456,// row
                    ((object) [
                        'numeric' => true,
                        'type' => 'int',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= 123.456', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    123.456,// row
                    ((object) [
                        'numeric' => true,
                        'type' => 'float',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= \'value\'', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'value',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= CAST(0x76616c7565 AS BINARY)', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'value',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    'BINARY',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= \'value\'', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'value',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'blob',
                        'charsetnr' => 32,// armscii8_general_ci
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= \'value\'', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'value',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'blob',
                        'charsetnr' => 48,// latin1_general_ci
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= CAST(0x76616c7565 AS BINARY)', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'value',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'blob',
                        'charsetnr' => 63,// binary
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['= CAST(0x76616c7565 AS BINARY)', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'value',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    'BINARY',// field flags
                    1,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            [' = 1001', ' CHAR_LENGTH(conditionKey) '],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    str_repeat('*', 1001),// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    'BINARY',// field flags
                    1,// fields count
                    'conditionKey',// condition key
                    'conditionInit',// condition
                ]
            )
        );
        $this->assertSame(
            [null, 'conditionInit'],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    str_repeat('*', 1001),// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'string',
                    ]),// field meta
                    'BINARY',// field flags
                    0,// fields count
                    'conditionKey',// condition key
                    'conditionInit',// condition
                ]
            )
        );
        $this->assertSame(
            ['= b\'0001\'', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    0x1,// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'bit',
                        'length' => 4,
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    'conditionKey',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['', '=0x626c6f6f6f626262 AND'],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'blooobbb',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'multipoint',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
        $this->assertSame(
            ['', '`table`.`tbl2`=0x626c6f6f6f626262 AND'],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    'blooobbb',// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'multipoint',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '`table`.`tbl2`',// condition
                ]
            )
        );
        $this->assertSame(
            ['', ''],
            $this->callFunction(
                null,
                Util::class,
                'getConditionValue',
                [
                    str_repeat('*', 5001),// row
                    ((object) [
                        'numeric' => false,
                        'type' => 'multipoint',
                    ]),// field meta
                    '',// field flags
                    0,// fields count
                    '',// condition key
                    '',// condition
                ]
            )
        );
    }

    /**
     * Test for getGISFunctions
     */
    public function testGetGISFunctions(): void
    {
        $funcs = Util::getGISFunctions();
        $this->assertArrayHasKey(
            'Dimension',
            $funcs
        );
        $this->assertArrayHasKey(
            'GeometryType',
            $funcs
        );
        $this->assertArrayHasKey(
            'MBRDisjoint',
            $funcs
        );
    }

    /**
     * Test for Page Selector
     */
    public function testPageSelector(): void
    {
        $this->assertStringContainsString(
            '<select class="pageselector ajax" name="pma" >',
            Util::pageselector('pma', 3)
        );

        // If pageNow > nbTotalPage, show the pageNow number to avoid confusion
        $this->assertStringContainsString(
            '<option selected="selected" style="font-weight: bold" value="297">100</option>',
            Util::pageselector('pma', 3, 100, 50)
        );
    }

    /**
     * Test for isForeignKeyCheck
     */
    public function testIsForeignKeyCheck(): void
    {
        $GLOBALS['server'] = 1;

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'enable';
        $this->assertTrue(
            Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'disable';
        $this->assertFalse(
            Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';
        $this->assertTrue(
            Util::isForeignKeyCheck()
        );
    }

    /**
     * Test for getCharsetQueryPart
     *
     * @param string $collation Collation
     * @param string $expected  Expected Charset Query
     *
     * @dataProvider charsetQueryData
     */
    public function testGenerateCharsetQueryPart(string $collation, string $expected): void
    {
        $this->assertEquals(
            $expected,
            Util::getCharsetQueryPart($collation)
        );
    }

    /**
     * Data Provider for testgetCharsetQueryPart
     *
     * @return array test data
     */
    public function charsetQueryData(): array
    {
        return [
            [
                'a_b_c_d',
                ' CHARSET=a COLLATE a_b_c_d',
            ],
            [
                'a_',
                ' CHARSET=a COLLATE a_',
            ],
            [
                'a',
                ' CHARSET=a',
            ],
        ];
    }

    /**
     * Test for random generation
     */
    public function testGenerateRandom(): void
    {
        $this->assertEquals(32, strlen(Util::generateRandom(32)));
        $this->assertEquals(16, strlen(Util::generateRandom(16)));
    }

    /**
     * Test clearing user cache
     *
     * @covers \PhpMyAdmin\Util::clearUserCache
     */
    public function testClearUserCache(): void
    {
        $GLOBALS['server'] = 'server';
        SessionCache::set('is_superuser', 'yes');
        $this->assertEquals(
            'yes',
            $_SESSION['cache']['server_server']['is_superuser']
        );

        Util::clearUserCache();
        $this->assertArrayNotHasKey(
            'is_superuser',
            $_SESSION['cache']['server_server']
        );
    }

    /**
     * Test for Util::checkParameters
     *
     * @covers \PhpMyAdmin\Util::checkParameters
     */
    public function testCheckParameterMissing(): void
    {
        parent::setGlobalConfig();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;

        $this->expectOutputRegex('/Missing parameter: field/');

        Util::checkParameters(
            [
                'db',
                'table',
                'field',
            ]
        );
    }

    /**
     * Test for Util::checkParameters
     *
     * @covers \PhpMyAdmin\Util::checkParameters
     */
    public function testCheckParameter(): void
    {
        parent::setGlobalConfig();
        $GLOBALS['cfg'] = ['ServerDefault' => 1];
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['db'] = 'dbDatabase';
        $GLOBALS['table'] = 'tblTable';
        $GLOBALS['field'] = 'test_field';
        $GLOBALS['sql_query'] = 'SELECT * FROM tblTable;';

        $this->expectOutputString('');
        Util::checkParameters(
            [
                'db',
                'table',
                'field',
                'sql_query',
            ]
        );
    }

    /**
     * Test for Util::convertBitDefaultValue
     *
     * @param string|null $bit Value
     * @param string      $val Expected value
     *
     * @covers \PhpMyAdmin\Util::convertBitDefaultValue
     * @dataProvider providerConvertBitDefaultValue
     */
    public function testConvertBitDefaultValue(?string $bit, string $val): void
    {
        $this->assertEquals(
            $val,
            Util::convertBitDefaultValue($bit)
        );
    }

    /**
     * Provider for testConvertBitDefaultValue
     *
     * @return array
     */
    public function providerConvertBitDefaultValue(): array
    {
        return [
            [
                null,
                '',
            ],
            [
                "b'",
                '',
            ],
            [
                "b'01'",
                '01',
            ],
            [
                "b'010111010'",
                '010111010',
            ],
            'database name starting with b' => [
                'big database',
                'big database',
            ],
            "database name containing b'" => [
                "a b'ig database",
                "a b'ig database",
            ],
            'database name in single quotes' => [
                "'a*database*name'",
                "'a*database*name'",
            ],
            "database name with multiple b'" => [
                "b'ens datab'ase'",
                "b'ens datab'ase'",
            ],
        ];
    }

    /**
     * data provider for testEscapeMysqlWildcards and testUnescapeMysqlWildcards
     *
     * @return array
     */
    public function providerUnEscapeMysqlWildcards(): array
    {
        return [
            [
                '\_test',
                '_test',
            ],
            [
                '\_\\',
                '_\\',
            ],
            [
                '\\_\%',
                '_%',
            ],
            [
                '\\\_',
                '\_',
            ],
            [
                '\\\_\\\%',
                '\_\%',
            ],
            [
                '\_\\%\_\_\%',
                '_%__%',
            ],
            [
                '\%\_',
                '%_',
            ],
            [
                '\\\%\\\_',
                '\%\_',
            ],
        ];
    }

    /**
     * PhpMyAdmin\Util::escapeMysqlWildcards tests
     *
     * @param string $a Expected value
     * @param string $b String to escape
     *
     * @covers \PhpMyAdmin\Util::escapeMysqlWildcards
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testEscapeMysqlWildcards(string $a, string $b): void
    {
        $this->assertEquals(
            $a,
            Util::escapeMysqlWildcards($b)
        );
    }

    /**
     * PhpMyAdmin\Util::unescapeMysqlWildcards tests
     *
     * @param string $a String to unescape
     * @param string $b Expected value
     *
     * @covers \PhpMyAdmin\Util::unescapeMysqlWildcards
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testUnescapeMysqlWildcards(string $a, string $b): void
    {
        $this->assertEquals(
            $b,
            Util::unescapeMysqlWildcards($a)
        );
    }

    /**
     * Test case for expanding strings
     *
     * @param string $in  string to evaluate
     * @param string $out expected output
     *
     * @covers \PhpMyAdmin\Util::expandUserString
     * @dataProvider providerExpandUserString
     */
    public function testExpandUserString(string $in, string $out): void
    {
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg'] = [
            'Server' => [
                'host' => 'host&',
                'verbose' => 'verbose',
            ],
        ];
        $GLOBALS['db'] = 'database';
        $GLOBALS['table'] = 'table';

        $out = str_replace('PMA_VERSION', PMA_VERSION, $out);

        $this->assertEquals(
            $out,
            Util::expandUserString($in)
        );

        $this->assertEquals(
            htmlspecialchars($out),
            Util::expandUserString(
                $in,
                'htmlspecialchars'
            )
        );
    }

    /**
     * Data provider for testExpandUserString
     *
     * @return array
     */
    public function providerExpandUserString(): array
    {
        return [
            [
                '@SERVER@',
                'host&',
            ],
            [
                '@VSERVER@',
                'verbose',
            ],
            [
                '@DATABASE@',
                'database',
            ],
            [
                '@TABLE@',
                'table',
            ],
            [
                '@IGNORE@',
                '@IGNORE@',
            ],
            [
                '@PHPMYADMIN@',
                'phpMyAdmin PMA_VERSION',
            ],
        ];
    }

    /**
     * Test case for parsing SHOW COLUMNS output
     *
     * @param string $in  Column specification
     * @param array  $out Expected value
     *
     * @covers \PhpMyAdmin\Util::extractColumnSpec
     * @dataProvider providerExtractColumnSpec
     */
    public function testExtractColumnSpec(string $in, array $out): void
    {
        $GLOBALS['cfg']['LimitChars'] = 1000;

        $this->assertEquals(
            $out,
            Util::extractColumnSpec($in)
        );
    }

    /**
     * Data provider for testExtractColumnSpec
     *
     * @return array
     */
    public function providerExtractColumnSpec(): array
    {
        return [
            [
                "SET('a','b')",
                [
                    'type' => 'set',
                    'print_type' => "set('a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a','b'",
                    'enum_set_values' => [
                        'a',
                        'b',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('a', 'b')",
                ],
            ],
            [
                "SET('\'a','b')",
                [
                    'type' => 'set',
                    'print_type' => "set('\'a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'\'a','b'",
                    'enum_set_values' => [
                        "'a",
                        'b',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('\'a', 'b')",
                ],
            ],
            [
                "SET('''a','b')",
                [
                    'type' => 'set',
                    'print_type' => "set('''a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'''a','b'",
                    'enum_set_values' => [
                        "'a",
                        'b',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('''a', 'b')",
                ],
            ],
            [
                "ENUM('a&b', 'b''c\\'d', 'e\\\\f')",
                [
                    'type' => 'enum',
                    'print_type' => "enum('a&b', 'b''c\\'d', 'e\\\\f')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a&b', 'b''c\\'d', 'e\\\\f'",
                    'enum_set_values' => [
                        'a&b',
                        'b\'c\'d',
                        'e\\f',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "enum('a&amp;b', 'b''c\\'d', 'e\\\\f')",
                ],
            ],
            [
                'INT UNSIGNED zerofill',
                [
                    'type' => 'int',
                    'print_type' => 'int',
                    'binary' => false,
                    'unsigned' => true,
                    'zerofill' => true,
                    'spec_in_brackets' => '',
                    'enum_set_values' => [],
                    'attribute' => 'UNSIGNED ZEROFILL',
                    'can_contain_collation' => false,
                    'displayed_type' => 'int',
                ],
            ],
            [
                'VARCHAR(255)',
                [
                    'type' => 'varchar',
                    'print_type' => 'varchar(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => [],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => 'varchar(255)',
                ],
            ],
            [
                'VARBINARY(255)',
                [
                    'type' => 'varbinary',
                    'print_type' => 'varbinary(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => [],
                    'attribute' => ' ',
                    'can_contain_collation' => false,
                    'displayed_type' => 'varbinary(255)',
                ],
            ],
        ];
    }

    /**
     * Test for Util::extractValueFromFormattedSize
     *
     * @param int|string $size     Size
     * @param int|float  $expected Expected value (float on some cpu architectures)
     *
     * @covers \PhpMyAdmin\Util::extractValueFromFormattedSize
     * @dataProvider providerExtractValueFromFormattedSize
     */
    public function testExtractValueFromFormattedSize($size, $expected): void
    {
        $this->assertEquals(
            $expected,
            Util::extractValueFromFormattedSize($size)
        );
    }

    /**
     * Data provider for testExtractValueFromFormattedSize
     *
     * @return array
     */
    public function providerExtractValueFromFormattedSize(): array
    {
        return [
            [
                100,
                -1,
            ],
            [
                '10GB',
                10737418240,
            ],
            [
                '15MB',
                15728640,
            ],
            [
                '256K',
                262144,
            ],
        ];
    }

    /**
     * foreign key supported test
     *
     * @param string $a Engine
     * @param bool   $e Expected Value
     *
     * @covers \PhpMyAdmin\Util::isForeignKeySupported
     * @dataProvider providerIsForeignKeySupported
     */
    public function testIsForeignKeySupported(string $a, bool $e): void
    {
        $GLOBALS['server'] = 1;

        $this->assertEquals(
            $e,
            Util::isForeignKeySupported($a)
        );
    }

    /**
     * data provider for foreign key supported test
     *
     * @return array
     */
    public function providerIsForeignKeySupported(): array
    {
        return [
            [
                'MyISAM',
                false,
            ],
            [
                'innodb',
                true,
            ],
            [
                'pBxT',
                true,
            ],
            [
                'ndb',
                true,
            ],
        ];
    }

    /**
     * format byte test, globals are defined
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param array            $e Expected value
     *
     * @covers \PhpMyAdmin\Util::formatByteDown
     * @dataProvider providerFormatByteDown
     */
    public function testFormatByteDown($a, int $b, int $c, array $e): void
    {
        $result = Util::formatByteDown($a, $b, $c);
        $this->assertIsArray($result);
        $result[0] = trim($result[0]);
        $this->assertSame($e, $result);
    }

    /**
     * format byte down data provider
     *
     * @return array
     */
    public function providerFormatByteDown(): array
    {
        return [
            [
                '0',
                6,
                0,
                [
                    '0',
                    __('B'),
                ],
            ],
            [
                'A4',
                6,
                0,
                [
                    '0',
                    __('B'),
                ],
            ],
            [
                10,
                2,
                2,
                [
                    '10',
                    __('B'),
                ],
            ],
            [
                100,
                2,
                0,
                [
                    '0',
                    __('KiB'),
                ],
            ],
            [
                100,
                3,
                0,
                [
                    '100',
                    __('B'),
                ],
            ],
            [
                100,
                2,
                2,
                [
                    '0.10',
                    __('KiB'),
                ],
            ],
            [
                1034,
                3,
                2,
                [
                    '1.01',
                    __('KiB'),
                ],
            ],
            [
                100233,
                3,
                3,
                [
                    '97.884',
                    __('KiB'),
                ],
            ],
            [
                '100233',
                3,
                3,
                [
                    '97.884',
                    __('KiB'),
                ],
            ],
            [
                2206451,
                1,
                2,
                [
                    '2.10',
                    __('MiB'),
                ],
            ],
            [
                21474836480,
                4,
                0,
                [
                    '20',
                    __('GiB'),
                ],
            ],
            [
                floatval(52) + floatval(2048),
                3,
                1,
                [
                    '2.1',
                    'KiB',
                ],
            ],
            [
                '' . (floatval(52) + floatval(2048)),
                3,
                1,
                [
                    '2.1',
                    'KiB',
                ],
            ],
        ];
    }

    /**
     * Core test for formatNumber
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param string           $d Expected value
     */
    private function assertFormatNumber($a, int $b, int $c, string $d): void
    {
        $this->assertEquals(
            $d,
            (string) Util::formatNumber(
                $a,
                $b,
                $c,
                false
            )
        );
    }

    /**
     * format number test, globals are defined
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param string           $d Expected value
     *
     * @covers \PhpMyAdmin\Util::formatNumber
     * @dataProvider providerFormatNumber
     */
    public function testFormatNumber($a, int $b, int $c, string $d): void
    {
        $this->assertFormatNumber($a, $b, $c, $d);

        // Test with various precisions
        $old_precision = (string) ini_get('precision');
        try {
            ini_set('precision', '20');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '14');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '10');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '5');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '-1');
            $this->assertFormatNumber($a, $b, $c, $d);
        } finally {
            ini_set('precision', $old_precision);
        }

        // Test with different translations
        $translator = Loader::getInstance()->getTranslator();

        try {
            // German
            $translator->setTranslation(',', '.');
            $translator->setTranslation('.', ',');
            $expected = str_replace([',', 'X'], ['.', ','], str_replace('.', 'X', $d));
            $this->assertFormatNumber($a, $b, $c, $expected);

            // Czech
            $translator->setTranslation(',', ' ');
            $translator->setTranslation('.', ',');
            $expected = str_replace([',', 'X'], [' ', ','], str_replace('.', 'X', $d));
            $this->assertFormatNumber($a, $b, $c, $expected);
        } finally {
            // Restore
            $translator->setTranslation(',', ',');
            $translator->setTranslation('.', '.');
        }
    }

    /**
     * format number data provider
     *
     * @return array
     */
    public function providerFormatNumber(): array
    {
        return [
            [
                10,
                2,
                2,
                '10  ',
            ],
            [
                100,
                2,
                0,
                '100  ',
            ],
            [
                100,
                2,
                2,
                '100  ',
            ],
            [
                '100',
                2,
                2,
                '100  ',
            ],
            [
                -1000.454,
                4,
                2,
                '-1,000.45  ',
            ],
            [
                '-1000.454',
                4,
                2,
                '-1,000.45  ',
            ],
            [
                0.00003,
                3,
                2,
                '30 µ',
            ],
            [
                0.003,
                3,
                3,
                '3 m',
            ],
            [
                -0.003,
                6,
                0,
                '-3,000 µ',
            ],
            [
                100.98,
                0,
                2,
                '100.98',
            ],
            [
                21010101,
                0,
                2,
                '21,010,101.00',
            ],
            [
                1100000000,
                5,
                0,
                '1,100 M',
            ],
            [
                '1100000000',
                5,
                0,
                '1,100 M',
            ],
            [
                20000,
                2,
                2,
                '20 k',
            ],
            [
                20011,
                2,
                2,
                '20.01 k',
            ],
            [
                123456789,
                6,
                0,
                '123,457 k',
            ],
            [
                -123456789,
                4,
                2,
                '-123.46 M',
            ],
            [
                0,
                6,
                0,
                '0',
            ],
        ];
    }

    /**
     * Test for Util::getFormattedMaximumUploadSize
     *
     * @param int|float $size Size (float on some cpu architectures)
     * @param string    $unit Unit
     * @param string    $res  Result
     *
     * @covers \PhpMyAdmin\Util::getFormattedMaximumUploadSize
     * @dataProvider providerGetFormattedMaximumUploadSize
     */
    public function testGetFormattedMaximumUploadSize($size, string $unit, string $res): void
    {
        $this->assertEquals(
            '(' . __('Max: ') . $res . $unit . ')',
            Util::getFormattedMaximumUploadSize($size)
        );
    }

    /**
     * Data provider for testGetFormattedMaximumUploadSize
     *
     * @return array
     */
    public function providerGetFormattedMaximumUploadSize(): array
    {
        return [
            [
                10,
                __('B'),
                '10',
            ],
            [
                100,
                __('B'),
                '100',
            ],
            [
                1024,
                __('B'),
                '1,024',
            ],
            [
                102400,
                __('KiB'),
                '100',
            ],
            [
                10240000,
                __('MiB'),
                '10',
            ],
            [
                2147483648,
                __('MiB'),
                '2,048',
            ],
            [
                21474836480,
                __('GiB'),
                '20',
            ],
        ];
    }

    /**
     * Test for Util::getTitleForTarget
     *
     * @param string $target Target
     * @param string $result Expected value
     *
     * @covers \PhpMyAdmin\Util::getTitleForTarget
     * @dataProvider providerGetTitleForTarget
     */
    public function testGetTitleForTarget(string $target, string $result): void
    {
        $this->assertEquals(
            $result,
            Util::getTitleForTarget($target)
        );
    }

    /**
     * Data provider for testGetTitleForTarget
     *
     * @return array
     */
    public function providerGetTitleForTarget(): array
    {
        return [
            [
                'structure',
                __('Structure'),
            ],
            [
                'sql',
                __('SQL'),
            ],
            [
                'search',
                __('Search'),
            ],
            [
                'insert',
                __('Insert'),
            ],
            [
                'browse',
                __('Browse'),
            ],
            [
                'operations',
                __('Operations'),
            ],
        ];
    }

    /**
     * localised date test, globals are defined
     *
     * @param int    $a      Current timestamp
     * @param string $b      Format
     * @param string $e      Expected output
     * @param string $tz     Timezone to set
     * @param string $locale Locale to set
     *
     * @covers \PhpMyAdmin\Util::localisedDate
     * @dataProvider providerLocalisedDate
     */
    public function testLocalisedDate(int $a, string $b, string $e, string $tz, string $locale): void
    {
        // A test case for #15830 could be added for using the php setlocale on a Windows CI
        // See https://github.com/phpmyadmin/phpmyadmin/issues/15830
        _setlocale(LC_ALL, $locale);
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set($tz);

        $this->assertEquals(
            $e,
            Util::localisedDate($a, $b)
        );

        date_default_timezone_set($tmpTimezone);
        _setlocale(LC_ALL, 'en');
    }

    /**
     * data provider for localised date test
     *
     * @return array
     */
    public function providerLocalisedDate(): array
    {
        $hasJaTranslations = file_exists(LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo');

        return [
            [
                1227455558,
                '',
                'Nov 23, 2008 at 03:52 PM',
                'Europe/London',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                '2008-11-23 15:52:38 Sun',
                'Europe/London',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                '2008-11-23 16:52:38 Sun',
                'Europe/Paris',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                '2008-11-24 00:52:38 Mon',
                'Asia/Tokyo',
                'en',
            ],
            [
                1227455558,
                '%a %A %b %B',
                'Mon Mon Nov Nov',
                'Asia/Tokyo',
                'en',
            ],
            [
                1227455558,
                '%a %A %b %B %P',
                'Mon Mon Nov Nov AM',
                'Asia/Tokyo',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                $hasJaTranslations ? '2008-11-24 00:52:38 月' : '2008-11-24 00:52:38 Mon',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%a %A %b %B',
                $hasJaTranslations ? '月 月 11 月 11 月' : 'Mon Mon Nov Nov',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%a %A %b %B %P',
                $hasJaTranslations ? '月 月 11 月 11 月 午前' : 'Mon Mon Nov Nov AM',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '月月',
                '月月',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%Y 年 2 月 %d 日 %H:%M',
                '2008 年 2 月 24 日 00:52',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%Y 年 2 � %d 日 %H:%M',
                '2008 年 2 � 24 日 00:52',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1617153941,
                'H:i:s Y-d-m',
                'H:i:s Y-d-m',// Not a valid strftime format
                'Europe/Paris',
                'fr',
            ],
            [
                1617153941,
                '',
                'mer. 31 mars 2021 à 03:25',// No format uses format "%B %d, %Y at %I:%M %p"
                'Europe/Paris',
                'fr',
            ],
        ];
    }

    /**
     * localised timestamp test, globals are defined
     *
     * @param int    $a Timespan in seconds
     * @param string $e Expected output
     *
     * @covers \PhpMyAdmin\Util::timespanFormat
     * @dataProvider providerTimespanFormat
     */
    public function testTimespanFormat(int $a, string $e): void
    {
        $GLOBALS['timespanfmt'] = '%s days, %s hours, %s minutes and %s seconds';
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');

        $this->assertEquals(
            $e,
            Util::timespanFormat($a)
        );

        date_default_timezone_set($tmpTimezone);
    }

    /**
     * data provider for localised timestamp test
     *
     * @return array
     */
    public function providerTimespanFormat(): array
    {
        return [
            [
                1258,
                '0 days, 0 hours, 20 minutes and 58 seconds',
            ],
            [
                821958,
                '9 days, 12 hours, 19 minutes and 18 seconds',
            ],
        ];
    }

    /**
     * test for generating string contains printable bit value of selected data
     *
     * @param int    $a Value
     * @param int    $b Length
     * @param string $e Expected output
     *
     * @covers \PhpMyAdmin\Util::printableBitValue
     * @dataProvider providerPrintableBitValue
     */
    public function testPrintableBitValue(int $a, int $b, string $e): void
    {
        $this->assertEquals(
            $e,
            Util::printableBitValue($a, $b)
        );
    }

    /**
     * data provider for printable bit value test
     *
     * @return array
     */
    public function providerPrintableBitValue(): array
    {
        return [
            [
                20131009,
                64,
                '0000000000000000000000000000000000000001001100110010110011000001',
            ],
            [
                5,
                32,
                '00000000000000000000000000000101',
            ],
        ];
    }

    /**
     * PhpMyAdmin\Util::unQuote test
     *
     * @param string $param    String
     * @param string $expected Expected output
     *
     * @covers \PhpMyAdmin\Util::unQuote
     * @dataProvider providerUnQuote
     */
    public function testUnQuote(string $param, string $expected): void
    {
        $this->assertEquals(
            $expected,
            Util::unQuote($param)
        );
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test
     *
     * @return array
     */
    public function providerUnQuote(): array
    {
        return [
            [
                '"test\'"',
                "test'",
            ],
            [
                "'test''",
                "test'",
            ],
            [
                "`test'`",
                "test'",
            ],
            [
                "'test'test",
                "'test'test",
            ],
        ];
    }

    /**
     * PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @param string $param    String
     * @param string $expected Expected output
     *
     * @covers \PhpMyAdmin\Util::unQuote
     * @dataProvider providerUnQuoteSelectedChar
     */
    public function testUnQuoteSelectedChar(string $param, string $expected): void
    {
        $this->assertEquals(
            $expected,
            Util::unQuote($param, '"')
        );
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @return array
     */
    public function providerUnQuoteSelectedChar(): array
    {
        return [
            [
                '"test\'"',
                "test'",
            ],
            [
                "'test''",
                "'test''",
            ],
            [
                "`test'`",
                "`test'`",
            ],
            [
                "'test'test",
                "'test'test",
            ],
        ];
    }

    /**
     * backquote test with different param $do_it (true, false)
     *
     * @param string|array $a String
     * @param string|array $b Expected output
     *
     * @covers \PhpMyAdmin\Util::backquote
     * @dataProvider providerBackquote
     */
    public function testBackquote($a, $b): void
    {
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($a, Util::backquote($a, false));

        // Test backquote
        $this->assertEquals($b, Util::backquote($a));
    }

    /**
     * data provider for backquote test
     *
     * @return array
     */
    public function providerBackquote(): array
    {
        return [
            [
                '0',
                '`0`',
            ],
            [
                'test',
                '`test`',
            ],
            [
                'te`st',
                '`te``st`',
            ],
            [
                [
                    'test',
                    'te`st',
                    '',
                    '*',
                ],
                [
                    '`test`',
                    '`te``st`',
                    '',
                    '*',
                ],
            ],
        ];
    }

    /**
     * backquoteCompat test with different param $compatibility (NONE, MSSQL)
     *
     * @param string|array $entry               String
     * @param string|array $expectedNoneOutput  Expected none output
     * @param string|array $expectedMssqlOutput Expected MSSQL output
     *
     * @covers \PhpMyAdmin\Util::backquoteCompat
     * @dataProvider providerBackquoteCompat
     */
    public function testBackquoteCompat($entry, $expectedNoneOutput, $expectedMssqlOutput): void
    {
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($entry, Util::backquoteCompat($entry, 'NONE', false));

        // Run tests in MSSQL compatibility mode
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($entry, Util::backquoteCompat($entry, 'MSSQL', false));

        // Test backquote
        $this->assertEquals($expectedNoneOutput, Util::backquoteCompat($entry, 'NONE'));

        // Test backquote
        $this->assertEquals($expectedMssqlOutput, Util::backquoteCompat($entry, 'MSSQL'));
    }

    /**
     * data provider for backquoteCompat test
     *
     * @return array
     */
    public function providerBackquoteCompat(): array
    {
        return [
            [
                '0',
                '`0`',
                '"0"',
            ],
            [
                'test',
                '`test`',
                '"test"',
            ],
            [
                'te`st',
                '`te``st`',
                '"te`st"',
            ],
            [
                [
                    'test',
                    'te`st',
                    '',
                    '*',
                ],
                [
                    '`test`',
                    '`te``st`',
                    '',
                    '*',
                ],
                [
                    '"test"',
                    '"te`st"',
                    '',
                    '*',
                ],
            ],
        ];
    }

    /**
     * backquoteCompat test with forbidden words
     *
     * @covers \PhpMyAdmin\Util::backquote
     */
    public function testBackquoteForbidenWords(): void
    {
        foreach (Context::$KEYWORDS as $keyword => $type) {
            if ($type & Token::FLAG_KEYWORD_RESERVED) {
                $this->assertEquals(
                    '`' . $keyword . '`',
                    Util::backquote($keyword, false)
                );
            } else {
                $this->assertEquals(
                    $keyword,
                    Util::backquote($keyword, false)
                );
            }
        }
    }

    /**
     * test of generating user dir, globals are defined
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @covers \PhpMyAdmin\Util::userDir
     * @dataProvider providerUserDir
     */
    public function testUserDir(string $a, string $e): void
    {
        $GLOBALS['cfg']['Server']['user'] = 'root';

        $this->assertEquals($e, Util::userDir($a));
    }

    /**
     * data provider for PhpMyAdmin\Util::userDir test
     *
     * @return array
     */
    public function providerUserDir(): array
    {
        return [
            [
                '/var/pma_tmp/%u/',
                '/var/pma_tmp/root/',
            ],
            [
                '/home/%u/pma',
                '/home/root/pma/',
            ],
        ];
    }

    /**
     * duplicate first newline test
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @covers \PhpMyAdmin\Util::duplicateFirstNewline
     * @dataProvider providerDuplicateFirstNewline
     */
    public function testDuplicateFirstNewline(string $a, string $e): void
    {
        $this->assertEquals(
            $e,
            Util::duplicateFirstNewline($a)
        );
    }

    /**
     * data provider for duplicate first newline test
     *
     * @return array
     */
    public function providerDuplicateFirstNewline(): array
    {
        return [
            [
                'test',
                'test',
            ],
            [
                "\r\ntest",
                "\n\r\ntest",
            ],
            [
                "\ntest",
                "\ntest",
            ],
            [
                "\n\r\ntest",
                "\n\r\ntest",
            ],
        ];
    }

    /**
     * Test for Util::unsupportedDatatypes
     *
     * @covers \PhpMyAdmin\Util::unsupportedDatatypes
     */
    public function testUnsupportedDatatypes(): void
    {
        $no_support_types = [];
        $this->assertEquals(
            $no_support_types,
            Util::unsupportedDatatypes()
        );
    }

    /**
     * Test for Util::getPageFromPosition
     *
     * @covers \PhpMyAdmin\Util::getPageFromPosition
     */
    public function testGetPageFromPosition(): void
    {
        $this->assertEquals(Util::getPageFromPosition(0, 1), 1);
        $this->assertEquals(Util::getPageFromPosition(1, 1), 2);
        $this->assertEquals(Util::getPageFromPosition(1, 2), 1);
        $this->assertEquals(Util::getPageFromPosition(1, 6), 1);
    }

    /**
     * Test for Util::isInteger
     *
     * @param bool  $expected Expected result for a given input
     * @param mixed $input    Input data to check
     *
     * @dataProvider providerIsInteger
     */
    public function testIsInteger(bool $expected, $input): void
    {
        $isInteger = Util::isInteger($input);
        $this->assertEquals($expected, $isInteger);
    }

    /**
     * Data provider for Util::isInteger test
     *
     * @return array
     */
    public function providerIsInteger(): array
    {
        return [
            [
                true,
                1000,
            ],
            [
                true,
                '1000',
            ],
            [
                false,
                1000.1,
            ],
            [
                false,
                '1000.1',
            ],
            [
                false,
                'input',
            ],
        ];
    }

    /**
     * Test for Util::getProtoFromForwardedHeader
     *
     * @param string $header The http Forwarded header
     * @param string $proto  The protocol http/https
     *
     * @dataProvider providerForwardedHeaders
     */
    public function testGetProtoFromForwardedHeader(string $header, string $proto): void
    {
        $protocolDetected = Util::getProtoFromForwardedHeader($header);
        $this->assertEquals($proto, $protocolDetected);
    }

    /**
     * Data provider for Util::getProtoFromForwardedHeader test
     *
     * @return array
     *
     * @source https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Forwarded MDN docs
     * @source https://www.nginx.com/resources/wiki/start/topics/examples/forwarded/ Nginx docs
     */
    public function providerForwardedHeaders(): array
    {
        return [
            [
                '',
                '',
            ],
            [
                '=',
                '',
            ],
            [
                'https',
                '',
            ],
            [
                'https',
                '',
            ],
            [
                '=https',
                '',
            ],
            [
                '=http',
                '',
            ],
            [
                'For="[2001:db8:cafe::17]:4711"',
                '',
            ],
            [
                'for=192.0.2.60;proto=http;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.43, for=198.51.100.17',
                '',
            ],
            [
                'for=123.34.567.89',
                '',
            ],
            [
                'for=192.0.2.43, for="[2001:db8:cafe::17]"',
                '',
            ],
            [
                'for=12.34.56.78;host=example.com;proto=https, for=23.45.67.89',
                'https',
            ],
            [
                'for=12.34.56.78, for=23.45.67.89;secret=egah2CGj55fSJFs, for=10.1.2.3',
                '',
            ],
            [
                'for=injected;by="',
                '',
            ],
            [
                'for=injected;by=", for=real',
                '',
            ],
            [
                'for=192.0.2.60;proto=http;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.60;proto=htTp;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.60;proto=HTTP;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.60;proto= http;by=203.0.113.43',
                'http',
            ],
            [
                'for=12.34.45.67;secret="special;proto=abc;test=1";proto=http,for=23.45.67.89',
                'http',
            ],
            [
                'for=12.34.45.67;secret="special;proto=abc;test=1";proto=418,for=23.45.67.89',
                '',
            ],
            /*[ // this test case is very special and would need a different implementation
                'for=12.34.45.67;secret="special;proto=http;test=1";proto=https,for=23.45.67.89',
                'https'
            ]*/
        ];
    }

    /**
     * Some data to test Util::asWKT for testAsWKT
     */
    public function dataProviderAsWKT(): array
    {
        return [
            [
                'SELECT ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)'],
                'POINT(1 1)',
                false,
                50300,
            ],
            [
                'SELECT ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\'),'
                . ' SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                [
                    'POINT(1 1)',
                    '0',
                ],
                '\'POINT(1 1)\',0',
                true,
                50300,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)'],
                'POINT(1 1)',
                false,
                50700,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\'),'
                . ' ST_SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                [
                    'POINT(1 1)',
                    '0',
                ],
                '\'POINT(1 1)\',0',
                true,
                50700,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\', \'axis-order=long-lat\'),'
                . ' ST_SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                [
                    'POINT(1 1)',
                    '0',
                ],
                '\'POINT(1 1)\',0',
                true,
                80010,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\'),'
                . ' ST_SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                [
                    'POINT(1 1)',
                    '0',
                ],
                '\'POINT(1 1)\',0',
                true,
                50700,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\', \'axis-order=long-lat\')',
                [
                    'POINT(1 1)',
                    '0',
                ],
                'POINT(1 1)',
                false,
                80010,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\')',
                [
                    'POINT(1 1)',
                    '0',
                ],
                'POINT(1 1)',
                false,
                50700,
            ],
        ];
    }

    /**
     * Test to get data asWKT
     *
     * @param string $expectedQuery  The query to expect
     * @param array  $returnData     The data to return for fetchRow
     * @param string $functionResult Result of the Util::asWKT invocation
     * @param bool   $SRIDOption     Use the SRID option or not
     * @param int    $mysqlVersion   The mysql version to return for getVersion
     *
     * @dataProvider dataProviderAsWKT
     */
    public function testAsWKT(
        string $expectedQuery,
        array $returnData,
        string $functionResult,
        bool $SRIDOption,
        int $mysqlVersion
    ): void {
        $oldDbi = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($SRIDOption ? $this->once() : $this->exactly(2))
            ->method('getVersion')
            ->will($this->returnValue($mysqlVersion));

        $dbi->expects($SRIDOption ? $this->once() : $this->exactly(2))
            ->method('tryQuery')
            ->with($expectedQuery)
            ->will($this->returnValue([]));// Omit the real object

        $dbi->expects($SRIDOption ? $this->once() : $this->exactly(2))
            ->method('fetchRow')
            ->will($this->returnValue($returnData));

        $GLOBALS['dbi'] = $dbi;

        if (! $SRIDOption) {
            // Also test default signature
            $this->assertSame($functionResult, Util::asWKT(
                (string) hex2bin('000000000101000000000000000000F03F000000000000F03F')
            ));
        }
        $this->assertSame($functionResult, Util::asWKT(
            (string) hex2bin('000000000101000000000000000000F03F000000000000F03F'),
            $SRIDOption
        ));

        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @return array[]
     */
    public function providerFkChecks(): array
    {
        return [
            [
                '',
                'OFF',
            ],
            [
                '0',
                'OFF',
            ],
            [
                '1',
                'ON',
            ],
        ];
    }

    /**
     * @dataProvider providerFkChecks
     */
    public function testHandleDisableFKCheckInit(string $fkChecksValue, string $setVariableParam): void
    {
        $oldDbi = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $_REQUEST['fk_checks'] = $fkChecksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->will($this->returnValue('ON'));

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->will($this->returnValue(true));

        $this->assertTrue(Util::handleDisableFKCheckInit());

        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @dataProvider providerFkChecks
     */
    public function testHandleDisableFKCheckInitVarFalse(string $fkChecksValue, string $setVariableParam): void
    {
        $oldDbi = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $_REQUEST['fk_checks'] = $fkChecksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->will($this->returnValue('OFF'));

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->will($this->returnValue(true));

        $this->assertFalse(Util::handleDisableFKCheckInit());

        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @return array[]
     */
    public function providerFkCheckCleanup(): array
    {
        return [
            [
                true,
                'ON',
            ],
            [
                false,
                'OFF',
            ],
        ];
    }

    /**
     * @dataProvider providerFkCheckCleanup
     */
    public function testHandleDisableFKCheckCleanup(bool $fkChecksValue, string $setVariableParam): void
    {
        $oldDbi = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->will($this->returnValue(true));

        Util::handleDisableFKCheckCleanup($fkChecksValue);

        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasPrivilegeSkipGrantTables(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['', '']));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Util::currentUserHasPrivilege('EVENT'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasUserPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with(
                'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
            )
            ->will($this->returnValue('EVENT'));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Util::currentUserHasPrivilege('EVENT'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasNotUserPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with(
                'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
            )
            ->will($this->returnValue(false));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertFalse(Util::currentUserHasPrivilege('EVENT'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasNotUserPrivilegeButDbPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->setMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(2))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                'EVENT'
            );

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Util::currentUserHasPrivilege('EVENT', 'my_data_base'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->setMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(2))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false
            );

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertFalse(Util::currentUserHasPrivilege('EVENT', 'my_data_base'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilegeButTablePrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->setMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(3))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA` AND TABLE_NAME='my_data_table'",
                ]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                'EVENT'
            );

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Util::currentUserHasPrivilege('EVENT', 'my_data_base', 'my_data_table'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilegeAndNotTablePrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->setMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(3))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA` AND TABLE_NAME='my_data_table'",
                ]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false
            );

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        $this->assertFalse(Util::currentUserHasPrivilege('EVENT', 'my_data_base', 'my_data_table'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @return array[]
     */
    public function dataProviderScriptNames(): array
    {
        // target
        // location
        // function output
        return [
            [
                'structure', // Notice the typo on db_structure.php
                'databasesss',
                './',// Fallback to a relative path, impossible to build a valid route link
            ],
            [
                'db_structures.php', // Notice the typo on databases
                'database',
                './',// Fallback to a relative path, impossible to build a valid route link
            ],
            [
                'tbl_structure.php', // Support the legacy value
                'table',
                'index.php?route=/table/structure&lang=en',
            ],
            [
                'structure',
                'table',
                'index.php?route=/table/structure&lang=en',
            ],
            [
                'tbl_sql.php', // Support the legacy value
                'table',
                'index.php?route=/table/sql&lang=en',
            ],
            [
                'sql',
                'table',
                'index.php?route=/table/sql&lang=en',
            ],
            [
                'tbl_select.php', // Support the legacy value
                'table',
                'index.php?route=/table/search&lang=en',
            ],
            [
                'search',
                'table',
                'index.php?route=/table/search&lang=en',
            ],
            [
                'tbl_change.php', // Support the legacy value
                'table',
                'index.php?route=/table/change&lang=en',
            ],
            [
                'insert',
                'table',
                'index.php?route=/table/change&lang=en',
            ],
            [
                'sql.php', // Support the legacy value
                'table',
                'index.php?route=/sql&lang=en',
            ],
            [
                'browse',
                'table',
                'index.php?route=/sql&lang=en',
            ],
            [
                'db_structure.php', // Support the legacy value
                'database',
                'index.php?route=/database/structure&lang=en',
            ],
            [
                'structure',
                'database',
                'index.php?route=/database/structure&lang=en',
            ],
            [
                'db_sql.php', // Support the legacy value
                'database',
                'index.php?route=/database/sql&lang=en',
            ],
            [
                'sql',
                'database',
                'index.php?route=/database/sql&lang=en',
            ],
            [
                'db_search.php', // Support the legacy value
                'database',
                'index.php?route=/database/search&lang=en',
            ],
            [
                'search',
                'database',
                'index.php?route=/database/search&lang=en',
            ],
            [
                'db_operations.php', // Support the legacy value
                'database',
                'index.php?route=/database/operations&lang=en',
            ],
            [
                'operations',
                'database',
                'index.php?route=/database/operations&lang=en',
            ],
            [
                'index.php', // Support the legacy value
                'server',
                'index.php?route=/&lang=en',
            ],
            [
                'welcome',
                'server',
                'index.php?route=/&lang=en',
            ],
            [
                'server_databases.php', // Support the legacy value
                'server',
                'index.php?route=/server/databases&lang=en',
            ],
            [
                'databases',
                'server',
                'index.php?route=/server/databases&lang=en',
            ],
            [
                'server_status.php', // Support the legacy value
                'server',
                'index.php?route=/server/status&lang=en',
            ],
            [
                'status',
                'server',
                'index.php?route=/server/status&lang=en',
            ],
            [
                'server_variables.php', // Support the legacy value
                'server',
                'index.php?route=/server/variables&lang=en',
            ],
            [
                'variables',
                'server',
                'index.php?route=/server/variables&lang=en',
            ],
            [
                'server_privileges.php', // Support the legacy value
                'server',
                'index.php?route=/server/privileges&lang=en',
            ],
            [
                'privileges',
                'server',
                'index.php?route=/server/privileges&lang=en',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderScriptNames
     */
    public function testGetScriptNameForOption(string $target, string $location, string $finalLink): void
    {
        $this->assertSame(
            $finalLink,
            Util::getScriptNameForOption($target, $location)
        );
    }
}
