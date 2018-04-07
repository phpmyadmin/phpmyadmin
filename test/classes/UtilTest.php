<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Util class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Util;

/**
 * Test for PhpMyAdmin\Util class
 *
 * @package PhpMyAdmin-test
 */
class UtilTest extends PmaTestCase
{
    /**
     * Test for createGISData
     *
     * @return void
     */
    public function testCreateGISData()
    {
        $this->assertEquals(
            "abc",
            Util::createGISData("abc")
        );
        $this->assertEquals(
            "GeomFromText('POINT()',10)",
            Util::createGISData("'POINT()',10")
        );
    }

    /**
     * Test for getGISFunctions
     *
     * @return void
     */
    public function testGetGISFunctions()
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
        $this->assertContains(
            '<select class="pageselector ajax" name="pma" >',
            Util::pageselector("pma", 3)
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
     * @return void
     * @test
     * @dataProvider charsetQueryData
     */
    public function testGenerateCharsetQueryPart($collation, $expected)
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
        return array(
            array("a_b_c_d", " CHARSET=a COLLATE a_b_c_d"),
            array("a_", " CHARSET=a COLLATE a_"),
            array("a", " CHARSET=a"),
        );
    }

    /**
     * Test for random generation
     */
    public function testGenerateRandom()
    {
        $this->assertEquals(32, strlen(Util::generateRandom(32)));
        $this->assertEquals(16, strlen(Util::generateRandom(16)));
    }

    /**
     * Test for PhpMyAdmin\Util::getBrowseUploadFileBlock
     *
     * @param int    $size Size
     * @param string $unit Unit
     * @param string $res  Result
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getBrowseUploadFileBlock
     * @dataProvider providerGetBrowseUploadFileBlock
     */
    function testGetBrowseUploadFileBlock($size, $unit, $res)
    {
        $GLOBALS['is_upload'] = false;
        $this->assertEquals(
            Util::getBrowseUploadFileBlock($size),
            '<label for="input_import_file">' . __("Browse your computer:")
            . '</label>'
            . '<div id="upload_form_status" class="hide"></div>'
            . '<div id="upload_form_status_info" class="hide"></div>'
            . '<input type="file" name="import_file" id="input_import_file" />'
            . "(" . __('Max: ') . $res . $unit . ")" . "\n"
            . '<input type="hidden" name="MAX_FILE_SIZE" value="'
            . $size . '" />' . "\n"
        );
    }

    /**
     * Data provider for testGetBrowseUploadFileBlock
     *
     * @return array
     */
    public function providerGetBrowseUploadFileBlock()
    {
        return array(
            array(10, __('B'), "10"),
            array(100, __('B'), "100"),
            array(1024, __('B'), "1,024"),
            array(102400, __('KiB'), "100"),
            array(10240000, __('MiB'), "10"),
            array(2147483648, __('MiB'), "2,048"),
            array(21474836480, __('GiB'), "20")
        );
    }

    /**
     * Test for PhpMyAdmin\Util::buildActionTitles
     *
     * @covers PhpMyAdmin\Util::buildActionTitles
     *
     * @return void
     */
    function testBuildActionTitles()
    {
        $GLOBALS['cfg'] = array('ActionLinksMode' => 'both');

        $titles = array();
        $titles['Browse']     = Util::getIcon('b_browse', __('Browse'));
        $titles['NoBrowse']   = Util::getIcon('bd_browse', __('Browse'));
        $titles['Search']     = Util::getIcon('b_select', __('Search'));
        $titles['NoSearch']   = Util::getIcon('bd_select', __('Search'));
        $titles['Insert']     = Util::getIcon('b_insrow', __('Insert'));
        $titles['NoInsert']   = Util::getIcon('bd_insrow', __('Insert'));
        $titles['Structure']  = Util::getIcon('b_props', __('Structure'));
        $titles['Drop']       = Util::getIcon('b_drop', __('Drop'));
        $titles['NoDrop']     = Util::getIcon('bd_drop', __('Drop'));
        $titles['Empty']      = Util::getIcon('b_empty', __('Empty'));
        $titles['NoEmpty']    = Util::getIcon('bd_empty', __('Empty'));
        $titles['Edit']       = Util::getIcon('b_edit', __('Edit'));
        $titles['NoEdit']     = Util::getIcon('bd_edit', __('Edit'));
        $titles['Export']     = Util::getIcon('b_export', __('Export'));
        $titles['NoExport']   = Util::getIcon('bd_export', __('Export'));
        $titles['Execute']    = Util::getIcon('b_nextpage', __('Execute'));
        $titles['NoExecute']  = Util::getIcon('bd_nextpage', __('Execute'));
        $titles['Favorite']   = Util::getIcon('b_favorite', '');
        $titles['NoFavorite'] = Util::getIcon('b_no_favorite', '');

        $this->assertEquals($titles, Util::buildActionTitles());
    }

