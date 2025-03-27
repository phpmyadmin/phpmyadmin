<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Url;
use stdClass;

use function __;
use function _pgettext;
use function hash;
use function header;
use function htmlspecialchars;
use function mb_strpos;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function preg_quote;
use function serialize;
use function str_repeat;

/**
 * @covers \PhpMyAdmin\Core
 */
class CoreTest extends AbstractNetworkTestCase
{
    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        parent::setLanguage();

        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'http://example.net/';
        $GLOBALS['config']->set('URLQueryEncryption', false);
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

        self::assertSame(Core::arrayRead('int', $arr), $arr['int']);

        self::assertSame(Core::arrayRead('str', $arr), $arr['str']);

        self::assertSame(Core::arrayRead('arr/0', $arr), $arr['arr'][0]);

        self::assertSame(Core::arrayRead('arr/1', $arr), $arr['arr'][1]);

        self::assertSame(Core::arrayRead('arr/2', $arr), $arr['arr'][2]);

        self::assertSame(Core::arrayRead('sarr/arr1/0', $arr), $arr['sarr']['arr1'][0]);

        self::assertSame(Core::arrayRead('sarr/arr1/1', $arr), $arr['sarr']['arr1'][1]);

        self::assertSame(Core::arrayRead('sarr/arr1/2', $arr), $arr['sarr']['arr1'][2]);

        self::assertSame(Core::arrayRead('sarr/0/0', $arr), $arr['sarr'][0][0]);

        self::assertSame(Core::arrayRead('sarr/0/1', $arr), $arr['sarr'][0][1]);

        self::assertSame(Core::arrayRead('sarr/0/1/2', $arr), $arr['sarr'][0][1][2]);

        self::assertSame(Core::arrayRead('sarr/not_exiting/1', $arr), null);

        self::assertSame(Core::arrayRead('sarr/not_exiting/1', $arr, 0), 0);

        self::assertSame(Core::arrayRead('sarr/not_exiting/1', $arr, 'default_val'), 'default_val');
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
        self::assertSame($arr['int'], 5);

        Core::arrayWrite('str', $arr, '_str');
        self::assertSame($arr['str'], '_str');

        Core::arrayWrite('arr/0', $arr, 'val_arr_0');
        self::assertSame($arr['arr'][0], 'val_arr_0');

        Core::arrayWrite('arr/1', $arr, 'val_arr_1');
        self::assertSame($arr['arr'][1], 'val_arr_1');

        Core::arrayWrite('arr/2', $arr, 'val_arr_2');
        self::assertSame($arr['arr'][2], 'val_arr_2');

        Core::arrayWrite('sarr/arr1/0', $arr, 'val_sarr_arr_0');
        self::assertSame($arr['sarr']['arr1'][0], 'val_sarr_arr_0');

        Core::arrayWrite('sarr/arr1/1', $arr, 'val_sarr_arr_1');
        self::assertSame($arr['sarr']['arr1'][1], 'val_sarr_arr_1');

        Core::arrayWrite('sarr/arr1/2', $arr, 'val_sarr_arr_2');
        self::assertSame($arr['sarr']['arr1'][2], 'val_sarr_arr_2');

        Core::arrayWrite('sarr/0/0', $arr, 5);
        self::assertSame($arr['sarr'][0][0], 5);

        Core::arrayWrite('sarr/0/1/0', $arr, 'e');
        self::assertSame($arr['sarr'][0][1][0], 'e');

        Core::arrayWrite('sarr/not_existing/1', $arr, 'some_val');
        self::assertSame($arr['sarr']['not_existing'][1], 'some_val');

        Core::arrayWrite('sarr/0/2', $arr, null);
        self::assertNull($arr['sarr'][0][2]);
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
        self::assertArrayNotHasKey('int', $arr);

        Core::arrayRemove('str', $arr);
        self::assertArrayNotHasKey('str', $arr);

        Core::arrayRemove('arr/0', $arr);
        self::assertArrayNotHasKey(0, $arr['arr']);

        Core::arrayRemove('arr/1', $arr);
        self::assertArrayNotHasKey(1, $arr['arr']);

        Core::arrayRemove('arr/2', $arr);
        self::assertArrayNotHasKey('arr', $arr);

        $tmp_arr = $arr;
        Core::arrayRemove('sarr/not_existing/1', $arr);
        self::assertSame($tmp_arr, $arr);

        Core::arrayRemove('sarr/arr1/0', $arr);
        self::assertArrayNotHasKey(0, $arr['sarr']['arr1']);

        Core::arrayRemove('sarr/arr1/1', $arr);
        self::assertArrayNotHasKey(1, $arr['sarr']['arr1']);

        Core::arrayRemove('sarr/arr1/2', $arr);
        self::assertArrayNotHasKey('arr1', $arr['sarr']);

        Core::arrayRemove('sarr/0/0', $arr);
        self::assertArrayNotHasKey(0, $arr['sarr'][0]);

