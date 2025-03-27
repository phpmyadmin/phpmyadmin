<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\DatabaseInterface;

use function array_merge;
use function array_replace_recursive;
use function define;
use function defined;
use function file_exists;
use function file_put_contents;
use function fileperms;
use function function_exists;
use function gd_info;
use function mb_strstr;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function phpinfo;
use function preg_match;
use function realpath;
use function strip_tags;
use function stristr;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const INFO_MODULES;
use const PHP_EOL;
use const PHP_OS;
use const TEST_PATH;

/**
 * @covers \PhpMyAdmin\Config
 */
class ConfigTest extends AbstractTestCase
{
    /** @var Config */
    protected $object;

    /** @var Config to test file permission */
    protected $permTestObj;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        $_SERVER['HTTP_USER_AGENT'] = '';
        $this->object = new Config();
        $GLOBALS['server'] = 0;
        $_SESSION['git_location'] = '.git';
        $_SESSION['is_git_revision'] = true;
        $GLOBALS['config'] = new Config(CONFIG_FILE);
        $GLOBALS['cfg']['ProxyUrl'] = '';

        //for testing file permissions
        $this->permTestObj = new Config(ROOT_PATH . 'config.sample.inc.php');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
        unset($this->permTestObj);
    }

    /**
     * Test for load
     */
    public function testLoadConfigs(): void
    {
        $defaultConfig = new Config();
        $tmpConfig = tempnam('./', 'config.test.inc.php');
        if ($tmpConfig === false) {
            $this->markTestSkipped('Creating a temporary file does not work');
        }

        self::assertFileExists($tmpConfig);

        // end of setup

        // Test loading an empty file does not change the default config
        $config = new Config($tmpConfig);
        self::assertSame($defaultConfig->settings, $config->settings);

        $contents = '<?php' . PHP_EOL
                    . '$cfg[\'ProtectBinary\'] = true;';
        file_put_contents($tmpConfig, $contents);

        // Test loading a config changes the setup
        $config = new Config($tmpConfig);
        $defaultConfig->settings['ProtectBinary'] = true;
        self::assertSame($defaultConfig->settings, $config->settings);
        $defaultConfig->settings['ProtectBinary'] = 'blob';

        // Teardown
        unlink($tmpConfig);
        self::assertFalse(file_exists($tmpConfig));
    }

    /**
     * Test for load
     */
    public function testLoadInvalidConfigs(): void
    {
        $defaultConfig = new Config();
        $tmpConfig = tempnam('./', 'config.test.inc.php');
        if ($tmpConfig === false) {
            $this->markTestSkipped('Creating a temporary file does not work');
        }

        self::assertFileExists($tmpConfig);

        // end of setup

        // Test loading an empty file does not change the default config
        $config = new Config($tmpConfig);
        self::assertSame($defaultConfig->settings, $config->settings);

        $contents = '<?php' . PHP_EOL
                    . '$cfg[\'fooBar\'] = true;';
        file_put_contents($tmpConfig, $contents);

        // Test loading a custom key config changes the setup
        $config = new Config($tmpConfig);
        $defaultConfig->settings['fooBar'] = true;
        // Equals because of the key sorting
        self::assertEquals($defaultConfig->settings, $config->settings);
        unset($defaultConfig->settings['fooBar']);

        $contents = '<?php' . PHP_EOL
                    . '$cfg[\'/InValidKey\'] = true;' . PHP_EOL
                    . '$cfg[\'In/ValidKey\'] = true;' . PHP_EOL
                    . '$cfg[\'/InValid/Key\'] = true;' . PHP_EOL
                    . '$cfg[\'In/Valid/Key\'] = true;' . PHP_EOL
                    . '$cfg[\'ValidKey\'] = true;';
        file_put_contents($tmpConfig, $contents);

        // Test loading a custom key config changes the setup
        $config = new Config($tmpConfig);
        $defaultConfig->settings['ValidKey'] = true;
        // Equals because of the key sorting
        self::assertEquals($defaultConfig->settings, $config->settings);
        unset($defaultConfig->settings['ValidKey']);

        // Teardown
        unlink($tmpConfig);
        self::assertFalse(file_exists($tmpConfig));
    }

    /**
     * Test for CheckSystem
     *
     * @group medium
     */
    public function testCheckSystem(): void
    {
        $this->object->checkSystem();

        self::assertIsBool($this->object->get('PMA_IS_WINDOWS'));
    }

    /**
     * Test for checkOutputCompression
     */
    public function testCheckOutputCompression(): void
    {
        $this->object->set('OBGzip', 'auto');

        $this->object->set('PMA_USR_BROWSER_AGENT', 'IE');
        $this->object->set('PMA_USR_BROWSER_VER', 6);
        $this->object->checkOutputCompression();
        self::assertTrue($this->object->get('OBGzip'));

        $this->object->set('OBGzip', 'auto');
        $this->object->set('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        $this->object->set('PMA_USR_BROWSER_VER', 5);
        $this->object->checkOutputCompression();
        self::assertTrue($this->object->get('OBGzip'));
    }

    /**
     * Tests client parsing code.
     *
     * @param string $agent   User agent string
     * @param string $os      Expected parsed OS (or null if none)
     * @param string $browser Expected parsed browser (or null if none)
     * @param string $version Expected browser version (or null if none)
     *
     * @dataProvider userAgentProvider
     */
    public function testCheckClient(string $agent, string $os, ?string $browser = null, ?string $version = null): void
    {
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $this->object->checkClient();
        self::assertEquals($os, $this->object->get('PMA_USR_OS'));
        if ($os != null) {
            self::assertEquals($browser, $this->object->get('PMA_USR_BROWSER_AGENT'));
        }

        if ($version == null) {
            return;
        }

        self::assertEquals($version, $this->object->get('PMA_USR_BROWSER_VER'));
    }

    /**
     * user Agent Provider
     *
     * @return array
     */
    public static function userAgentProvider(): array
    {
        return [
            [
                'Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00',
                'Linux',
                'OPERA',
                '9.80',
            ],
            [
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US) AppleWebKit/528.16 OmniWeb/622.8.0.112941',
                'Mac',
                'OMNIWEB',
                '622',
            ],
            [
                'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)',
                'Win',
                'IE',
                '8.0',
            ],
            [
                'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
                'Win',
                'IE',
                '9.0',
            ],
            [
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)',
                'Win',
                'IE',
                '10.0',
            ],
            [
                'Mozilla/5.0 (IE 11.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C; rv:11.0) like Gecko',
                'Win',
                'IE',
                '11.0',
            ],
            [
                'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; '
                . '.NET4.0C; .NET CLR 3.5.30729; .NET CLR 2.0.50727; '
                . '.NET CLR 3.0.30729; InfoPath.3; rv:11.0) like Gecko',
                'Win',
                'IE',
                '11.0',
            ],
            [
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, '
                . 'like Gecko) Chrome/25.0.1364.172 Safari/537.22',
                'Win',
                'CHROME',
                '25.0.1364.172',
            ],
            [
                'Mozilla/5.0 (Unknown; U; Unix BSD/SYSV system; C -) '
                . 'AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.10.2',
                'Unix',
                'SAFARI',
                '5.0.419',
            ],
            [
                'Mozilla/5.0 (Windows; U; Win95; en-US; rv:1.9b) Gecko/20031208',
                'Win',
                'GECKO',
                '1.9',
            ],
            [
                'Mozilla/5.0 (compatible; Konqueror/4.5; NetBSD 5.0.2; X11; amd64; en_US) KHTML/4.5.4 (like Gecko)',
                'Other',
                'KONQUEROR',
            ],
            [
                'Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0',
                'Linux',
                'FIREFOX',
                '5.0',
            ],
            [
                'Mozilla/5.0 (X11; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0',
                'Linux',
                'FIREFOX',
                '12.0',
            ],
            /**
             * @todo Is this version really expected?
             */
            [
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.4+ (KHTML, like G'
                . 'ecko) Version/5.0 Safari/535.4+ SUSE/12.1 (3.2.1) Epiphany/3.2.1',
                'Linux',
                'SAFARI',
                '5.0',
            ],
        ];
    }

    /**
     * test for CheckGd2
     */
    public function testCheckGd2(): void
    {
        $this->object->set('GD2Available', 'yes');
        $this->object->checkGd2();
        self::assertEquals(1, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available', 'no');
        $this->object->checkGd2();
        self::assertEquals(0, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available', 'auto');

        if (! function_exists('imagecreatetruecolor')) {
            $this->object->checkGd2();
            self::assertEquals(
                0,
                $this->object->get('PMA_IS_GD2'),
                'imagecreatetruecolor does not exist, PMA_IS_GD2 should be 0'
            );
        }

        if (function_exists('gd_info')) {
            $this->object->checkGd2();
            $gd_nfo = gd_info();
            if (mb_strstr($gd_nfo['GD Version'], '2.')) {
                self::assertEquals(1, $this->object->get('PMA_IS_GD2'), 'GD Version >= 2, PMA_IS_GD2 should be 1');
            } else {
                self::assertEquals(0, $this->object->get('PMA_IS_GD2'), 'GD Version < 2, PMA_IS_GD2 should be 0');
            }
        }

        /* Get GD version string from phpinfo output */
        ob_start();
        phpinfo(INFO_MODULES); /* Only modules */
        $a = strip_tags((string) ob_get_contents());
        ob_end_clean();

        if (! preg_match('@GD Version[[:space:]]*\(.*\)@', $a, $v)) {
            return;
        }

        if (mb_strstr($v, '2.')) {
            self::assertEquals(1, $this->object->get('PMA_IS_GD2'), 'PMA_IS_GD2 should be 1');
        } else {
            self::assertEquals(0, $this->object->get('PMA_IS_GD2'), 'PMA_IS_GD2 should be 0');
        }
    }

    /**
     * Web server detection test
     *
     * @param string $server Server identification
     * @param int    $iis    Whether server should be detected as IIS
     *
     * @dataProvider serverNames
     */
    public function testCheckWebServer(string $server, int $iis): void
    {
        $_SERVER['SERVER_SOFTWARE'] = $server;
        $this->object->checkWebServer();
        self::assertEquals($iis, $this->object->get('PMA_IS_IIS'));
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    /**
     * return server names
     *
     * @return array
     */
    public static function serverNames(): array
    {
        return [
            [
                'Microsoft-IIS 7.0',
                1,
            ],
            [
                'Apache/2.2.17',
                0,
            ],
        ];
    }

    /**
     * test for CheckWebServerOs
     */
    public function testCheckWebServerOs(): void
    {
        $this->object->checkWebServerOs();

        if (defined('PHP_OS')) {
            if (stristr(PHP_OS, 'darwin')) {
                self::assertFalse($this->object->get('PMA_IS_WINDOWS'));
            } elseif (stristr(PHP_OS, 'win')) {
                self::assertTrue($this->object->get('PMA_IS_WINDOWS'));
            } elseif (stristr(PHP_OS, 'OS/2')) {
                self::assertTrue($this->object->get('PMA_IS_WINDOWS'));
            } elseif (stristr(PHP_OS, 'Linux')) {
                self::assertFalse($this->object->get('PMA_IS_WINDOWS'));
            } else {
                $this->markTestIncomplete('Not known PHP_OS: ' . PHP_OS);
            }
        } else {
            self::assertEquals(0, $this->object->get('PMA_IS_WINDOWS'));

            define('PHP_OS', 'Windows');
            self::assertTrue($this->object->get('PMA_IS_WINDOWS'));
        }
    }

    /**
     * Tests loading of default values
     *
     * @group large
     */
    public function testLoadDefaults(): void
    {
        $this->object->defaultServer = [];
        $this->object->default = [];
        $this->object->settings = ['is_setup' => false, 'AvailableCharsets' => ['test']];

        $this->object->loadDefaults();

        $settings = new Settings([]);
        $config = $settings->toArray();

        self::assertIsArray($config['Servers']);
        self::assertEquals($config['Servers'][1], $this->object->defaultServer);
        unset($config['Servers']);
        self::assertEquals($config, $this->object->default);
        self::assertEquals(
            array_replace_recursive(['is_setup' => false, 'AvailableCharsets' => ['test']], $config),
            $this->object->settings
        );
    }

    /**
     * test for CheckConfigSource
     */
    public function testCheckConfigSource(): void
    {
        $this->object->setSource('unexisted.config.php');
        self::assertFalse($this->object->checkConfigSource());
        self::assertEquals(0, $this->object->sourceMtime);

        $this->object->setSource(TEST_PATH . 'test/test_data/config.inc.php');

        self::assertNotEmpty($this->object->getSource());
        self::assertTrue($this->object->checkConfigSource());
    }

    /**
     * Test getting and setting config values
     */
    public function testGetAndSet(): void
    {
        self::assertNull($this->object->get('unresisting_setting'));

        $this->object->set('test_setting', 'test_value');

        self::assertEquals('test_value', $this->object->get('test_setting'));
    }

    /**
     * Tests setting configuration source
     */
    public function testGetSetSource(): void
    {
        echo $this->object->getSource();

        self::assertEmpty($this->object->getSource(), 'Source is null by default');

        $this->object->setSource(ROOT_PATH . 'config.sample.inc.php');

        self::assertEquals(ROOT_PATH . 'config.sample.inc.php', $this->object->getSource(), 'Cant set new source');
    }

    /**
     * test for IsHttp
     *
     * @param string $scheme          http scheme
     * @param string $https           https
     * @param string $forwarded       forwarded header
     * @param string $uri             request uri
     * @param string $lb              http https from lb
     * @param string $front           http front end https
     * @param string $proto           http x forwarded proto
     * @param string $protoCloudFront http cloudfront forwarded proto
     * @param string $pmaAbsoluteUri  phpMyAdmin absolute URI
     * @param int    $port            server port
     * @param bool   $expected        expected result
     *
     * @dataProvider httpsParams
     */
    public function testIsHttps(
        string $scheme,
        string $https,
        string $forwarded,
        string $uri,
        string $lb,
        string $front,
        string $proto,
        string $protoCloudFront,
        string $pmaAbsoluteUri,
        int $port,
        bool $expected
    ): void {
        $_SERVER['HTTP_SCHEME'] = $scheme;
        $_SERVER['HTTPS'] = $https;
        $_SERVER['HTTP_FORWARDED'] = $forwarded;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HTTPS_FROM_LB'] = $lb;
        $_SERVER['HTTP_FRONT_END_HTTPS'] = $front;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = $proto;
        $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] = $protoCloudFront;
        $_SERVER['SERVER_PORT'] = $port;

        $this->object->set('is_https', null);
        $this->object->set('PmaAbsoluteUri', $pmaAbsoluteUri);
        self::assertEquals($expected, $this->object->isHttps());
    }

    /**
     * Data provider for https detection
     *
     * @return array
     */
    public static function httpsParams(): array
    {
        return [
            [
                'http',
                '',
                '',
                '',
                '',
                '',
                'http',
                '',
                '',
                80,
                false,
            ],
            [
                'http',
                '',
                '',
                'http://',
                '',
                '',
                'http',
                '',
                '',
                80,
                false,
            ],
            [
                'http',
                '',
                '',
                '',
                '',
                '',
                'http',
                '',
                '',
                443,
                true,
            ],
            [
                'http',
                '',
                '',
                '',
                '',
                '',
                'https',
                '',
                '',
                80,
                true,
            ],
            [
                'http',
                '',
                '',
                '',
                '',
                'on',
                'http',
                '',
                '',
                80,
                true,
            ],
            [
                'http',
                '',
                '',
                '',
                'on',
                '',
                'http',
                '',
                '',
                80,
                true,
            ],
            [
                'http',
                '',
                '',
                'https://',
                '',
                '',
                'http',
                '',
                '',
                80,
                true,
            ],
            [
                'http',
                'on',
                '',
                '',
                '',
                '',
                'http',
                '',
                '',
                80,
                true,
            ],
            [
                'https',
                '',
                '',
                '',
                '',
                '',
                'http',
                '',
                '',
                80,
                true,
            ],
            [
                'http',
                '',
                '',
                '',
                '',
                '',
                '',
                'https',
                '',
                80,
                true,
            ],
            [
                'http',
                '',
                '',
                '',
                '',
                '',
                'https',
                'http',
                '',
                80,
                true,
            ],
            [
                'https',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                80,
                true,
            ],
            [
                'http',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                8080,
                false,
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'https://127.0.0.1',
                80,
                true,
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'http://127.0.0.1',
                80,
                false,
            ],
            [
                '',
                '',
                'for=12.34.56.78;host=example.com;proto=https, for=23.45.67.89',
                '',
                '',
                '',
                '',
                '',
                'http://127.0.0.1',
                80,
                true,
            ],
        ];
    }

    /**
     * Test for getting root path
     *
     * @param string $request  The request URL used for phpMyAdmin
     * @param string $absolute The absolute URL used for phpMyAdmin
     * @param string $expected Expected root path
     *
     * @dataProvider rootUris
     */
    public function testGetRootPath(string $request, string $absolute, string $expected): void
    {
        $GLOBALS['PMA_PHP_SELF'] = $request;
        $this->object->set('PmaAbsoluteUri', $absolute);
        self::assertEquals($expected, $this->object->getRootPath());
    }

    /**
     * Data provider for testGetRootPath
     *
     * @return array data for testGetRootPath
     */
    public static function rootUris(): array
    {
        return [
            [
                '',
                '',
                '/',
            ],
            [
                '/',
                '',
                '/',
            ],
            [
                '/index.php',
                '',
                '/',
            ],
            [
                '\\index.php',
                '',
                '/',
            ],
            [
                '\\',
                '',
                '/',
            ],
            [
                '\\path\\to\\index.php',
                '',
                '/path/to/',
            ],
            [
                '/foo/bar/phpmyadmin/index.php',
                '',
                '/foo/bar/phpmyadmin/',
            ],
            [
                '/foo/bar/phpmyadmin/',
                '',
                '/foo/bar/phpmyadmin/',
            ],
            [
                'https://example.net/baz/phpmyadmin/',
                '',
                '/baz/phpmyadmin/',
            ],
            [
                'http://example.net/baz/phpmyadmin/',
                '',
                '/baz/phpmyadmin/',
            ],
            [
                'http://example.net/phpmyadmin/',
                '',
                '/phpmyadmin/',
            ],
            [
                'http://example.net/',
                '',
                '/',
            ],
            [
                'http://example.net/',
                'http://example.net/phpmyadmin/',
                '/phpmyadmin/',
            ],
            [
                'http://example.net/',
                'http://example.net/phpmyadmin',
                '/phpmyadmin/',
            ],
            [
                'http://example.net/',
                '/phpmyadmin2',
                '/phpmyadmin2/',
            ],
            [
                'http://example.net/',
                '/phpmyadmin3/',
                '/phpmyadmin3/',
            ],
        ];
    }

    /**
     * Tests loading of config file
     *
     * @param string $source File name of config to load
     * @param bool   $result Expected result of loading
     *
     * @dataProvider configPaths
     */
    public function testLoad(string $source, bool $result): void
    {
        if ($result) {
            self::assertTrue($this->object->load($source));
        } else {
            self::assertFalse($this->object->load($source));
        }
    }

    /**
     * return of config Paths
     *
     * @return array
     */
    public static function configPaths(): array
    {
        return [
            [
                TEST_PATH . 'test/test_data/config.inc.php',
                true,
            ],
            [
                TEST_PATH . 'test/test_data/config-nonexisting.inc.php',
                false,
            ],
        ];
    }

    /**
     * Test for loading user preferences
     *
     * @todo Test actually preferences loading
     * @doesNotPerformAssertions
     */
    public function testLoadUserPreferences(): void
    {
        $this->object->loadUserPreferences();
    }

    /**
     * Test for setting user config value
     */
    public function testSetUserValue(): void
    {
        $this->object->setUserValue(null, 'lang', 'cs', 'en');
        $this->object->setUserValue('TEST_COOKIE_USER_VAL', '', 'cfg_val_1');
        self::assertEquals($this->object->getUserValue('TEST_COOKIE_USER_VAL', 'fail'), 'cfg_val_1');
    }

    /**
     * Test for getting user config value
     */
    public function testGetUserValue(): void
    {
        self::assertEquals($this->object->getUserValue('test_val', 'val'), 'val');
    }

    /**
     * Should test checking of config permissions
     */
    public function testCheckPermissions(): void
    {
        //load file permissions for the current permissions file
        $perms = @fileperms($this->object->getSource());
        //testing for permissions for no configuration file
        self::assertFalse(! ($perms === false) && ($perms & 2));

        //load file permissions for the current permissions file
        $perms = @fileperms($this->permTestObj->getSource());

        if (! ($perms === false) && ($perms & 2)) {
            self::assertTrue((bool) $this->permTestObj->get('PMA_IS_WINDOWS'));
        } else {
            self::assertFalse((bool) $this->permTestObj->get('PMA_IS_WINDOWS'));
        }
    }

    /**
     * Test for setting cookies
     */
    public function testSetCookie(): void
    {
        $this->object->set('is_https', false);
        self::assertFalse($this->object->setCookie(
            'TEST_DEF_COOKIE',
            'test_def_123',
            'test_def_123'
        ));

        self::assertTrue($this->object->setCookie(
            'TEST_CONFIG_COOKIE',
            'test_val_123',
            null,
            3600
        ));

        self::assertTrue($this->object->setCookie(
            'TEST_CONFIG_COOKIE',
            '',
            'default_val'
        ));

        $_COOKIE['TEST_MANUAL_COOKIE'] = 'some_test_val';
        self::assertTrue($this->object->setCookie(
            'TEST_MANUAL_COOKIE',
            'other',
            'other'
        ));
    }

    /**
     * Test for getTempDir
     *
     * @group file-system
     */
    public function testGetTempDir(): void
    {
        $dir = realpath(sys_get_temp_dir());
        self::assertNotFalse($dir);
        self::assertDirectoryExists($dir);
        self::assertDirectoryIsWritable($dir);

        $this->object->set('TempDir', $dir . DIRECTORY_SEPARATOR);
        // Check no double slash is here
        self::assertEquals($dir . DIRECTORY_SEPARATOR . 'upload', $this->object->getTempDir('upload'));
    }

    /**
     * Test for getUploadTempDir
     *
     * @group file-system
     * @depends testGetTempDir
     */
    public function testGetUploadTempDir(): void
    {
        $dir = realpath(sys_get_temp_dir());
        self::assertNotFalse($dir);
        self::assertDirectoryExists($dir);
        self::assertDirectoryIsWritable($dir);

        $this->object->set('TempDir', $dir . DIRECTORY_SEPARATOR);

        self::assertEquals($this->object->getTempDir('upload'), $this->object->getUploadTempDir());
    }

    /**
     * Test for checkServers
     *
     * @param array $settings settings array
     * @param array $expected expected result
     *
     * @dataProvider serverSettingsProvider
     */
    public function testCheckServers(array $settings, array $expected): void
    {
        $this->object->settings['Servers'] = $settings;
        $this->object->checkServers();
        $expected = array_merge($this->object->defaultServer, $expected);

        self::assertEquals($expected, $this->object->settings['Servers'][1]);
    }

    /**
     * Data provider for checkServers test
     *
     * @return array
     */
    public static function serverSettingsProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'only_host' => [
                [1 => ['host' => '127.0.0.1']],
                ['host' => '127.0.0.1'],
            ],
            'empty_host' => [
                [1 => ['host' => '']],
                [
                    'verbose' => 'Server 1',
                    'host' => '',
                ],
            ],
        ];
    }

    /**
     * @group with-trigger-error
     * @requires PHPUnit < 10
     */
    public function testCheckServersWithInvalidServer(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Invalid server index: invalid');

        $this->object->settings['Servers'] = ['invalid' => ['host' => '127.0.0.1'], 1 => ['host' => '127.0.0.1']];
        $this->object->checkServers();
        $expected = array_merge($this->object->defaultServer, ['host' => '127.0.0.1']);

        self::assertEquals($expected, $this->object->settings['Servers'][1]);
    }

    /**
     * Test for selectServer
     *
     * @param array  $settings settings array
     * @param string $request  request
     * @param int    $expected expected result
     *
     * @dataProvider selectServerProvider
     * @depends testCheckServers
     */
    public function testSelectServer(array $settings, string $request, int $expected): void
    {
        $this->object->settings['Servers'] = $settings;
        $this->object->checkServers();
        $_REQUEST['server'] = $request;
        self::assertEquals($expected, $this->object->selectServer());
    }

    /**
     * Data provider for selectServer test
     *
     * @return array
     */
    public static function selectServerProvider(): array
    {
        return [
            'zero' => [
                [],
                '0',
                1,
            ],
            'number' => [
                [1 => []],
                '1',
                1,
            ],
            'host' => [
                [2 => ['host' => '127.0.0.1']],
                '127.0.0.1',
                2,
            ],
            'verbose' => [
                [
                    1 => [
                        'verbose' => 'Server 1',
                        'host' => '',
                    ],
                ],
                'Server 1',
                1,
            ],
            'md5' => [
                [
                    66 => [
                        'verbose' => 'Server 1',
                        'host' => '',
                    ],
                ],
                '753f173bd4ac8a45eae0fe9a4fbe0fc0',
                66,
            ],
            'nonexisting_string' => [
                [1 => []],
                'invalid',
                1,
            ],
            'nonexisting' => [
                [1 => []],
                '100',
                1,
            ],
        ];
    }

    /**
     * Test for getConnectionParams
     *
     * @param array      $server_cfg Server configuration
     * @param int        $mode       Mode to test
     * @param array|null $server     Server array to test
     * @param array      $expected   Expected result
     *
     * @dataProvider connectionParams
     */
    public function testGetConnectionParams(array $server_cfg, int $mode, ?array $server, array $expected): void
    {
        $GLOBALS['cfg']['Server'] = $server_cfg;
        $result = Config::getConnectionParams($mode, $server);
        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for getConnectionParams test
     *
     * @return array
     */
    public static function connectionParams(): array
    {
        $cfg_basic = [
            'user' => 'u',
            'password' => 'pass',
            'host' => '',
            'controluser' => 'u2',
            'controlpass' => 'p2',
            'hide_connection_errors' => false,
        ];
        $cfg_ssl = [
            'user' => 'u',
            'password' => 'pass',
            'host' => '',
            'ssl' => true,
            'controluser' => 'u2',
            'controlpass' => 'p2',
            'hide_connection_errors' => false,
        ];
        $cfg_control_ssl = [
            'user' => 'u',
            'password' => 'pass',
            'host' => '',
            'control_ssl' => true,
            'controluser' => 'u2',
            'controlpass' => 'p2',
            'hide_connection_errors' => false,
        ];

        return [
            [
                $cfg_basic,
                DatabaseInterface::CONNECT_USER,
                null,
                [
                    'u',
                    'pass',
                    [
                        'user' => 'u',
                        'password' => 'pass',
                        'host' => 'localhost',
                        'socket' => null,
                        'port' => 0,
                        'ssl' => false,
                        'compress' => false,
                        'controluser' => 'u2',
                        'controlpass' => 'p2',
                        'hide_connection_errors' => false,
                    ],
                ],
            ],
            [
                $cfg_basic,
                DatabaseInterface::CONNECT_CONTROL,
                null,
                [
                    'u2',
                    'p2',
                    [
                        'host' => 'localhost',
                        'socket' => null,
                        'port' => 0,
                        'ssl' => false,
                        'compress' => false,
                        'hide_connection_errors' => false,
                    ],
                ],
            ],
            [
                $cfg_ssl,
                DatabaseInterface::CONNECT_USER,
                null,
                [
                    'u',
                    'pass',
                    [
                        'user' => 'u',
                        'password' => 'pass',
                        'host' => 'localhost',
                        'socket' => null,
                        'port' => 0,
                        'ssl' => true,
                        'compress' => false,
                        'controluser' => 'u2',
                        'controlpass' => 'p2',
                        'hide_connection_errors' => false,
                    ],
                ],
            ],
            [
                $cfg_ssl,
                DatabaseInterface::CONNECT_CONTROL,
                null,
                [
                    'u2',
                    'p2',
                    [
                        'host' => 'localhost',
                        'socket' => null,
                        'port' => 0,
                        'ssl' => true,
                        'compress' => false,
                        'hide_connection_errors' => false,
                    ],
                ],
            ],
            [
                $cfg_control_ssl,
                DatabaseInterface::CONNECT_USER,
                null,
                [
                    'u',
                    'pass',
                    [
                        'user' => 'u',
                        'password' => 'pass',
                        'host' => 'localhost',
                        'socket' => null,
                        'port' => 0,
                        'ssl' => false,
                        'compress' => false,
                        'controluser' => 'u2',
                        'controlpass' => 'p2',
                        'control_ssl' => true,
                        'hide_connection_errors' => false,
                    ],
                ],
            ],
            [
                $cfg_control_ssl,
                DatabaseInterface::CONNECT_CONTROL,
                null,
                [
                    'u2',
                    'p2',
                    [
                        'host' => 'localhost',
                        'socket' => null,
                        'port' => 0,
                        'ssl' => true,
                        'compress' => false,
                        'hide_connection_errors' => false,
                    ],
                ],
            ],
        ];
    }
}
