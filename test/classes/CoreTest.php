<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Core class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Tests\PmaTestCase;
use stdClass;

/**
 * Tests for PhpMyAdmin\Core class
 *
 * @package PhpMyAdmin-test
 */
class CoreTest extends PmaTestCase
{
    protected $goto_whitelist = array(
        'db_datadict.php',
        'db_sql.php',
        'db_export.php',
        'db_search.php',
        'export.php',
        'import.php',
        'index.php',
        'pdf_pages.php',
        'pdf_schema.php',
        'server_binlog.php',
        'server_variables.php',
        'sql.php',
        'tbl_select.php',
        'transformation_overview.php',
        'transformation_wrapper.php',
        'user_password.php',
    );

    /**
     * Setup for test cases
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'http://example.net/';
    }

    /**
     * Test for Core::arrayRead
     *
     * @return void
     */
    function testArrayRead()
    {
        $arr = array(
            "int" => 1,
            "str" => "str_val",
            "arr" => array('val1', 'val2', 'val3'),
            "sarr" => array(
                'arr1' => array(1, 2, 3),
                array(3, array('a', 'b', 'c'), 4)
            )
        );

        $this->assertEquals(
            Core::arrayRead('int', $arr),
            $arr['int']
        );

        $this->assertEquals(
            Core::arrayRead('str', $arr),
            $arr['str']
        );

        $this->assertEquals(
            Core::arrayRead('arr/0', $arr),
            $arr['arr'][0]
        );

        $this->assertEquals(
            Core::arrayRead('arr/1', $arr),
            $arr['arr'][1]
        );

        $this->assertEquals(
            Core::arrayRead('arr/2', $arr),
            $arr['arr'][2]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/arr1/0', $arr),
            $arr['sarr']['arr1'][0]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/arr1/1', $arr),
            $arr['sarr']['arr1'][1]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/arr1/2', $arr),
            $arr['sarr']['arr1'][2]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/0/0', $arr),
            $arr['sarr'][0][0]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/0/1', $arr),
            $arr['sarr'][0][1]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/0/1/2', $arr),
            $arr['sarr'][0][1][2]
        );

        $this->assertEquals(
            Core::arrayRead('sarr/not_exiting/1', $arr),
            null
        );

        $this->assertEquals(
            Core::arrayRead('sarr/not_exiting/1', $arr, 0),
            0
        );

        $this->assertEquals(
            Core::arrayRead('sarr/not_exiting/1', $arr, 'default_val'),
            'default_val'
        );
    }

    /**
     * Test for Core::arrayWrite
     *
     * @return void
     */
    function testArrayWrite()
    {
        $arr = array(
            "int" => 1,
            "str" => "str_val",
            "arr" => array('val1', 'val2', 'val3'),
            "sarr" => array(
                'arr1' => array(1, 2, 3),
                array(3, array('a', 'b', 'c'), 4)
            )
        );

        Core::arrayWrite('int', $arr, 5);
        $this->assertEquals($arr['int'], 5);

        Core::arrayWrite('str', $arr, '_str');
        $this->assertEquals($arr['str'], '_str');

        Core::arrayWrite('arr/0', $arr, 'val_arr_0');
        $this->assertEquals($arr['arr'][0], 'val_arr_0');

        Core::arrayWrite('arr/1', $arr, 'val_arr_1');
        $this->assertEquals($arr['arr'][1], 'val_arr_1');

        Core::arrayWrite('arr/2', $arr, 'val_arr_2');
        $this->assertEquals($arr['arr'][2], 'val_arr_2');

        Core::arrayWrite('sarr/arr1/0', $arr, 'val_sarr_arr_0');
        $this->assertEquals($arr['sarr']['arr1'][0], 'val_sarr_arr_0');

        Core::arrayWrite('sarr/arr1/1', $arr, 'val_sarr_arr_1');
        $this->assertEquals($arr['sarr']['arr1'][1], 'val_sarr_arr_1');

        Core::arrayWrite('sarr/arr1/2', $arr, 'val_sarr_arr_2');
        $this->assertEquals($arr['sarr']['arr1'][2], 'val_sarr_arr_2');

        Core::arrayWrite('sarr/0/0', $arr, 5);
        $this->assertEquals($arr['sarr'][0][0], 5);

        Core::arrayWrite('sarr/0/1/0', $arr, 'e');
        $this->assertEquals($arr['sarr'][0][1][0], 'e');

        Core::arrayWrite('sarr/not_existing/1', $arr, 'some_val');
        $this->assertEquals($arr['sarr']['not_existing'][1], 'some_val');

        Core::arrayWrite('sarr/0/2', $arr, null);
        $this->assertNull($arr['sarr'][0][2]);
    }