        Core::arrayRemove('sarr/0/1/0', $arr);
        self::assertArrayNotHasKey(0, $arr['sarr'][0][1]);

        Core::arrayRemove('sarr/0/1/1', $arr);
        self::assertArrayNotHasKey(1, $arr['sarr'][0][1]);

        Core::arrayRemove('sarr/0/1/2', $arr);
        self::assertArrayNotHasKey(1, $arr['sarr'][0]);

        Core::arrayRemove('sarr/0/2', $arr);

        self::assertEmpty($arr);
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
        self::assertSame($expected, Core::checkPageValidity($page, $allowList, $include));
    }

    /**
     * Data provider for testGotoNowhere
     *
     * @return array
     */
    public static function providerTestGotoNowhere(): array
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
     * Test for Core::fatalError
     */
    public function testFatalErrorMessage(): void
    {
        $_REQUEST = [];
        ResponseRenderer::getInstance()->setAjax(false);

        $this->expectOutputRegex('/FatalError!/');
        Core::fatalError('FatalError!');
    }

    /**
     * Test for Core::fatalError
     */
    public function testFatalErrorMessageWithArgs(): void
    {
        $_REQUEST = [];
        ResponseRenderer::getInstance()->setAjax(false);

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
        self::assertSame($expected, Core::getRealSize($size));
    }

    /**
     * Data provider for testGetRealSize
     *
     * @return array
     */
    public static function providerTestGetRealSize(): array
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
            [
                '2048',
                2048,
            ],
            [
                '2048K',
                2048 * 1024,
            ],
            [
                '2048K',
                2048 * 1024,
            ],
            [
                '102400K',
                102400 * 1024,
            ],
        ];
    }

    /**
     * Test for Core::getPHPDocLink
     */
    public function testGetPHPDocLink(): void
    {
        $lang = _pgettext('PHP documentation language', 'en');
        self::assertSame(Core::getPHPDocLink('function'), './url.php?url=https%3A%2F%2Fwww.php.net%2Fmanual%2F'
        . $lang . '%2Ffunction');
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
        self::assertSame(Core::linkURL($link), $url);
    }

    /**
     * Data provider for testLinkURL
     *
     * @return array
     */
    public static function providerTestLinkURL(): array
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
        $GLOBALS['config']->set('PMA_IS_IIS', true);

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
        $GLOBALS['config']->set('PMA_IS_IIS', null);

        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        Core::sendHeaderLocation($testUri); // sets $GLOBALS['header']
    }

    /**
     * Test for Core::sendHeaderLocation
     */
    public function testSendHeaderLocationIisLongUri(): void
    {
        $GLOBALS['server'] = 0;
        parent::setGlobalConfig();
        $GLOBALS['config']->set('PMA_IS_IIS', true);

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
        self::assertSame($expected, Core::isAllowedDomain($url));
    }

    /**
     * Test data provider
     *
     * @return array
     */
    public static function provideTestIsAllowedDomain(): array
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
     * Test for unserializing
     *
     * @param string $data     Serialized data
     * @param mixed  $expected Expected result
     *
     * @dataProvider provideTestSafeUnserialize
     */
    public function testSafeUnserialize(string $data, $expected): void
    {
        self::assertSame($expected, Core::safeUnserialize($data));
    }

    /**
     * Test data provider
     *
     * @return array
     */
    public static function provideTestSafeUnserialize(): array
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
        self::assertSame($expected, Core::sanitizeMySQLHost($host));
    }

    /**
     * Test data provider
     *
     * @return array
     */
    public static function provideTestSanitizeMySQLHost(): array
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
        self::assertSame(Core::securePath('../../../etc/passwd'), './././etc/passwd');
        self::assertSame(Core::securePath('/var/www/../phpmyadmin'), '/var/www/./phpmyadmin');
        self::assertSame(Core::securePath('./path/with..dots/../../file..php'), './path/with.dots/././file.php');
    }

    /**
     * Test for Core::warnMissingExtension
     */
    public function testMissingExtensionFatal(): void
    {
        $_REQUEST = [];
        ResponseRenderer::getInstance()->setAjax(false);

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
        $_REQUEST = [];
        ResponseRenderer::getInstance()->setAjax(false);

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

        self::assertGreaterThan(0, mb_strpos((string) $printed, $warn));
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
        self::assertSame($hmac, $signature, 'The signature must match the computed one');
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignature(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '33371e8680a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3a4ffe2';
        self::assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFails(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', '132654987gguieunofz');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '33371e8680a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3a4ffe2';
        self::assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsBadHash(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '3333333380a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3eeeeee';
        self::assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsNoSession(): void
    {
        $_SESSION[' HMAC_secret '] = 'empty';
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = '3333333380a640dc05944a2a24e6e630d3e9e3dba24464135f2fb954c3eeeeee';
        self::assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    /**
     * Test for Core::checkSqlQuerySignature
     */
    public function testCheckSqlQuerySignatureFailsFromAnotherSession(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'firstSession');
        $sqlQuery = 'SELECT * FROM `test`.`db` WHERE 1;';
        $hmac = Core::signSqlQuery($sqlQuery);
        self::assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
        $_SESSION[' HMAC_secret '] = hash('sha1', 'secondSession');
        // Try to use the token (hmac) from the previous session
        self::assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));
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
        self::assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
        $GLOBALS['cfg']['blowfish_secret'] = str_repeat('a', 32);
        // Try to use the previous HMAC signature
        self::assertFalse(Core::checkSqlQuerySignature($sqlQuery, $hmac));

        $GLOBALS['cfg']['blowfish_secret'] = str_repeat('a', 32);
        // Generate the HMAC signature to check that it works
        $hmac = Core::signSqlQuery($sqlQuery);
        // Must work now, (good secret and blowfish_secret)
        self::assertTrue(Core::checkSqlQuerySignature($sqlQuery, $hmac));
    }

    public function testPopulateRequestWithEncryptedQueryParams(): void
    {
        global $config;

        $_SESSION = [];
        $config->set('URLQueryEncryption', true);
        $config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $_GET = ['pos' => '0', 'eq' => Url::encryptQuery('{"db":"test_db","table":"test_table"}')];
        $_REQUEST = $_GET;

        $request = $this->createStub(ServerRequest::class);
        $request->method('getQueryParams')->willReturn($_GET);
        $request->method('getParsedBody')->willReturn(null);
        $request->method('withQueryParams')->willReturnSelf();
        $request->method('withParsedBody')->willReturnSelf();

        Core::populateRequestWithEncryptedQueryParams($request);

        $expected = ['pos' => '0', 'db' => 'test_db', 'table' => 'test_table'];

        self::assertSame($expected, $_GET);
        self::assertSame($expected, $_REQUEST);
    }

    /**
     * @param string[] $encrypted
     * @param string[] $decrypted
     *
     * @dataProvider providerForTestPopulateRequestWithEncryptedQueryParamsWithInvalidParam
     */
    public function testPopulateRequestWithEncryptedQueryParamsWithInvalidParam(
        array $encrypted,
        array $decrypted
    ): void {
        global $config;

        $_SESSION = [];
        $config->set('URLQueryEncryption', true);
        $config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $_GET = $encrypted;
        $_REQUEST = $encrypted;

        $request = $this->createStub(ServerRequest::class);
        $request->method('getQueryParams')->willReturn($_GET);
        $request->method('getParsedBody')->willReturn(null);
        $request->method('withQueryParams')->willReturnSelf();
        $request->method('withParsedBody')->willReturnSelf();

        Core::populateRequestWithEncryptedQueryParams($request);

        self::assertSame($decrypted, $_GET);
        self::assertSame($decrypted, $_REQUEST);
    }

    /**
     * @return array<int, array<int, array<string, string|mixed[]>>>
     */
    public static function providerForTestPopulateRequestWithEncryptedQueryParamsWithInvalidParam(): array
    {
        return [
            [[], []],
            [['eq' => []], []],
            [['eq' => ''], []],
            [['eq' => 'invalid'], []],
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @requires extension xdebug
     * @group ext-xdebug
     */
    public function testDownloadHeader(): void
    {
        $GLOBALS['config']->set('PMA_USR_BROWSER_AGENT', 'FIREFOX');

        header('Cache-Control: private, max-age=10800');

        Core::downloadHeader('test.sql', 'text/x-sql', 100, false);

        // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        $headersList = \xdebug_get_headers();
        // phpcs:enable

        self::assertContains('Cache-Control: private, max-age=10800', $headersList);
        self::assertContains('Content-Description: File Transfer', $headersList);
        self::assertContains('Content-Disposition: attachment; filename="test.sql"', $headersList);
        self::assertContains('Content-type: text/x-sql;charset=UTF-8', $headersList);
        self::assertContains('Content-Transfer-Encoding: binary', $headersList);
        self::assertContains('Content-Length: 100', $headersList);
        self::assertNotContains('Content-Encoding: gzip', $headersList);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @requires extension xdebug
     * @group ext-xdebug
     */
    public function testDownloadHeader2(): void
    {
        $GLOBALS['config']->set('PMA_USR_BROWSER_AGENT', 'FIREFOX');

        header('Cache-Control: private, max-age=10800');

        Core::downloadHeader('test.sql.gz', 'application/x-gzip', 0, false);

        // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        $headersList = \xdebug_get_headers();
        // phpcs:enable

        self::assertContains('Cache-Control: private, max-age=10800', $headersList);
        self::assertContains('Content-Description: File Transfer', $headersList);
        self::assertContains('Content-Disposition: attachment; filename="test.sql.gz"', $headersList);
        self::assertContains('Content-Type: application/x-gzip', $headersList);
        self::assertNotContains('Content-Encoding: gzip', $headersList);
        self::assertContains('Content-Transfer-Encoding: binary', $headersList);
        self::assertNotContains('Content-Length: 0', $headersList);
    }
}