    /**
     * Test if cached data is available after set
     *
     * @covers PhpMyAdmin\Util::cacheExists
     *
     * @return void
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
     * @covers PhpMyAdmin\Util::cacheGet
     *
     * @return void
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
     * @covers PhpMyAdmin\Util::cacheSet
     *
     * @return void
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
     * @covers PhpMyAdmin\Util::cacheUnset
     *
     * @return void
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
     * @covers PhpMyAdmin\Util::clearUserCache
     *
     * @return void
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
     * @covers PhpMyAdmin\Util::checkParameters
     *
     * @return void
     */
    function testCheckParameterMissing()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;

        $this->expectOutputRegex("/Missing parameter: field/");

        Util::checkParameters(
            array('db', 'table', 'field')
        );
    }

    /**
     * Test for Util::checkParameters
     *
     * @covers PhpMyAdmin\Util::checkParameters
     *
     * @return void
     */
    function testCheckParameter()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['cfg'] = array('ServerDefault' => 1);
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
        $GLOBALS['db'] = "dbDatabase";
        $GLOBALS['table'] = "tblTable";
        $GLOBALS['field'] = "test_field";
        $GLOBALS['sql_query'] = "SELECT * FROM tblTable;";

        $this->expectOutputString("");
        Util::checkParameters(
            array('db', 'table', 'field', 'sql_query')
        );
    }

    /**
     * Test for Util::containsNonPrintableAscii
     *
     * @param string $str Value
     * @param bool   $res Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::containsNonPrintableAscii
     * @dataProvider providerContainsNonPrintableAscii
     */
    public function testContainsNonPrintableAscii($str, $res)
    {
        $this->assertEquals(
            $res, Util::containsNonPrintableAscii($str)
        );
    }

    /**
     * Data provider for testContainsNonPrintableAscii
     *
     * @return array
     */
    public function providerContainsNonPrintableAscii()
    {
        return array(
            array("normal string", 0),
            array("new\nline", 1),
            array("tab\tspace", 1),
            array("escape" . chr(27) . "char", 1),
            array("chars%$\r\n", 1),
        );
    }

    /**
     * Test for Util::convertBitDefaultValue
     *
     * @param string $bit Value
     * @param string $val Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::convertBitDefaultValue
     * @dataProvider providerConvertBitDefaultValue
     */
    public function testConvertBitDefaultValue($bit, $val)
    {
        $this->assertEquals(
            $val, Util::convertBitDefaultValue($bit)
        );
    }

    /**
     * Provider for testConvertBitDefaultValue
     *
     * @return array
     */
    public function providerConvertBitDefaultValue()
    {
        return array(
            array("b'",""),
            array("b'01'","01"),
            array("b'010111010'","010111010")
        );
    }

    /**
     * data provider for testEscapeMysqlWildcards and testUnescapeMysqlWildcards
     *
     * @return array
     */
    public function providerUnEscapeMysqlWildcards()
    {
        return array(
            array('\_test', '_test'),
            array('\_\\', '_\\'),
            array('\\_\%', '_%'),
            array('\\\_', '\_'),
            array('\\\_\\\%', '\_\%'),
            array('\_\\%\_\_\%', '_%__%'),
            array('\%\_', '%_'),
            array('\\\%\\\_', '\%\_')
        );
    }

    /**
     * PhpMyAdmin\Util::escapeMysqlWildcards tests
     *
     * @param string $a Expected value
     * @param string $b String to escape
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::escapeMysqlWildcards
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testEscapeMysqlWildcards($a, $b)
    {
        $this->assertEquals(
            $a, Util::escapeMysqlWildcards($b)
        );
    }

    /**
     * PhpMyAdmin\Util::unescapeMysqlWildcards tests
     *
     * @param string $a String to unescape
     * @param string $b Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::unescapeMysqlWildcards
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testUnescapeMysqlWildcards($a, $b)
    {
        $this->assertEquals(
            $b, Util::unescapeMysqlWildcards($a)
        );
    }

    /**
     * Test case for expanding strings
     *
     * @param string $in  string to evaluate
     * @param string $out expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::expandUserString
     * @dataProvider providerExpandUserString
     */
    public function testExpandUserString($in, $out)
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg'] = array(
            'Server' => array(
                'host' => 'host&',
                'verbose' => 'verbose',
            )
        );
        $GLOBALS['db'] = 'database';
        $GLOBALS['table'] = 'table';

        $out = str_replace('PMA_VERSION', PMA_VERSION, $out);

        $this->assertEquals(
            $out, Util::expandUserString($in)
        );

        $this->assertEquals(
            htmlspecialchars($out),
            Util::expandUserString(
                $in, 'htmlspecialchars'
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
        return array(
            array('@SERVER@', 'host&'),
            array('@VSERVER@', 'verbose'),
            array('@DATABASE@', 'database'),
            array('@TABLE@', 'table'),
            array('@IGNORE@', '@IGNORE@'),
            array('@PHPMYADMIN@', 'phpMyAdmin PMA_VERSION'),
        );
    }

    /**
     * Test case for parsing SHOW COLUMNS output
     *
     * @param string $in  Column specification
     * @param array  $out Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::extractColumnSpec
     * @dataProvider providerExtractColumnSpec
     */
    public function testExtractColumnSpec($in, $out)
    {
        $GLOBALS['cfg']['LimitChars'] = 1000;

        $this->assertEquals(
            $out, Util::extractColumnSpec($in)
        );
    }

    /**
     * Data provider for testExtractColumnSpec
     *
     * @return array
     */
    public function providerExtractColumnSpec()
    {
        return array(
            array(
                "SET('a','b')",
                array(
                    'type' => 'set',
                    'print_type' => "set('a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a','b'",
                    'enum_set_values' => array('a', 'b'),
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('a', 'b')"
                ),
            ),
            array(
                "SET('\'a','b')",
                array(
                    'type' => 'set',
                    'print_type' => "set('\'a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'\'a','b'",
                    'enum_set_values' => array("'a", 'b'),
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('\'a', 'b')"
                ),
            ),
            array(
                "SET('''a','b')",
                array(
                    'type' => 'set',
                    'print_type' => "set('''a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'''a','b'",
                    'enum_set_values' => array("'a", 'b'),
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('''a', 'b')"
                ),
            ),
            array(
                "ENUM('a&b', 'b''c\\'d', 'e\\\\f')",
                array(
                    'type' => 'enum',
                    'print_type' => "enum('a&b', 'b''c\\'d', 'e\\\\f')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a&b', 'b''c\\'d', 'e\\\\f'",
                    'enum_set_values' => array('a&b', 'b\'c\'d', 'e\\f'),
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "enum('a&amp;b', 'b''c\\'d', 'e\\\\f')"
                ),
            ),
            array(
                "INT UNSIGNED zerofill",
                array(
                    'type' => 'int',
                    'print_type' => 'int',
                    'binary' => false,
                    'unsigned' => true,
                    'zerofill' => true,
                    'spec_in_brackets' => '',
                    'enum_set_values' => array(),
                    'attribute' => 'UNSIGNED ZEROFILL',
                    'can_contain_collation' => false,
                    'displayed_type' => "int"
                ),
            ),
            array(
                "VARCHAR(255)",
                array(
                    'type' => 'varchar',
                    'print_type' => 'varchar(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => array(),
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "varchar(255)"
                ),
            ),
            array(
                "VARBINARY(255)",
                array(
                    'type' => 'varbinary',
                    'print_type' => 'varbinary(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => array(),
                    'attribute' => ' ',
                    'can_contain_collation' => false,
                    'displayed_type' => "varbinary(255)"
                ),
            ),
        );
    }

    /**
     * Test for Util::extractValueFromFormattedSize
     *
     * @param int|string $size     Size
     * @param int        $expected Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::extractValueFromFormattedSize
     * @dataProvider providerExtractValueFromFormattedSize
     */
    function testExtractValueFromFormattedSize($size, $expected)
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
        return array(
            array(100, -1),
            array("10GB", 10737418240),
            array("15MB", 15728640),
            array("256K", 262144)
        );
    }

    /**
     * foreign key supported test
     *
     * @param string $a Engine
     * @param bool   $e Expected Value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::isForeignKeySupported
     * @dataProvider providerIsForeignKeySupported
     */
    public function testIsForeignKeySupported($a, $e)
    {
        $GLOBALS['server'] = 1;

        $this->assertEquals(
            $e, Util::isForeignKeySupported($a)
        );
    }

    /**
     * data provider for foreign key supported test
     *
     * @return array
     */
    public function providerIsForeignKeySupported()
    {
        return array(
            array('MyISAM', false),
            array('innodb', true),
            array('pBxT', true),
            array('ndb', true)
        );
    }

    /**
     * Test for formatSql
     *
     * @covers PhpMyAdmin\Util::formatSql
     *
     * @return void
     */
    function testFormatSql()
    {
        $this->assertEquals(
            '<code class="sql"><pre>' . "\n"
            . 'SELECT 1 &lt; 2' . "\n"
            . '</pre></code>',
            Util::formatSql('SELECT 1 < 2')
        );

        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 6;

        $this->assertEquals(
            '<code class="sql"><pre>' . "\n"
            . 'SELECT[...]' . "\n"
            . '</pre></code>',
            Util::formatSql('SELECT 1 < 2', true)
        );
    }

    /**
     * format byte test, globals are defined
     *
     * @param float $a Value to format
     * @param int   $b Sensitiveness
     * @param int   $c Number of decimals to retain
     * @param array $e Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::formatByteDown
     * @dataProvider providerFormatByteDown
     */
    public function testFormatByteDown($a, $b, $c, $e)
    {
        $result = Util::formatByteDown($a, $b, $c);
        $result[0] = trim($result[0]);
        $this->assertEquals($e, $result);
    }

    /**
     * format byte down data provider
     *
     * @return array
     */
    public function providerFormatByteDown()
    {
        return array(
            array(10, 2, 2, array('10', __('B'))),
            array(100, 2, 0, array('0', __('KiB'))),
            array(100, 3, 0, array('100', __('B'))),
            array(100, 2, 2, array('0.10', __('KiB'))),
            array(1034, 3, 2, array('1.01', __('KiB'))),
            array(100233, 3, 3, array('97.884', __('KiB'))),
            array(2206451, 1, 2, array('2.10', __('MiB'))),
            array(21474836480, 4, 0, array('20', __('GiB'))),
            array(doubleval(52) + doubleval(2048), 3, 1, array('2.1', 'KiB')),
        );
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
                $a, $b, $c, false
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
     * @return void
     *
     * @covers PhpMyAdmin\Util::formatNumber
     * @dataProvider providerFormatNumber
     */
    public function testFormatNumber($a, $b, $c, $d)
    {
        $this->assertFormatNumber($a, $b, $c, $d);

        // Test with various precisions
        $old_precision = ini_get('precision');
        try {
            ini_set('precision', 20);
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', 14);
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', 10);
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', 5);
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', -1);
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
        return array(
            array(10, 2, 2, '10  '),
            array(100, 2, 0, '100  '),
            array(100, 2, 2, '100  '),
            array(-1000.454, 4, 2, '-1,000.45  '),
            array(0.00003, 3, 2, '30 &micro;'),
            array(0.003, 3, 3, '3 m'),
            array(-0.003, 6, 0, '-3,000 &micro;'),
            array(100.98, 0, 2, '100.98'),
            array(21010101, 0, 2, '21,010,101.00'),
            array(1100000000, 5, 0, '1,100 M'),
            array(20000, 2, 2, '20 k'),
            array(20011, 2, 2, '20.01 k'),
            array(123456789, 6, 0, '123,457 k'),
            array(-123456789, 4, 2, '-123.46 M'),
            array(0, 6, 0, '0')
        );
    }

    /**
     * Test for Util::generateHiddenMaxFileSize
     *
     * @param int $size Size
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::generateHiddenMaxFileSize
     * @dataProvider providerGenerateHiddenMaxFileSize
     */
    public function testGenerateHiddenMaxFileSize($size)
    {
        $this->assertEquals(
            Util::generateHiddenMaxFileSize($size),
            '<input type="hidden" name="MAX_FILE_SIZE" value="' . $size . '" />'
        );
    }

    /**
     * Data provider for testGenerateHiddenMaxFileSize
     *
     * @return array
     */
    public function providerGenerateHiddenMaxFileSize()
    {
        return array(
            array(10),
            array("100"),
            array(1024),
            array("1024Mb"),
            array(2147483648),
            array("some_string")
        );
    }

    /**
     * Test for getDbLink
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDbLink
     * @group medium
     */
    public function testGetDbLinkEmpty()
    {
        $GLOBALS['db'] = null;
        $this->assertEmpty(Util::getDbLink());
    }

    /**
     * Test for getDbLink
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDbLink
     * @group medium
     */
    public function testGetDbLinkNull()
    {
        global $cfg;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['server'] = 99;
        $database = $GLOBALS['db'];
        $this->assertEquals(
            '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
            . '?db=' . $database
            . '&amp;server=99&amp;lang=en" '
            . 'title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Util::getDbLink()
        );
    }

    /**
     * Test for getDbLink
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDbLink
     */
    public function testGetDbLink()
    {
        global $cfg;
        $GLOBALS['server'] = 99;
        $database = 'test_database';
        $this->assertEquals(
            '<a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
            . '?db=' . $database
            . '&amp;server=99&amp;lang=en" title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Util::getDbLink($database)
        );
    }

    /**
     * Test for getDbLink
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDbLink
     */
    public function testGetDbLinkWithSpecialChars()
    {
        global $cfg;
        $GLOBALS['server'] = 99;
        $database = 'test&data\'base';
        $this->assertEquals(
            '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
            . '?db='
            . htmlspecialchars(urlencode($database))
            . '&amp;server=99&amp;lang=en" title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Util::getDbLink($database)
        );
    }

    /**
     * Test for getDivForSliderEffect
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDivForSliderEffect
     */
    public function testGetDivForSliderEffectTest()
    {
        global $cfg;
        $cfg['InitialSlidersState'] = 'undefined';

        $id = "test_id";
        $message = "test_message";

        $this->assertXmlStringEqualsXmlString(
            "<root>" . Util::getDivForSliderEffect($id, $message) . "</div></root>",
            "<root><div id=\"$id\" class=\"pma_auto_slider\"\ntitle=\""
            . htmlspecialchars($message) . "\" >\n</div></root>"
        );
    }

    /**
     * Test for getDivForSliderEffect
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDivForSliderEffect
     */
    public function testGetDivForSliderEffectTestClosed()
    {
        global $cfg;
        $cfg['InitialSlidersState'] = 'closed';

        $id = "test_id";
        $message = "test_message";

        $this->assertXmlStringEqualsXmlString(
            "<root>" . Util::getDivForSliderEffect($id, $message) . "</div></root>",
            "<root><div id=\"$id\" style=\"display: none; overflow:auto;\" class=\"pma_auto_slider\"\ntitle=\""
            . htmlspecialchars($message) . "\" >\n</div></root>"
        );

    }

    /**
     * Test for getDivForSliderEffect
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDivForSliderEffect
     */
    public function testGetDivForSliderEffectTestDisabled()
    {
        global $cfg;
        $cfg['InitialSlidersState'] = 'disabled';

        $id = "test_id";
        $message = "test_message";

        $this->assertXmlStringEqualsXmlString(
            "<root>" . Util::getDivForSliderEffect($id, $message) . "</div></root>",
            "<root><div id=\"$id\">\n</div></root>"
        );
    }

    /**
     * Test for getDropdown
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDropdown
     */
    public function testGetDropdownEmpty()
    {
        $name = "test_dropdown_name";
        $choices = array();
        $active_choice = null;
        $id = "test_&lt;dropdown&gt;_name";

        $result = '<select name="' . htmlspecialchars($name) . '" id="'
            . htmlspecialchars($id) . '">' . "\n" . '</select>' . "\n";

        $this->assertEquals(
            $result,
            Util::getDropdown(
                $name, $choices, $active_choice, $id
            )
        );
    }

    /**
     * Test for getDropdown
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDropdown
     */
    public function testGetDropdown()
    {
        $name = "&test_dropdown_name";
        $choices = array("value_1" => "label_1", "value&_2\"" => "label_2");
        $active_choice = null;
        $id = "test_&lt;dropdown&gt;_name";

        $result = '<select name="' . htmlspecialchars($name) . '" id="'
            . htmlspecialchars($id) . '">';
        foreach ($choices as $one_choice_value => $one_choice_label) {
            $result .= "\n" . '<option value="' . htmlspecialchars($one_choice_value) . '"';
            if ($one_choice_value == $active_choice) {
                $result .= ' selected="selected"';
            }
            $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
        }
        $result .= "\n" . '</select>' . "\n";

        $this->assertEquals(
            $result,
            Util::getDropdown(
                $name, $choices, $active_choice, $id
            )
        );
    }

    /**
     * Test for getDropdown
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getDropdown
     */
    public function testGetDropdownWithActive()
    {
        $name = "&test_dropdown_name";
        $choices = array("value_1" => "label_1", "value&_2\"" => "label_2");
        $active_choice = "value&_2\"";
        $id = "test_&lt;dropdown&gt;_name";

        $result = '<select name="' . htmlspecialchars($name) . '" id="'
            . htmlspecialchars($id) . '">';
        foreach ($choices as $one_choice_value => $one_choice_label) {
            $result .= "\n";
            $result .= '<option value="' . htmlspecialchars($one_choice_value) . '"';
            if ($one_choice_value == $active_choice) {
                $result .= ' selected="selected"';
            }
            $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
        }
        $result .= "\n";
        $result .= '</select>' . "\n";

        $this->assertEquals(
            $result,
            Util::getDropdown(
                $name, $choices, $active_choice, $id
            )
        );
    }

    /**
     * Test for Util::getFormattedMaximumUploadSize
     *
     * @param int    $size Size
     * @param string $unit Unit
     * @param string $res  Result
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getFormattedMaximumUploadSize
     * @dataProvider providerGetFormattedMaximumUploadSize
     */
    public function testGetFormattedMaximumUploadSize($size, $unit, $res)
    {
        $this->assertEquals(
            "(" . __('Max: ') . $res . $unit . ")",
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
        return array(
            array(10, __('B'), "10"),
            array(100, __('B'), "100"),
            array(1024, __('B'), "1,024"),
            array(102400, __('KiB'), "100"),
            array(10240000, __('MiB'), "10"),
            array(2147483648, __('MiB'), "2,048"),
            array(21474836480, __('GiB'), "20")
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getIcon
     */
    public function testGetIconWithoutActionLinksMode()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'text';

        $this->assertEquals(
            '<span class="nowrap"></span>',
            Util::getIcon('b_comment')
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getIcon
     */
    public function testGetIconWithActionLinksMode()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment" /></span>',
            Util::getIcon('b_comment')
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getIcon
     */
    public function testGetIconAlternate()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="'
            . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /></span>',
            Util::getIcon('b_comment', $alternate_text)
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getIcon
     */
    public function testGetIconWithForceText()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternate_text = 'alt_str';

        // Here we are checking for an icon embedded inside a span (i.e not a menu
        // bar icon
        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="'
            . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" />&nbsp;' . $alternate_text . '</span>',
            Util::getIcon('b_comment', $alternate_text, true, false)
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsEmpty()
    {
        $name = "test_display_radio";
        $choices = array();

        $this->assertEquals(
            Util::getRadioFields($name, $choices),
            ""
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFields()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields($name, $choices),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsWithChecked()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields(
                $name, $choices, $checked_choice
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsWithCheckedWithClass()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');
        $checked_choice = "value_2";
        $class = "test_class";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<div class="' . $class . '">';
            $out .= "\n";
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br />';
            $out .= "\n";
            $out .= '</div>';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields(
                $name, $choices, $checked_choice, true, false, $class
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsWithoutBR()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value&_&lt;2&gt;'=>'choice_2');
        $checked_choice = "choice_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields(
                $name, $choices, $checked_choice, false
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsEscapeLabelEscapeLabel()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">'
                . htmlspecialchars($choice_label) . '</label>';
            $out .= "\n";
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields(
                $name, $choices, $checked_choice, true, true
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsEscapeLabelNotEscapeLabel()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields(
                $name, $choices, $checked_choice, true, false
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getRadioFields
     */
    public function testGetRadioFieldsEscapeLabelEscapeLabelWithClass()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";
        $class = "test_class";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<div class="' . $class . '">';
            $out .= "\n";
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">'
                . htmlspecialchars($choice_label) . '</label>';
            $out .= "\n";
            $out .= '<br />';
            $out .= "\n";
            $out .= '</div>';
            $out .= "\n";
        }

        $this->assertEquals(
            Util::getRadioFields(
                $name, $choices, $checked_choice, true, true, $class
            ),
            $out
        );
    }

    /**
     * Test for Util::getTitleForTarget
     *
     * @param string $target Target
     * @param array  $result Expected value
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::getTitleForTarget
     * @dataProvider providerGetTitleForTarget
     */
    public function testGetTitleForTarget($target, $result)
    {
        $this->assertEquals(
            $result, Util::getTitleForTarget($target)
        );
    }

    /**
     * Data provider for testGetTitleForTarget
     *
     * @return array
     */
    public function providerGetTitleForTarget()
    {
        return array(
            array('tbl_structure.php', __('Structure')),
            array('tbl_sql.php', __('SQL'),),
            array('tbl_select.php', __('Search'),),
            array('tbl_change.php', __('Insert')),
            array('sql.php', __('Browse')),
            array('db_structure.php', __('Structure')),
            array('db_sql.php', __('SQL')),
            array('db_search.php', __('Search')),
            array('db_operations.php', __('Operations')),
        );
    }

    /**
     * localised date test, globals are defined
     *
     * @param string $a Current timestamp
     * @param string $b Format
     * @param string $e Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::localisedDate
     * @dataProvider providerLocalisedDate
     */
    public function testLocalisedDate($a, $b, $e)
    {
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');

        $this->assertEquals(
            $e, Util::localisedDate($a, $b)
        );

        date_default_timezone_set($tmpTimezone);
    }

    /**
     * data provider for localised date test
     *
     * @return array
     */
    public function providerLocalisedDate()
    {
        return array(
            array(1227455558, '', 'Nov 23, 2008 at 03:52 PM'),
            array(1227455558, '%Y-%m-%d %H:%M:%S %a', '2008-11-23 15:52:38 Sun')
        );
    }

    /**
     * localised timestamp test, globals are defined
     *
     * @param int    $a Timespan in seconds
     * @param string $e Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::timespanFormat
     * @dataProvider providerTimespanFormat
     */
    public function testTimespanFormat($a, $e)
    {
        $GLOBALS['timespanfmt'] = '%s days, %s hours, %s minutes and %s seconds';
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');

        $this->assertEquals(
            $e, Util::timespanFormat($a)
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
        return array(
            array(1258, '0 days, 0 hours, 20 minutes and 58 seconds'),
            array(821958, '9 days, 12 hours, 19 minutes and 18 seconds')
        );
    }

    /**
     * test for generating string contains printable bit value of selected data
     *
     * @param integer $a Value
     * @param int     $b Length
     * @param string  $e Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::printableBitValue
     * @dataProvider providerPrintableBitValue
     */
    public function testPrintableBitValue($a, $b, $e)
    {
        $this->assertEquals(
            $e, Util::printableBitValue($a, $b)
        );
    }

    /**
     * data provider for printable bit value test
     *
     * @return array
     */
    public function providerPrintableBitValue()
    {
        return array(
            array(
                '20131009',
                64,
                '0000000000000000000000000000000000000001001100110010110011000001'
            ),
            array('5', 32, '00000000000000000000000000000101')
        );
    }

    /**
     * PhpMyAdmin\Util::unQuote test
     *
     * @param string $param    String
     * @param string $expected Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::unQuote
     * @dataProvider providerUnQuote
     */
    public function testUnQuote($param, $expected)
    {
        $this->assertEquals(
            $expected, Util::unQuote($param)
        );
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test
     *
     * @return array
     */
    public function providerUnQuote()
    {
        return array(
            array('"test\'"', "test'"),
            array("'test''", "test'"),
            array("`test'`", "test'"),
            array("'test'test", "'test'test")
        );
    }

    /**
     * PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @param string $param    String
     * @param string $expected Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::unQuote
     * @dataProvider providerUnQuoteSelectedChar
     */
    public function testUnQuoteSelectedChar($param, $expected)
    {
        $this->assertEquals(
            $expected, Util::unQuote($param, '"')
        );
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @return array
     */
    public function providerUnQuoteSelectedChar()
    {
        return array(
            array('"test\'"', "test'"),
            array("'test''", "'test''"),
            array("`test'`", "`test'`"),
            array("'test'test", "'test'test")
        );
    }

    /**
     * backquote test with different param $do_it (true, false)
     *
     * @param string $a String
     * @param string $b Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::backquote
     * @dataProvider providerBackquote
     */
    public function testBackquote($a, $b)
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
        return array(
            array('0', '`0`'),
            array('test', '`test`'),
            array('te`st', '`te``st`'),
            array(
                array('test', 'te`st', '', '*'),
                array('`test`', '`te``st`', '', '*')
            )
        );
    }

    /**
     * backquoteCompat test with different param $compatibility (NONE, MSSQL)
     *
     * @param string $a String
     * @param string $b Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::backquoteCompat
     * @dataProvider providerBackquoteCompat
     */
    public function testBackquoteCompat($a, $b)
    {
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($a, Util::backquoteCompat($a, 'NONE', false));

        // Run tests in MSSQL compatibility mode
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($a, Util::backquoteCompat($a, 'MSSQL', false));

        // Test backquote
        $this->assertEquals($b, Util::backquoteCompat($a, 'MSSQL'));
    }

    /**
     * data provider for backquoteCompat test
     *
     * @return array
     */
    public function providerBackquoteCompat()
    {
        return array(
            array('0', '"0"'),
            array('test', '"test"'),
            array('te`st', '"te`st"'),
            array(
                array('test', 'te`st', '', '*'),
                array('"test"', '"te`st"', '', '*')
            )
        );
    }

    /**
     * backquoteCompat test with forbidden words
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::backquote
     */
    public function testBackquoteForbidenWords()
    {
        foreach (Context::$KEYWORDS as $keyword => $type) {
            if ($type & Token::FLAG_KEYWORD_RESERVED) {
                $this->assertEquals(
                    "`" . $keyword . "`",
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
     * Test for Util::showDocu
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::showDocu
     */
    public function testShowDocu()
    {
        $GLOBALS['server'] = '99';
        $GLOBALS['cfg']['ServerDefault'] = 1;

        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fpage.html%23anchor" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help" /></a>',
            Util::showDocu('page', 'anchor')
        );
    }

    /**
     * Test for showPHPDocu
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::showPHPDocu
     */
    public function testShowPHPDocu()
    {
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="./url.php?url=https%3A%2F%2Fsecure.php.net%2Fmanual%2F' . $lang
            . '%2F' . $target . '" target="documentation">'
            . '<img src="themes/dot.gif" title="' . __('Documentation') . '" alt="'
            . __('Documentation') . '" class="icon ic_b_help" /></a>';

        $this->assertEquals(
            $expected, Util::showPHPDocu($target)
        );
    }

    /**
     * test of generating user dir, globals are defined
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::userDir
     * @dataProvider providerUserDir
     */
    public function testUserDir($a, $e)
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
        return array(
            array('/var/pma_tmp/%u/', "/var/pma_tmp/root/"),
            array('/home/%u/pma', "/home/root/pma/")
        );
    }

    /**
     * duplicate first newline test
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::duplicateFirstNewline
     * @dataProvider providerDuplicateFirstNewline
     */
    public function testDuplicateFirstNewline($a, $e)
    {
        $this->assertEquals(
            $e, Util::duplicateFirstNewline($a)
        );
    }

    /**
     * data provider for duplicate first newline test
     *
     * @return array
     */
    public function providerDuplicateFirstNewline()
    {
        return array(
            array('test', 'test'),
            array("\r\ntest", "\n\r\ntest"),
            array("\ntest", "\ntest"),
            array("\n\r\ntest", "\n\r\ntest")
        );
    }

    /**
     * Test for Util::unsupportedDatatypes
     *
     * @return void
     *
     * @covers PhpMyAdmin\Util::unsupportedDatatypes
     */
    function testUnsupportedDatatypes()
    {
        $no_support_types = array();
        $this->assertEquals(
            $no_support_types, Util::unsupportedDatatypes()
        );
    }

    /**
     * Test for Util::linkOrButton
     *
     * @return void
     *
     * @dataProvider linksOrButtons
     */
    public function testLinkOrButton(array $params, $limit, $match)
    {
        $restore = isset($GLOBALS['cfg']['LinkLengthLimit']) ? $GLOBALS['cfg']['LinkLengthLimit'] : 1000;
        $GLOBALS['cfg']['LinkLengthLimit'] = $limit;
        try {
            $result = call_user_func_array(
                array('PhpMyAdmin\Util', 'linkOrButton'),
                $params
            );
            $this->assertEquals($match, $result);
        } finally {
            $GLOBALS['cfg']['LinkLengthLimit'] = $restore;
        }
    }

    /**
     * Data provider for Util::linkOrButton test
     *
     * @return array
     */
    public function linksOrButtons()
    {
        return [
            [
                ['index.php', 'text'],
                1000,
                '<a href="index.php" >text</a>'
            ],
            [
                ['index.php?some=parameter', 'text'],
                20,
                '<a href="index.php" data-post="some=parameter">text</a>',
            ],
            [
                ['index.php', 'text', [], 'target'],
                1000,
                '<a href="index.php" target="target">text</a>',
            ],
            [
                ['url.php?url=http://phpmyadmin.net/', 'text', [], '_blank'],
                1000,
                '<a href="url.php?url=http://phpmyadmin.net/" target="_blank" rel="noopener noreferrer">text</a>',
            ],
        ];
    }
}