    /**
     * Test for Core::arrayRemove
     *
     * @return void
     */
    function testArrayRemove()
    {
        $arr = array(
            "int" => 1,
            "str" => "str_val",
            "arr" => array('val1', 'val2', 'val3'),
            "sarr" => array(
                'arr1' => array(1, 2, 3),
                array(3, array('a', 'b', 'c'), 4)
            )
        );

        Core::arrayRemove('int', $arr);
        $this->assertArrayNotHasKey('int', $arr);

        Core::arrayRemove('str', $arr);
        $this->assertArrayNotHasKey('str', $arr);

        Core::arrayRemove('arr/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['arr']);

        Core::arrayRemove('arr/1', $arr);
        $this->assertArrayNotHasKey(1, $arr['arr']);

        Core::arrayRemove('arr/2', $arr);
        $this->assertArrayNotHasKey('arr', $arr);

        $tmp_arr = $arr;
        Core::arrayRemove('sarr/not_existing/1', $arr);
        $this->assertEquals($tmp_arr, $arr);

        Core::arrayRemove('sarr/arr1/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['sarr']['arr1']);

        Core::arrayRemove('sarr/arr1/1', $arr);
        $this->assertArrayNotHasKey(1, $arr['sarr']['arr1']);

        Core::arrayRemove('sarr/arr1/2', $arr);
        $this->assertArrayNotHasKey('arr1', $arr['sarr']);

        Core::arrayRemove('sarr/0/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['sarr'][0]);

        Core::arrayRemove('sarr/0/1/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['sarr'][0][1]);

        Core::arrayRemove('sarr/0/1/1', $arr);
        $this->assertArrayNotHasKey(1, $arr['sarr'][0][1]);

        Core::arrayRemove('sarr/0/1/2', $arr);
        $this->assertArrayNotHasKey(1, $arr['sarr'][0]);

        Core::arrayRemove('sarr/0/2', $arr);

        $this->assertEmpty($arr);
    }

    /**
     * Test for Core::checkPageValidity
     *
     * @param string     $page      Page
     * @param array|null $whiteList White list
     * @param int        $expected  Expected value
     *
     * @return void
     *
     * @dataProvider providerTestGotoNowhere
     */
    function testGotoNowhere($page, $whiteList, $include, $expected)
    {
        $this->assertSame($expected, Core::checkPageValidity($page, $whiteList, $include));
    }

    /**
     * Data provider for testGotoNowhere
     *
     * @return array
     */
    public function providerTestGotoNowhere()
    {
        return array(
            array(null, [], false, false),
            array(null, [], true, false),
            array('export.php', [], false, true),
            array('export.php', [], true, true),
            array('export.php', $this->goto_whitelist, false, true),
            array('export.php', $this->goto_whitelist, true, true),
            array('shell.php', $this->goto_whitelist, false, false),
            array('shell.php', $this->goto_whitelist, true, false),
            array('index.php?sql.php&test=true', $this->goto_whitelist, false, true),
            array('index.php?sql.php&test=true', $this->goto_whitelist, true, false),
            array('index.php%3Fsql.php%26test%3Dtrue', $this->goto_whitelist, false, true),
            array('index.php%3Fsql.php%26test%3Dtrue', $this->goto_whitelist, true, false),
        );
    }

    /**
     * Test for Core::cleanupPathInfo
     *
     * @param string $php_self  The PHP_SELF value
     * @param string $request   The REQUEST_URI value
     * @param string $path_info The PATH_INFO value
     * @param string $expected  Expected result
     *
     * @return void
     *
     * @dataProvider providerTestPathInfo
     */
    public function testPathInfo($php_self, $request, $path_info, $expected)
    {
        $_SERVER['PHP_SELF'] = $php_self;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['PATH_INFO'] = $path_info;
        Core::cleanupPathInfo();
        $this->assertEquals(
            $expected,
            $GLOBALS['PMA_PHP_SELF']
        );
    }

    /**
     * Data provider for Core::cleanupPathInfo tests
     *
     * @return array
     */
    public function providerTestPathInfo()
    {
        return array(
            array(
                '/phpmyadmin/index.php/; cookieinj=value/',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '//example.com/../phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '//example.com/../../.././phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '/page.php/malicouspathinfo?malicouspathinfo',
                'malicouspathinfo',
                '/page.php'
            ),
            array(
                '/phpmyadmin/./index.php',
                '/phpmyadmin/./index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '/phpmyadmin/index.php',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
        );
    }

    /**
     * Test for Core::fatalError
     *
     * @return void
     */
    public function testFatalErrorMessage()
    {
        $this->expectOutputRegex("/FatalError!/");
        Core::fatalError("FatalError!");
    }

    /**
     * Test for Core::fatalError
     *
     * @return void
     */
    public function testFatalErrorMessageWithArgs()
    {
        $message = "Fatal error #%d in file %s.";
        $params = array(1, 'error_file.php');

        $this->expectOutputRegex("/Fatal error #1 in file error_file.php./");
        Core::fatalError($message, $params);

        $message = "Fatal error in file %s.";
        $params = 'error_file.php';

        $this->expectOutputRegex("/Fatal error in file error_file.php./");
        Core::fatalError($message, $params);
    }

    /**
     * Test for Core::getRealSize
     *
     * @param string $size     Size
     * @param int    $expected Expected value
     *
     * @return void
     *
     * @dataProvider providerTestGetRealSize
     */
    public function testGetRealSize($size, $expected)
    {
        $this->assertEquals($expected, Core::getRealSize($size));
    }

    /**
     * Data provider for testGetRealSize
     *
     * @return array
     */
    public function providerTestGetRealSize()
    {
        return array(
            array('0', 0),
            array('1kb', 1024),
            array('1024k', 1024 * 1024),
            array('8m', 8 * 1024 * 1024),
            array('12gb', 12 * 1024 * 1024 * 1024),
            array('1024', 1024),
        );
    }

    /**
     * Test for Core::getPHPDocLink
     *
     * @return void
     */
    public function testGetPHPDocLink()
    {
        $lang = _pgettext('PHP documentation language', 'en');
        $this->assertEquals(
            Core::getPHPDocLink('function'),
            './url.php?url=https%3A%2F%2Fsecure.php.net%2Fmanual%2F'
            . $lang . '%2Ffunction'
        );
    }

    /**
     * Test for Core::linkURL
     *
     * @param string $link URL where to go
     * @param string $url  Expected value
     *
     * @return void
     *
     * @dataProvider providerTestLinkURL
     */
    public function testLinkURL($link, $url)
    {
        $this->assertEquals(Core::linkURL($link), $url);
    }

    /**
     * Data provider for testLinkURL
     *
     * @return array
     */
    public function providerTestLinkURL()
    {
        return array(
            array('https://wiki.phpmyadmin.net',
             './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net'),
            array('https://wiki.phpmyadmin.net',
             './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net'),
            array('wiki.phpmyadmin.net', 'wiki.phpmyadmin.net'),
            array('index.php?db=phpmyadmin', 'index.php?db=phpmyadmin')
        );
    }

    /**
     * Test for Core::sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithoutSidWithIis()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', true);

        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        Core::sendHeaderLocation($testUri); // sets $GLOBALS['header']

        $this->tearDown();

        $this->mockResponse('Refresh: 0; ' . $testUri);
        Core::sendHeaderLocation($testUri, true); // sets $GLOBALS['header']
    }

    /**
     * Test for Core::sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithoutSidWithoutIis()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', null);

        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        Core::sendHeaderLocation($testUri);            // sets $GLOBALS['header']
    }

    /**
     * Test for Core::sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationIisLongUri()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', true);

        // over 600 chars
        $testUri = 'https://example.com/test.php?testlonguri=over600chars&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test';
        $testUri_html = htmlspecialchars($testUri);
        $testUri_js = Sanitize::escapeJsString($testUri);

        $header = "<html>\n<head>\n    <title>- - -</title>
    <meta http-equiv=\"expires\" content=\"0\" />"
            . "\n    <meta http-equiv=\"Pragma\" content=\"no-cache\" />"
            . "\n    <meta http-equiv=\"Cache-Control\" content=\"no-cache\" />"
            . "\n    <meta http-equiv=\"Refresh\" content=\"0;url=" . $testUri_html . "\" />"
            . "\n    <script type=\"text/javascript\">\n        //<![CDATA[
        setTimeout(function() { window.location = decodeURI('" . $testUri_js . "'); }, 2000);
        //]]>\n    </script>\n</head>
<body>\n<script type=\"text/javascript\">\n    //<![CDATA[
    document.write('<p><a href=\"" . $testUri_html . "\">" . __('Go') . "</a></p>');
    //]]>\n</script>\n</body>\n</html>
";

        $this->expectOutputString($header);

        $this->mockResponse();

        Core::sendHeaderLocation($testUri);
    }

    /**
     * Test for Core::ifSetOr
     *
     * @return void
     */
    public function testVarSet()
    {
        $default = 'foo';
        $in = 'bar';
        $out = Core::ifSetOr($in, $default);
        $this->assertEquals($in, $out);
    }

    /**
     * Test for Core::ifSetOr
     *
     * @return void
     */
    public function testVarSetWrongType()
    {
        $default = 'foo';
        $in = 'bar';
        $out = Core::ifSetOr($in, $default, 'boolean');
        $this->assertEquals($out, $default);
    }

    /**
     * Test for Core::ifSetOr
     *
     * @return void
     */
    public function testVarNotSet()
    {
        $default = 'foo';
        // $in is not set!
        $out = Core::ifSetOr($in, $default);
        $this->assertEquals($out, $default);
    }

    /**
     * Test for Core::ifSetOr
     *
     * @return void
     */
    public function testVarNotSetNoDefault()
    {
        // $in is not set!
        $out = Core::ifSetOr($in);
        $this->assertNull($out);
    }

    /**
     * Test for unserializing
     *
     * @param string $url      URL to test
     * @param mixed  $expected Expected result
     *
     * @return void
     *
     * @dataProvider provideTestIsAllowedDomain
     */
    function testIsAllowedDomain($url, $expected)
    {
        $_SERVER['SERVER_NAME'] = 'server.local';
        $this->assertEquals(
            $expected,
            Core::isAllowedDomain($url)
        );
    }

    /**
     * Test data provider
     *
     * @return array
     */
    function provideTestIsAllowedDomain()
    {
        return array(
            array('https://www.phpmyadmin.net/', true),
            array('http://duckduckgo.com\\@github.com', false),
            array('https://github.com/', true),
            array('https://github.com:123/', false),
            array('https://user:pass@github.com:123/', false),
            array('https://user:pass@github.com/', false),
            array('https://server.local/', true),
            array('./relative/', false),
        );
    }

    /**
     * Test for Core::isValid
     *
     * @param mixed $var     Variable to check
     * @param mixed $type    Type
     * @param mixed $compare Compared value
     *
     * @return void
     *
     * @dataProvider providerTestNoVarType
     */
    public function testNoVarType($var, $type, $compare)
    {
        $this->assertTrue(Core::isValid($var, $type, $compare));
    }

    /**
     * Data provider for testNoVarType
     *
     * @return array
     */
    public static function providerTestNoVarType()
    {
        return array(
            array(0, false, 0),
            array(0, false, 1),
            array(1, false, null),
            array(1.1, false, null),
            array('', false, null),
            array(' ', false, null),
            array('0', false, null),
            array('string', false, null),
            array(array(), false, null),
            array(array(1, 2, 3), false, null),
            array(true, false, null),
            array(false, false, null));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testVarNotSetAfterTest()
    {
        Core::isValid($var);
        $this->assertFalse(isset($var));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNotSet()
    {
        $this->assertFalse(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testEmptyString()
    {
        $var = '';
        $this->assertFalse(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNotEmptyString()
    {
        $var = '0';
        $this->assertTrue(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testZero()
    {
        $var = 0;
        $this->assertTrue(Core::isValid($var));
        $this->assertTrue(Core::isValid($var, 'int'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNullFail()
    {
        $var = null;
        $this->assertFalse(Core::isValid($var));

        $var = 'null_text';
        $this->assertFalse(Core::isValid($var, 'null'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNotSetArray()
    {
        /** @var $array undefined array */
        $this->assertFalse(Core::isValid($array['x']));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testScalarString()
    {
        $var = 'string';
        $this->assertTrue(Core::isValid($var, 'len'));
        $this->assertTrue(Core::isValid($var, 'scalar'));
        $this->assertTrue(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testScalarInt()
    {
        $var = 1;
        $this->assertTrue(Core::isValid($var, 'int'));
        $this->assertTrue(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testScalarFloat()
    {
        $var = 1.1;
        $this->assertTrue(Core::isValid($var, 'float'));
        $this->assertTrue(Core::isValid($var, 'double'));
        $this->assertTrue(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testScalarBool()
    {
        $var = true;
        $this->assertTrue(Core::isValid($var, 'scalar'));
        $this->assertTrue(Core::isValid($var, 'bool'));
        $this->assertTrue(Core::isValid($var, 'boolean'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNotScalarArray()
    {
        $var = array('test');
        $this->assertFalse(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNotScalarNull()
    {
        $var = null;
        $this->assertFalse(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNumericInt()
    {
        $var = 1;
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNumericFloat()
    {
        $var = 1.1;
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNumericZero()
    {
        $var = 0;
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNumericString()
    {
        $var = '+0.1';
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testValueInArray()
    {
        $var = 'a';
        $this->assertTrue(Core::isValid($var, array('a', 'b',)));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testValueNotInArray()
    {
        $var = 'c';
        $this->assertFalse(Core::isValid($var, array('a', 'b',)));
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testNumericIdentical()
    {
        $var = 1;
        $compare = 1;
        $this->assertTrue(Core::isValid($var, 'identic', $compare));

        $var = 1;
        $compare += 2;
        $this->assertFalse(Core::isValid($var, 'identic', $compare));

        $var = 1;
        $compare = '1';
        $this->assertFalse(Core::isValid($var, 'identic', $compare));
    }


    /**
     * Test for Core::isValid
     *
     * @param mixed $var     Variable
     * @param mixed $compare Compare
     *
     * @return void
     *
     * @dataProvider provideTestSimilarType
     */
    public function testSimilarType($var, $compare)
    {
        $this->assertTrue(Core::isValid($var, 'similar', $compare));
        $this->assertTrue(Core::isValid($var, 'equal', $compare));
        $this->assertTrue(Core::isValid($compare, 'similar', $var));
        $this->assertTrue(Core::isValid($compare, 'equal', $var));
    }

    /**
     * Data provider for testSimilarType
     *
     * @return array
     */
    public function provideTestSimilarType()
    {
        return array(
            array(1, 1),
            array(1.5, 1.5),
            array(true, true),
            array('string', "string"),
            array(array(1, 2, 3.4), array(1, 2, 3.4)),
            array(array(1, '2', '3.4', 5, 'text'), array('1', '2', 3.4,'5'))
        );
    }

    /**
     * Test for Core::isValid
     *
     * @return void
     */
    public function testOtherTypes()
    {
        $var = new CoreTest();
        $this->assertFalse(Core::isValid($var, 'class'));
    }

    /**
     * Test for unserializing
     *
     * @param string $data     Serialized data
     * @param mixed  $expected Expected result
     *
     * @return void
     *
     * @dataProvider provideTestSafeUnserialize
     */
    function testSafeUnserialize($data, $expected)
    {
        $this->assertEquals(
            $expected,
            Core::safeUnserialize($data)
        );
    }

    /**
     * Test data provider
     *
     * @return array
     */
    function provideTestSafeUnserialize()
    {
        return array(
            array('s:6:"foobar";', 'foobar'),
            array('foobar', null),
            array('b:0;', false),
            array('O:1:"a":1:{s:5:"value";s:3:"100";}', null),
            array('O:8:"stdClass":1:{s:5:"field";O:8:"stdClass":0:{}}', null),
            array('a:2:{i:0;s:90:"1234567890;a345678901234567890123456789012345678901234567890123456789012345678901234567890";i:1;O:8:"stdClass":0:{}}', null),
            array(serialize(array(1, 2, 3)), array(1, 2, 3)),
            array(serialize('string""'), 'string""'),
            array(serialize(array('foo' => 'bar')), array('foo' => 'bar')),
            array(serialize(array('1', new stdClass(), '2')), null),
        );
    }

    /**
     * Test for MySQL host sanitizing
     *
     * @param string $host     Test host name
     * @param string $expected Expected result
     *
     * @return void
     *
     * @dataProvider provideTestSanitizeMySQLHost
     */
    function testSanitizeMySQLHost($host, $expected)
    {
        $this->assertEquals(
            $expected,
            Core::sanitizeMySQLHost($host)
        );
    }

    /**
     * Test data provider
     *
     * @return array
     */
    function provideTestSanitizeMySQLHost()
    {
        return array(
            array('p:foo.bar', 'foo.bar'),
            array('p:p:foo.bar', 'foo.bar'),
            array('bar.baz', 'bar.baz'),
            array('P:example.com', 'example.com'),
        );
    }

    /**
     * Test for replacing dots.
     *
     * @return void
     */
    public function testReplaceDots()
    {
        $this->assertEquals(
            Core::securePath('../../../etc/passwd'),
            './././etc/passwd'
        );
        $this->assertEquals(
            Core::securePath('/var/www/../phpmyadmin'),
            '/var/www/./phpmyadmin'
        );
        $this->assertEquals(
            Core::securePath('./path/with..dots/../../file..php'),
            './path/with.dots/././file.php'
        );
    }

    /**
     * Test for Core::warnMissingExtension
     *
     * @return void
     */
    function testMissingExtensionFatal()
    {
        $ext = 'php_ext';
        $warn = 'The <a href="' . Core::getPHPDocLink('book.' . $ext . '.php')
            . '" target="Documentation"><em>' . $ext
            . '</em></a> extension is missing. Please check your PHP configuration.';

        $this->expectOutputRegex('@' . preg_quote($warn, '@') . '@');

        Core::warnMissingExtension($ext, true);
    }

    /**
     * Test for Core::warnMissingExtension
     *
     * @return void
     */
    function testMissingExtensionFatalWithExtra()
    {
        $ext = 'php_ext';
        $extra = 'Appended Extra String';

        $warn = 'The <a href="' . Core::getPHPDocLink('book.' . $ext . '.php')
            . '" target="Documentation"><em>' . $ext
            . '</em></a> extension is missing. Please check your PHP configuration.'
            . ' ' . $extra;

        ob_start();
        Core::warnMissingExtension($ext, true, $extra);
        $printed = ob_get_contents();
        ob_end_clean();

        $this->assertGreaterThan(0, mb_strpos($printed, $warn));
    }
}
