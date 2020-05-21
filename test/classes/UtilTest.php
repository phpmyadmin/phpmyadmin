<?php
/**
 * Test for PhpMyAdmin\Util class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Util;
use const LC_ALL;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function file_exists;
use function floatval;
use function htmlspecialchars;
use function ini_get;
use function ini_set;
use function str_replace;
use function strlen;
use function trim;

/**
 * Test for PhpMyAdmin\Util class
 */
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
     *
     * @return void
     */
    public function testPageSelector()
    {
        $this->assertStringContainsString(
            '<select class="pageselector ajax" name="pma" >',
            Util::pageselector('pma', 3)
        );
    }

    /**
     * Test for isForeignKeyCheck
     *
     * @return void
     */
    public function testIsForeignKeyCheck()
    {
        $GLOBALS['server'] = 1;

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'enable';
        $this->assertEquals(
            true,
            Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'disable';
        $this->assertEquals(
            false,
            Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';
        $this->assertEquals(
            true,
            Util::isForeignKeyCheck()
        );
    }

    /**
     * Test for getCharsetQueryPart
     *
     * @param string $collation Collation
     * @param string $expected  Expected Charset Query
     *
     * @test
     * @dataProvider charsetQueryData
     */
    public function testGenerateCharsetQueryPart($collation, $expected): void
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
    public function charsetQueryData()
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
     *
     * @return void
     */
    public function testGenerateRandom()
    {
        $this->assertEquals(32, strlen(Util::generateRandom(32)));
        $this->assertEquals(16, strlen(Util::generateRandom(16)));
    }

    /**
     * Test if cached data is available after set
     *
     * @return void
     *
     * @covers \PhpMyAdmin\Util::cacheExists
     */
    public function testCacheExists()
    {
        $GLOBALS['server'] = 'server';
        Util::cacheSet('test_data', 5);
        Util::cacheSet('test_data_2', 5);

        $this->assertTrue(Util::cacheExists('test_data'));
        $this->assertTrue(Util::cacheExists('test_data_2'));
        $this->assertFalse(Util::cacheExists('fake_data_2'));
    }

    /**
     * Test if PhpMyAdmin\Util::cacheGet does not return data for non existing cache entries
     *
     * @return void
     *
     * @covers \PhpMyAdmin\Util::cacheGet
     */
    public function testCacheGet()
    {
        $GLOBALS['server'] = 'server';
        Util::cacheSet('test_data', 5);
        Util::cacheSet('test_data_2', 5);

        $this->assertNotNull(Util::cacheGet('test_data'));
        $this->assertNotNull(Util::cacheGet('test_data_2'));
        $this->assertNull(Util::cacheGet('fake_data_2'));
    }

    /**
     * Test retrieval of cached data
     *
     * @return void
     *
     * @covers \PhpMyAdmin\Util::cacheSet
     */
    public function testCacheSetGet()
    {
        $GLOBALS['server'] = 'server';
        Util::cacheSet('test_data', 25);

        Util::cacheSet('test_data', 5);
        $this->assertEquals(5, $_SESSION['cache']['server_server']['test_data']);
        Util::cacheSet('test_data_3', 3);
        $this->assertEquals(3, $_SESSION['cache']['server_server']['test_data_3']);
    }

    /**
     * Test clearing cached values
     *
     * @return void
     *
     * @covers \PhpMyAdmin\Util::cacheUnset
     */
    public function testCacheUnSet()
    {
        $GLOBALS['server'] = 'server';
        Util::cacheSet('test_data', 25);
        Util::cacheSet('test_data_2', 25);

        Util::cacheUnset('test_data');
        $this->assertArrayNotHasKey(
            'test_data',
            $_SESSION['cache']['server_server']
        );
        Util::cacheUnset('test_data_2');
        $this->assertArrayNotHasKey(
            'test_data_2',
            $_SESSION['cache']['server_server']
        );
    }

    /**
     * Test clearing user cache
     *
     * @return void
     *
     * @covers \PhpMyAdmin\Util::clearUserCache
     */
    public function testClearUserCache()
    {
        $GLOBALS['server'] = 'server';
        Util::cacheSet('is_superuser', 'yes');
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
     * @return void
     *
     * @covers \PhpMyAdmin\Util::checkParameters
     */
    public function testCheckParameterMissing()
    {
        parent::setGlobalConfig();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
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
     * @return void
     *
     * @covers \PhpMyAdmin\Util::checkParameters
     */
    public function testCheckParameter()
    {
        parent::setGlobalConfig();
        $GLOBALS['cfg'] = ['ServerDefault' => 1];
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
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
     * @param string $bit Value
     * @param string $val Expected value
     *
     * @covers \PhpMyAdmin\Util::convertBitDefaultValue
     * @dataProvider providerConvertBitDefaultValue
     */
    public function testConvertBitDefaultValue($bit, $val): void
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
    public function providerConvertBitDefaultValue()
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
    public function providerUnEscapeMysqlWildcards()
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
    public function testEscapeMysqlWildcards($a, $b): void
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
    public function testUnescapeMysqlWildcards($a, $b): void
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
    public function testExpandUserString($in, $out): void
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
    public function providerExpandUserString()
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
    public function testExtractColumnSpec($in, $out): void
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
    public function providerExtractColumnSpec()
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
     * @param int        $expected Expected value
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
    public function providerExtractValueFromFormattedSize()
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
    public function testIsForeignKeySupported($a, $e): void
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
    public function providerIsForeignKeySupported()
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
     * @param float $a Value to format
     * @param int   $b Sensitiveness
     * @param int   $c Number of decimals to retain
     * @param array $e Expected value
     *
     * @covers \PhpMyAdmin\Util::formatByteDown
     * @dataProvider providerFormatByteDown
     */
    public function testFormatByteDown($a, $b, $c, $e): void
    {
        $result = Util::formatByteDown($a, $b, $c);
        $result[0] = trim($result[0]);
        $this->assertSame($e, $result);
    }

    /**
     * format byte down data provider
     *
     * @return array
     */
    public function providerFormatByteDown()
    {
        return [
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
        ];
    }

    /**
     * Core test for formatNumber
     *
     * @param float $a Value to format
     * @param int   $b Sensitiveness
     * @param int   $c Number of decimals to retain
     * @param array $d Expected value
     *
     * @return void
     */
    private function assertFormatNumber($a, $b, $c, $d)
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
     * @param float $a Value to format
     * @param int   $b Sensitiveness
     * @param int   $c Number of decimals to retain
     * @param array $d Expected value
     *
     * @covers \PhpMyAdmin\Util::formatNumber
     * @dataProvider providerFormatNumber
     */
    public function testFormatNumber($a, $b, $c, $d): void
    {
        $this->assertFormatNumber($a, $b, $c, $d);

        // Test with various precisions
        $old_precision = ini_get('precision');
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
    public function providerFormatNumber()
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
                -1000.454,
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
     * @param int    $size Size
     * @param string $unit Unit
     * @param string $res  Result
     *
     * @covers \PhpMyAdmin\Util::getFormattedMaximumUploadSize
     * @dataProvider providerGetFormattedMaximumUploadSize
     */
    public function testGetFormattedMaximumUploadSize($size, $unit, $res): void
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
    public function providerGetFormattedMaximumUploadSize()
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
     * @param array  $result Expected value
     *
     * @covers \PhpMyAdmin\Util::getTitleForTarget
     * @dataProvider providerGetTitleForTarget
     */
    public function testGetTitleForTarget($target, $result): void
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
    public function providerGetTitleForTarget()
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
    public function providerLocalisedDate()
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
    public function testTimespanFormat($a, $e): void
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
    public function providerTimespanFormat()
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
    public function testPrintableBitValue($a, $b, $e): void
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
    public function providerPrintableBitValue()
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
    public function testUnQuote($param, $expected): void
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
    public function providerUnQuote()
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
    public function testUnQuoteSelectedChar($param, $expected): void
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
    public function providerUnQuoteSelectedChar()
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
     * @param string $a String
     * @param string $b Expected output
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
    public function providerBackquote()
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
     * @param string $entry               String
     * @param string $expectedNoneOutput  Expected none output
     * @param string $expectedMssqlOutput Expected MSSQL output
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
    public function providerBackquoteCompat()
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
     * @return void
     *
     * @covers \PhpMyAdmin\Util::backquote
     */
    public function testBackquoteForbidenWords()
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
    public function testUserDir($a, $e): void
    {
        $GLOBALS['cfg']['Server']['user'] = 'root';

        $this->assertEquals($e, Util::userDir($a));
    }

    /**
     * data provider for PhpMyAdmin\Util::userDir test
     *
     * @return array
     */
    public function providerUserDir()
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
    public function testDuplicateFirstNewline($a, $e): void
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
    public function providerDuplicateFirstNewline()
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
     * @return void
     *
     * @covers \PhpMyAdmin\Util::unsupportedDatatypes
     */
    public function testUnsupportedDatatypes()
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
     * @return void
     *
     * @covers \PhpMyAdmin\Util::getPageFromPosition
     */
    public function testGetPageFromPosition()
    {
        $this->assertEquals(Util::getPageFromPosition(0, 1), 1);
        $this->assertEquals(Util::getPageFromPosition(1, 1), 2);
        $this->assertEquals(Util::getPageFromPosition(1, 2), 1);
        $this->assertEquals(Util::getPageFromPosition(1, 6), 1);
    }

    /**
     * Test for PhpMyAdmin\Util::buildActionTitles
     */
    public function testBuildActionTitles(): void
    {
        $GLOBALS['cfg'] = ['ActionLinksMode' => 'both'];

        $titles = [];
        $titles['Browse']     = Generator::getIcon('b_browse', __('Browse'));
        $titles['NoBrowse']   = Generator::getIcon('bd_browse', __('Browse'));
        $titles['Search']     = Generator::getIcon('b_select', __('Search'));
        $titles['NoSearch']   = Generator::getIcon('bd_select', __('Search'));
        $titles['Insert']     = Generator::getIcon('b_insrow', __('Insert'));
        $titles['NoInsert']   = Generator::getIcon('bd_insrow', __('Insert'));
        $titles['Structure']  = Generator::getIcon('b_props', __('Structure'));
        $titles['Drop']       = Generator::getIcon('b_drop', __('Drop'));
        $titles['NoDrop']     = Generator::getIcon('bd_drop', __('Drop'));
        $titles['Empty']      = Generator::getIcon('b_empty', __('Empty'));
        $titles['NoEmpty']    = Generator::getIcon('bd_empty', __('Empty'));
        $titles['Edit']       = Generator::getIcon('b_edit', __('Edit'));
        $titles['NoEdit']     = Generator::getIcon('bd_edit', __('Edit'));
        $titles['Export']     = Generator::getIcon('b_export', __('Export'));
        $titles['NoExport']   = Generator::getIcon('bd_export', __('Export'));
        $titles['Execute']    = Generator::getIcon('b_nextpage', __('Execute'));
        $titles['NoExecute']  = Generator::getIcon('bd_nextpage', __('Execute'));
        $titles['Favorite']   = Generator::getIcon('b_favorite', '');
        $titles['NoFavorite'] = Generator::getIcon('b_no_favorite', '');

        $this->assertEquals($titles, Util::buildActionTitles());
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
     * @return void
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
}
