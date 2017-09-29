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
use PhpMyAdmin\Util;

/**
 * Test for PhpMyAdmin\Util class
 *
 * @package PhpMyAdmin-test
 */
class UtilTest extends \PMATestCase
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
     * Skip test if CURL extension is not installed
     *
     * @param boolean $ssl_flags Whether to check support for SSL flags
     *
     * @return void
     */
    public function checkCurl($ssl_flags = false)
    {
        if (! function_exists('curl_init')) {
            $this->markTestSkipped('curl not supported');
        }
        if ($ssl_flags) {
            $curl = curl_version();
            /*
             * Some SSL engines in CURL do not support CURLOPT_CAPATH
             * and CURLOPT_CAINFO flags, see
             * https://curl.haxx.se/docs/ssl-compared.html
             */
            if (stripos($curl['ssl_version'], 'WinSSL') !== false
                || stripos($curl['ssl_version'], 'SecureTransport') !== false
            ) {
                $this->markTestSkipped('Not supported in CURL SSL backend: ' . $curl['ssl_version']);
            }
        }
    }

    /**
     * Test for http request using Curl
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestCurl($url, $method, $return_only_status, $expected)
    {
        $this->checkCurl();
        $result = Util::httpRequestCurl($url, $method, $return_only_status);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAPATH
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestCurlCAPath($url, $method, $return_only_status, $expected)
    {
        $this->checkCurl(true);
        $result = Util::httpRequestCurl($url, $method, $return_only_status, null, '', CURLOPT_CAPATH);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAINFO
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestCurlCAInfo($url, $method, $return_only_status, $expected)
    {
        $this->checkCurl(true);
        $result = Util::httpRequestCurl($url, $method, $return_only_status, null, '', CURLOPT_CAINFO);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using fopen
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestFopen($url, $method, $return_only_status, $expected)
    {
        if (! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('allow_url_fopen not supported');
        }
        $result = Util::httpRequestFopen($url, $method, $return_only_status);
        $this->validateHttp($result, $expected);
    }


    /**
     * Test for http request using generic interface
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequest($url, $method, $return_only_status, $expected)
    {
        if (! function_exists('curl_init') && ! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('neither curl nor allow_url_fopen are supported');
        }
        $result = Util::httpRequest($url, $method, $return_only_status);
        $this->validateHttp($result, $expected);
    }

    /**
     * Method to check http test results
     *
     * @param mixed $result   Result of HTTP request
     * @param mixed $expected Expected match
     *
     * @return void
     */
    private function validateHttp($result, $expected)
    {
        if ($expected === true) {
            $this->assertTrue($result);
        } elseif ($expected === false) {
            $this->assertFalse($result);
        } elseif ($expected === null) {
            $this->assertNull($result);
        } else {
            $this->assertContains($expected, $result);
        }
    }

    /**
     * Data provider for HTTP tests
     *
     * @return array
     */
    public function httpRequests()
    {
        return array(
            array("https://www.phpmyadmin.net/test/data", "GET", true, true),
            array("https://www.phpmyadmin.net/test/data", "POST", true, null),
            array("https://nonexisting.phpmyadmin.net/test/data", "GET", true, null),
            array("https://www.phpmyadmin.net/test/data","GET", false, "TEST DATA"),
            array("https://www.phpmyadmin.net/test/nothing","GET", true, false),
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
        $titles['Browse']     = Util::getIcon('b_browse.png', __('Browse'));
        $titles['NoBrowse']   = Util::getIcon('bd_browse.png', __('Browse'));
        $titles['Search']     = Util::getIcon('b_select.png', __('Search'));
        $titles['NoSearch']   = Util::getIcon('bd_select.png', __('Search'));
        $titles['Insert']     = Util::getIcon('b_insrow.png', __('Insert'));
        $titles['NoInsert']   = Util::getIcon('bd_insrow.png', __('Insert'));
        $titles['Structure']  = Util::getIcon('b_props.png', __('Structure'));
        $titles['Drop']       = Util::getIcon('b_drop.png', __('Drop'));
        $titles['NoDrop']     = Util::getIcon('bd_drop.png', __('Drop'));
        $titles['Empty']      = Util::getIcon('b_empty.png', __('Empty'));
        $titles['NoEmpty']    = Util::getIcon('bd_empty.png', __('Empty'));
        $titles['Edit']       = Util::getIcon('b_edit.png', __('Edit'));
        $titles['NoEdit']     = Util::getIcon('bd_edit.png', __('Edit'));
        $titles['Export']     = Util::getIcon('b_export.png', __('Export'));
        $titles['NoExport']   = Util::getIcon('bd_export.png', __('Export'));
        $titles['Execute']    = Util::getIcon('b_nextpage.png', __('Execute'));
        $titles['NoExecute']  = Util::getIcon('bd_nextpage.png', __('Execute'));
        $titles['Favorite']   = Util::getIcon('b_favorite.png', '');
        $titles['NoFavorite'] = Util::getIcon('b_no_favorite.png', '');

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
        $GLOBALS['cfg'] = array('ServerDefault' => 1);
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();

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
}
