<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\Sanitize;
use stdClass;
use function hash;
use function htmlspecialchars;
use function mb_strpos;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function preg_quote;
use function serialize;

class CoreTest extends AbstractNetworkTestCase
{
    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        parent::setLanguage();
        parent::loadDefaultConfig();

        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'http://example.net/';
    }

    /**
     * Test for Core::arrayRead
     */
    public function testArrayRead(): void
    {
        $arr = [
            'int' => 1,
            'str' => 'str_val',
            'arr' => [
                'val1',
                'val2',
                'val3',
            ],
            'sarr' => [
                'arr1' => [
                    1,
                    2,
                    3,
                ],
                [
                    3,
                    [
                        'a',
                        'b',
                        'c',
                    ],
                    4,
                ],
            ],
        ];

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
     */
    public function testArrayWrite(): void
    {
        $arr = [
            'int' => 1,
            'str' => 'str_val',
            'arr' => [
                'val1',
                'val2',
                'val3',
            ],
            'sarr' => [
                'arr1' => [
                    1,
                    2,
                    3,
                ],
                [
                    3,
                    [
                        'a',
                        'b',
                        'c',
                    ],
                    4,
                ],
            ],
        ];

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
     */
    public function testArrayRemove(): void
    {
        $arr = [
            'int' => 1,
            'str' => 'str_val',
            'arr' => [
                'val1',
                'val2',
                'val3',
            ],
            'sarr' => [
                'arr1' => [
                    1,
                    2,
                    3,
                ],
                [
                    3,
                    [
                        'a',
                        'b',
                        'c',
                    ],
                    4,
                ],
            ],
        ];

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
     * @param string|null $page      Page
     * @param array       $allowList Allow list
     * @param bool        $include   whether the page is going to be included
     * @param bool        $expected  Expected value
     *
     * @dataProvider providerTestGotoNowhere
     */
    public function testGotoNowhere(?string $page, array $allowList, bool $include, bool $expected): void
    {
        $this->assertSame($expected, Core::checkPageValidity($page, $allowList, $include));
    }

    /**
     * Data provider for testGotoNowhere
     *
     * @return array
     */
    public function providerTestGotoNowhere(): array
    {
        return [
            [
                null,
                [],
                false,
                false,
            ],
            [
                null,
                [],
                true,
                false,
            ],
            [
                'shell.php',
                ['index.php'],
                false,
                false,
            ],
            [
                'shell.php',
                ['index.php'],
                true,
                false,
            ],
            [
                'index.php?sql.php&test=true',
                ['index.php'],
                false,
                true,
            ],
            [
                'index.php?sql.php&test=true',
                ['index.php'],
                true,
                false,
            ],
            [
                'index.php%3Fsql.php%26test%3Dtrue',
                ['index.php'],
                false,
                true,
            ],
            [
                'index.php%3Fsql.php%26test%3Dtrue',
                ['index.php'],
                true,
                false,
            ],
        ];
    }

    /**
     * Test for Core::cleanupPathInfo
     *
     * @param string $php_self  The PHP_SELF value
     * @param string $request   The REQUEST_URI value
     * @param string $path_info The PATH_INFO value
     * @param string $expected  Expected result
     *
     * @dataProvider providerTestPathInfo
     */
    public function testPathInfo(string $php_self, string $request, string $path_info, string $expected): void
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
    public function providerTestPathInfo(): array
    {
        return [
            [
                '/phpmyadmin/index.php/; cookieinj=value/',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '//example.com/../phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '//example.com/../../.././phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '/page.php/malicouspathinfo?malicouspathinfo',
                'malicouspathinfo',
                '/page.php',
            ],
            [
                '/phpmyadmin/./index.php',
                '/phpmyadmin/./index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '/phpmyadmin/index.php',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
        ];
    }

    /**
     * Test for Core::fatalError
     */
    public function testFatalErrorMessage(): void
    {
        $this->expectOutputRegex('/FatalError!/');
        Core::fatalError('FatalError!');
    }

    /**
     * Test for Core::fatalError
     */
    public function testFatalErrorMessageWithArgs(): void
    {
        $message = 'Fatal error #%d in file %s.';
        $params = [
            1,
            'error_file.php',
        ];

        $this->expectOutputRegex('/Fatal error #1 in file error_file.php./');
        Core::fatalError($message, $params);

        $message = 'Fatal error in file %s.';
        $params = 'error_file.php';

        $this->expectOutputRegex('/Fatal error in file error_file.php./');
        Core::fatalError($message, $params);
    }

    /**
     * Test for Core::getRealSize
     *
     * @param string $size     Size
     * @param int    $expected Expected value
     *
     * @group 32bit-incompatible
     *
     * @dataProvider providerTestGetRealSize
     */
    public function testGetRealSize(string $size, int $expected): void
    {
        $this->assertEquals($expected, Core::getRealSize($size));
    }

    /**
     * Data provider for testGetRealSize
     *
     * @return array
     */
    public function providerTestGetRealSize(): array
    {
        return [
            [
                '0',
                0,
            ],
            [
                '1kb',
                1024,
            ],
            [
                '1024k',
                1024 * 1024,
            ],
            [
                '8m',
                8 * 1024 * 1024,
            ],
            [
                '12gb',
                12 * 1024 * 1024 * 1024,
            ],
            [
                '1024',
                1024,
            ],
            [
                '8000m',
                8 * 1000 * 1024 * 1024,
            ],
            [
                '8G',
                8 * 1024 * 1024 * 1024,
            ],
        ];
    }

    /**
     * Test for Core::getPHPDocLink
     */
    public function testGetPHPDocLink(): void
    {
        $lang = _pgettext('PHP documentation language', 'en');
        $this->assertEquals(
            Core::getPHPDocLink('function'),
            './url.php?url=https%3A%2F%2Fwww.php.net%2Fmanual%2F'
            . $lang . '%2Ffunction'
        );
    }

    /**
     * Test for Core::linkURL
     *
     * @param string $link URL where to go
     * @param string $url  Expected value
     *
     * @dataProvider providerTestLinkURL
     */
    public function testLinkURL(string $link, string $url): void
    {
        $this->assertEquals(Core::linkURL($link), $url);
    }

    /**
     * Data provider for testLinkURL
     *
     * @return array
     */
    public function providerTestLinkURL(): array
    {
        return [
            [
                'https://wiki.phpmyadmin.net',
                './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net',
            ],
            [
                'https://wiki.phpmyadmin.net',
                './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net',
            ],
            [
                'wiki.phpmyadmin.net',
                'wiki.phpmyadmin.net',
            ],
            [
                'index.php?db=phpmyadmin',
                'index.php?db=phpmyadmin',
            ],
        ];
    }

    /**
     * Test for Core::sendHeaderLocation
     */
    public function testSendHeaderLocationWithoutSidWithIis(): void
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', true);

        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        Core::sendHeaderLocation($testUri); // sets $GLOBALS['header']

        $this->mockResponse('Refresh: 0; ' . $testUri);
        Core::sendHeaderLocation($testUri, true); // sets $GLOBALS['header']
    }

    /**
     * Test for Core::sendHeaderLocation
     */
    public function testSendHeaderLocationWithoutSidWithoutIis(): void
    {
        $GLOBALS['server'] = 0;
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', null);

        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        Core::sendHeaderLocation($testUri);            // sets $GLOBALS['header']
    }

    /**
     * Test for Core::sendHeaderLocation
     */
    public function testSendHeaderLocationIisLongUri(): void
    {
        $GLOBALS['server'] = 0;
        parent::setGlobalConfig();
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

        $header = "<html>\n<head>\n    <title>- - -</title>"
            . "\n    <meta http-equiv=\"expires\" content=\"0\">"
            . "\n    <meta http-equiv=\"Pragma\" content=\"no-cache\">"
            . "\n    <meta http-equiv=\"Cache-Control\" content=\"no-cache\">"
            . "\n    <meta http-equiv=\"Refresh\" content=\"0;url=" . $testUri_html . '">'
            . "\n    <script type=\"text/javascript\">\n        //<![CDATA["
            . "\n        setTimeout(function() { window.location = decodeURI('" . $testUri_js . "'); }, 2000);"
            . "\n        //]]>\n    </script>\n</head>"
            . "\n<body>\n<script type=\"text/javascript\">\n    //<![CDATA["
            . "\n    document.write('<p><a href=\"" . $testUri_html . '">' . __('Go') . "</a></p>');"
            . "\n    //]]>\n</script>\n</body>\n</html>\n";

        $this->expectOutputString($header);

        $this->mockResponse();

        Core::sendHeaderLocation($testUri);
    }

    /**
     * Test for Core::ifSetOr
     */
    public function testVarSet(): void
    {
        $default = 'foo';
        $in = 'bar';
        $out = Core::ifSetOr($in, $default);
        $this->assertEquals($in, $out);
    }

    /**
     * Test for Core::ifSetOr
     */
    public function testVarSetWrongType(): void
    {
        $default = 'foo';
        $in = 'bar';
        $out = Core::ifSetOr($in, $default, 'boolean');
        $this->assertEquals($out, $default);
    }

    /**
     * Test for Core::ifSetOr
     */
    public function testVarNotSet(): void
    {
        $default = 'foo';
        // $in is not set!
        $out = Core::ifSetOr($in, $default);
        $this->assertEquals($out, $default);
    }

    /**
     * Test for Core::ifSetOr
     */
    public function testVarNotSetNoDefault(): void
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
     * @dataProvider provideTestIsAllowedDomain
     */
    public function testIsAllowedDomain(string $url, $expected): void
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
    public function provideTestIsAllowedDomain(): array
    {
        return [
            [
                'https://www.phpmyadmin.net/',
                true,
            ],
            [
                'http://duckduckgo.com\\@github.com',
                false,
            ],
            [
                'https://github.com/',
                true,
            ],
            [
                'https://github.com:123/',
                false,
            ],
            [
                'https://user:pass@github.com:123/',
                false,
            ],
            [
                'https://user:pass@github.com/',
                false,
            ],
            [
                'https://server.local/',
                true,
            ],
            [
                './relative/',
                false,
            ],
        ];
    }

    /**
     * Test for Core::isValid
     *
     * @param mixed $var     Variable to check
     * @param mixed $type    Type
     * @param mixed $compare Compared value
     *
     * @dataProvider providerTestNoVarType
     */
    public function testNoVarType($var, $type, $compare): void
    {
        $this->assertTrue(Core::isValid($var, $type, $compare));
    }

    /**
     * Data provider for testNoVarType
     *
     * @return array
     */
    public static function providerTestNoVarType(): array
    {
        return [
            [
                0,
                false,
                0,
            ],
            [
                0,
                false,
                1,
            ],
            [
                1,
                false,
                null,
            ],
            [
                1.1,
                false,
                null,
            ],
            [
                '',
                false,
                null,
            ],
            [
                ' ',
                false,
                null,
            ],
            [
                '0',
                false,
                null,
            ],
            [
                'string',
                false,
                null,
            ],
            [
                [],
                false,
                null,
            ],
            [
                [
                    1,
                    2,
                    3,
                ],
                false,
                null,
            ],
            [
                true,
                false,
                null,
            ],
            [
                false,
                false,
                null,
            ],
        ];
    }

    /**
     * Test for Core::isValid
     */
    public function testVarNotSetAfterTest(): void
    {
        Core::isValid($var);
        $this->assertFalse(isset($var));
    }

    /**
     * Test for Core::isValid
     */
    public function testNotSet(): void
    {
        $this->assertFalse(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     */
    public function testEmptyString(): void
    {
        $var = '';
        $this->assertFalse(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     */
    public function testNotEmptyString(): void
    {
        $var = '0';
        $this->assertTrue(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     */
    public function testZero(): void
    {
        $var = 0;
        $this->assertTrue(Core::isValid($var));
        $this->assertTrue(Core::isValid($var, 'int'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNullFail(): void
    {
        $var = null;
        $this->assertFalse(Core::isValid($var));

        $var = 'null_text';
        $this->assertFalse(Core::isValid($var, 'null'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNotSetArray(): void
    {
        $array = ['x' => null];
        $this->assertFalse(Core::isValid($array['x']));
    }

    /**
     * Test for Core::isValid
     */
    public function testScalarString(): void
    {
        $var = 'string';
        $this->assertTrue(Core::isValid($var, 'len'));
        $this->assertTrue(Core::isValid($var, 'scalar'));
        $this->assertTrue(Core::isValid($var));
    }

    /**
     * Test for Core::isValid
     */
    public function testScalarInt(): void
    {
        $var = 1;
        $this->assertTrue(Core::isValid($var, 'int'));
        $this->assertTrue(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     */
    public function testScalarFloat(): void
    {
        $var = 1.1;
        $this->assertTrue(Core::isValid($var, 'float'));
        $this->assertTrue(Core::isValid($var, 'double'));
        $this->assertTrue(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     */
    public function testScalarBool(): void
    {
        $var = true;
        $this->assertTrue(Core::isValid($var, 'scalar'));
        $this->assertTrue(Core::isValid($var, 'bool'));
        $this->assertTrue(Core::isValid($var, 'boolean'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNotScalarArray(): void
    {
        $var = ['test'];
        $this->assertFalse(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNotScalarNull(): void
    {
        $var = null;
        $this->assertFalse(Core::isValid($var, 'scalar'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNumericInt(): void
    {
        $var = 1;
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNumericFloat(): void
    {
        $var = 1.1;
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNumericZero(): void
    {
        $var = 0;
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     */
    public function testNumericString(): void
    {
        $var = '+0.1';
        $this->assertTrue(Core::isValid($var, 'numeric'));
    }

    /**
     * Test for Core::isValid
     */
    public function testValueInArray(): void
    {
        $var = 'a';
        $this->assertTrue(Core::isValid($var, ['a', 'b']));
    }

    /**
     * Test for Core::isValid
     */
    public function testValueNotInArray(): void
    {
        $var = 'c';
        $this->assertFalse(Core::isValid($var, ['a', 'b']));
    }

    /**
     * Test for Core::isValid
     */
    public function testNumericIdentical(): void
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
     * @dataProvider provideTestSimilarType
     */
    public function testSimilarType($var, $compare): void
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
    public function provideTestSimilarType(): array
    {
        return [
            [
                1,
                1,
            ],
            [
                1.5,
                1.5,
            ],
            [
                true,
                true,
            ],
            [
                'string',
                'string',
            ],
            [
                [
                    1,
                    2,
                    3.4,
                ],
                [
                    1,
                    2,
                    3.4,
                ],
            ],
            [
                [
                    1,
                    '2',
                    '3.4',
                    5,
                    'text',
                ],
                [
                    '1',
                    '2',
                    3.4,
                    '5',
                ],
            ],
        ];
    }

    /**
     * Test for Core::isValid
     */
    public function testOtherTypes(): void
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
     * @dataProvider provideTestSafeUnserialize
     */
    public function testSafeUnserialize(string $data, $expected): void
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
    public function provideTestSafeUnserialize(): array
    {
        return [
            [
                's:6:"foobar";',
                'foobar',
            ],
            [
                'foobar',
                null,
            ],
            [
                'b:0;',
                false,
            ],
            [
                'O:1:"a":1:{s:5:"value";s:3:"100";}',
                null,
            ],
            [
                'O:8:"stdClass":1:{s:5:"field";O:8:"stdClass":0:{}}',
                null,
            ],
            [
                'a:2:{i:0;s:90:"1234567890;a3456789012345678901234567890123456789012'
                . '34567890123456789012345678901234567890";i:1;O:8:"stdClass":0:{}}',
                null,
            ],
            [
                serialize([1, 2, 3]),
                [
                    1,
                    2,
                    3,
                ],
            ],
            [
                serialize('string""'),
                'string""',
            ],
            [
                serialize(['foo' => 'bar']),
                ['foo' => 'bar'],
            ],
            [
                serialize(['1', new stdClass(), '2']),
                null,
            ],
        ];
    }

    /**
     * Test for MySQL host sanitizing
     *
     * @param string $host     Test host name
     * @param string $expected Expected result
     *
     * @dataProvider provideTestSanitizeMySQLHost
     */
    public function testSanitizeMySQLHost(string $host, string $expected): void
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
    public function provideTestSanitizeMySQLHost(): array
    {
        return [
            [
                'p:foo.bar',
                'foo.bar',
            ],
            [
                'p:p:foo.bar',
                'foo.bar',
            ],
            [
                'bar.baz',
                'bar.baz',
            ],
            [
                'P:example.com',
                'example.com',
            ],
        ];
    }

    /**
     * Test for replacing dots.
     */
    public function testReplaceDots(): void
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
     */
    public function testMissingExtensionFatal(): void
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
     */
    public function testMissingExtensionFatalWithExtra(): void
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

        $this->assertGreaterThan(0, mb_strpos((string) $printed, $warn));
    }

    /**
     * Test for Core::signSqlQuery
     */
    public function testSignSqlQuery(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $signature = Core::signSqlQuery($sqlQuery);
        $hmac = '33371e8680a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3a4ffe2';
        $this->assertSame($hmac, $signature, 'The signature must match the computed one');
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignature(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '33371e8680a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3a4ffe2';
        $this->assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFails(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', '132654987gguieunofz');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '33371e8680a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3a4ffe2';
        $this->assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsBadHash(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '3333333380a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3eeeeee';
        $this->assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsNoSession(): void
    {
        $_SESSION[' HMAC_secret '] = 'empty';
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '3333333380a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3eeeeee';
        $this->assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsFromAnotherSession(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'firstSession');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = Core::signSqlQuery($sqlQuery);
        $this->assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
        $_SESSION[' HMAC_secret '] = hash('sha1', 'secondSession');
        // Try to use the token (hmac) from the previous session
        $this->assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsBlowfishSecretChanged(): void
    {
        $GLOBALS['cfg']['blowfish_secret'] = '';
        $_SESSION[' HMAC_secret '] = hash('sha1', 'firstSession');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = Core::signSqlQuery($sqlQuery);
        $this->assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
        $GLOBALS['cfg']['blowfish_secret'] = '32154987zd';
        // Try to use the previous HMAC signature
        $this->assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));

        $GLOBALS['cfg']['blowfish_secret'] = '32154987zd';
        // Generate the HMAC signature to check that it works
        $hmac = Core::signSqlQuery($sqlQuery);
        // Must work now, (good secret and blowfish_secret)
        $this->assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    public function testCheckTokenRequestParam(): void
    {
        global $token_mismatch, $token_provided;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        Core::checkTokenRequestParam();
        $this->assertTrue($token_mismatch);
        $this->assertFalse($token_provided);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['test'] = 'test';
        Core::checkTokenRequestParam();
        $this->assertTrue($token_mismatch);
        $this->assertFalse($token_provided);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'mismatch';
        Core::checkTokenRequestParam();
        $this->assertTrue($token_mismatch);
        $this->assertTrue($token_provided);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        Core::checkTokenRequestParam();
        $this->assertFalse($token_mismatch);
        $this->assertTrue($token_provided);
        $this->assertArrayHasKey('test', $_POST);
        $this->assertEquals('test', $_POST['test']);
    }
}
