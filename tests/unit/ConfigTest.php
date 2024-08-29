<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function define;
use function defined;
use function file_put_contents;
use function fileperms;
use function function_exists;
use function gd_info;
use function md5;
use function ob_get_clean;
use function ob_start;
use function phpinfo;
use function preg_match;
use function realpath;
use function str_contains;
use function strip_tags;
use function stristr;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const CHANGELOG_FILE;
use const CONFIG_FILE;
use const DIRECTORY_SEPARATOR;
use const INFO_MODULES;
use const PHP_OS;

#[CoversClass(Config::class)]
#[Medium]
class ConfigTest extends AbstractTestCase
{
    protected Config $object;

    /** @var Config to test file permission */
    protected Config $permTestObj;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $_SERVER['HTTP_USER_AGENT'] = '';
        $this->object = $this->createConfig();
        $_SESSION['git_location'] = '.git';
        $_SESSION['is_git_revision'] = true;
        Config::$instance = null;
        $config = Config::getInstance();
        $config->loadAndCheck(CONFIG_FILE);
        $config->settings['ProxyUrl'] = '';

        //for testing file permissions
        $this->permTestObj = new Config();
        $this->permTestObj->loadAndCheck(ROOT_PATH . 'config.sample.inc.php');
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

    public function testLoadConfigs(): void
    {
        $defaultConfig = $this->createConfig();
        $tmpConfig = tempnam('./', 'config.test.inc.php');
        if ($tmpConfig === false) {
            self::markTestSkipped('Creating a temporary file does not work');
        }

        self::assertFileExists($tmpConfig);

        // end of setup

        // Test loading an empty file does not change the default config
        $config = new Config();
        $config->loadAndCheck($tmpConfig);
        self::assertSame($defaultConfig->settings, $config->settings);
        self::assertEquals($defaultConfig->getSettings(), $config->getSettings());

        $contents = <<<'PHP'
<?php
$cfg['environment'] = 'development';
$cfg['UnknownKey'] = true;
PHP;
        file_put_contents($tmpConfig, $contents);

        // Test loading a config changes the setup
        $config = new Config();
        $config->loadAndCheck($tmpConfig);
        $defaultConfig->set('environment', 'development');
        self::assertSame($defaultConfig->settings, $config->settings);
        self::assertArrayHasKey('environment', $config->settings);
        self::assertSame($config->settings['environment'], 'development');
        self::assertArrayNotHasKey('UnknownKey', $config->settings);
        self::assertEquals($defaultConfig->getSettings(), $config->getSettings());

        // Teardown
        unlink($tmpConfig);
        self::assertFileDoesNotExist($tmpConfig);
    }

    /**
     * Test for CheckSystem
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
     */
    #[DataProvider('userAgentProvider')]
    public function testCheckClient(
        string $agent,
        string $os,
        string|null $browser = null,
        string|null $version = null,
    ): void {
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $this->object->checkClient();
        self::assertSame($os, $this->object->get('PMA_USR_OS'));
        self::assertSame($browser, $this->object->get('PMA_USR_BROWSER_AGENT'));

        if ($version === null) {
            return;
        }

        self::assertEquals(
            $version,
            $this->object->get('PMA_USR_BROWSER_VER'),
        );
    }

    /**
     * user Agent Provider
     *
     * @return mixed[]
     */
    public static function userAgentProvider(): array
    {
        return [
            ['Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00', 'Linux', 'OPERA', '9.80'],
            [
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US) AppleWebKit/528.16 OmniWeb/622.8.0.112941',
                'Mac',
                'OMNIWEB',
                '622',
            ],
            ['Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)', 'Win', 'IE', '8.0'],
            ['Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)', 'Win', 'IE', '9.0'],
            ['Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)', 'Win', 'IE', '10.0'],
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
            ['Mozilla/5.0 (Windows; U; Win95; en-US; rv:1.9b) Gecko/20031208', 'Win', 'GECKO', '1.9'],
            [
                'Mozilla/5.0 (compatible; Konqueror/4.5; NetBSD 5.0.2; X11; amd64; en_US) KHTML/4.5.4 (like Gecko)',
                'Other',
                'KONQUEROR',
            ],
            ['Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0', 'Linux', 'FIREFOX', '5.0'],
            ['Mozilla/5.0 (X11; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0', 'Linux', 'FIREFOX', '12.0'],
            /** @todo Is this version really expected? */
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
        self::assertSame(1, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available', 'no');
        $this->object->checkGd2();
        self::assertSame(0, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available', 'auto');

        if (! function_exists('imagecreatetruecolor')) {
            $this->object->checkGd2();
            self::assertSame(
                0,
                $this->object->get('PMA_IS_GD2'),
                'imagecreatetruecolor does not exist, PMA_IS_GD2 should be 0',
            );
        }

        if (function_exists('gd_info')) {
            $this->object->checkGd2();
            $gdNfo = gd_info();
            if (str_contains($gdNfo['GD Version'], '2.')) {
                self::assertSame(
                    1,
                    $this->object->get('PMA_IS_GD2'),
                    'GD Version >= 2, PMA_IS_GD2 should be 1',
                );
            } else {
                self::assertSame(
                    0,
                    $this->object->get('PMA_IS_GD2'),
                    'GD Version < 2, PMA_IS_GD2 should be 0',
                );
            }
        }

        /* Get GD version string from phpinfo output */
        ob_start();
        phpinfo(INFO_MODULES); /* Only modules */
        $a = strip_tags((string) ob_get_clean());

        if (preg_match('@GD Version[[:space:]]*\(.*\)@', $a, $v) !== 1) {
            return;
        }

        // TODO: The variable $v clearly is incorrect. Was this meant to be $a?
        if (str_contains($v, '2.')) {
            self::assertSame(
                1,
                $this->object->get('PMA_IS_GD2'),
                'PMA_IS_GD2 should be 1',
            );
        } else {
            self::assertSame(
                0,
                $this->object->get('PMA_IS_GD2'),
                'PMA_IS_GD2 should be 0',
            );
        }
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
                self::markTestIncomplete('Not known PHP_OS: ' . PHP_OS);
            }
        } else {
            self::assertSame(0, $this->object->get('PMA_IS_WINDOWS'));

            define('PHP_OS', 'Windows');
            self::assertTrue($this->object->get('PMA_IS_WINDOWS'));
        }
    }

    public function testConstructor(): void
    {
        $object = new Config();
        $settings = new Settings([]);
        $config = $settings->asArray();
        self::assertIsArray($config['Servers']);
        self::assertEquals($settings, $object->getSettings());
        self::assertSame($config, $object->default);
        self::assertSame($config, $object->settings);
        self::assertSame($config, $object->baseSettings);
    }

    /**
     * test for CheckConfigSource
     */
    public function testCheckConfigSource(): void
    {
        $this->object->setSource('unexisted.config.php');
        self::assertFalse($this->object->checkConfigSource());
        self::assertSame(0, $this->object->sourceMtime);

        $this->object->setSource(__DIR__ . '/../test_data/config.inc.php');

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

        self::assertSame('test_value', $this->object->get('test_setting'));
    }

    /**
     * Tests setting configuration source
     */
    public function testGetSetSource(): void
    {
        echo $this->object->getSource();

        self::assertEmpty($this->object->getSource(), 'Source is null by default');

        $this->object->setSource(ROOT_PATH . 'config.sample.inc.php');

        self::assertSame(
            ROOT_PATH . 'config.sample.inc.php',
            $this->object->getSource(),
            'Cant set new source',
        );
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
     */
    #[DataProvider('httpsParams')]
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
        bool $expected,
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
        self::assertSame($expected, $this->object->isHttps());
    }

    /**
     * Data provider for https detection
     *
     * @return mixed[]
     */
    public static function httpsParams(): array
    {
        return [
            ['http', '', '', '', '', '', 'http', '', '', 80, false],
            ['http', '', '', 'http://', '', '', 'http', '', '', 80, false],
            ['http', '', '', '', '', '', 'http', '', '', 443, true],
            ['http', '', '', '', '', '', 'https', '', '', 80, true],
            ['http', '', '', '', '', 'on', 'http', '', '', 80, true],
            ['http', '', '', '', 'on', '', 'http', '', '', 80, true],
            ['http', '', '', 'https://', '', '', 'http', '', '', 80, true],
            ['http', 'on', '', '', '', '', 'http', '', '', 80, true],
            ['https', '', '', '', '', '', 'http', '', '', 80, true],
            ['http', '', '', '', '', '', '', 'https', '', 80, true],
            ['http', '', '', '', '', '', 'https', 'http', '', 80, true],
            ['https', '', '', '', '', '', '', '', '', 80, true],
            ['http', '', '', '', '', '', '', '', '', 8080, false],
            ['', '', '', '', '', '', '', '', 'https://127.0.0.1', 80, true],
            ['', '', '', '', '', '', '', '', 'http://127.0.0.1', 80, false],
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
     */
    #[DataProvider('rootUris')]
    public function testGetRootPath(string $request, string $absolute, string $expected): void
    {
        $_SERVER['PHP_SELF'] = $request;
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['PATH_INFO'] = '';
        $this->object->set('PmaAbsoluteUri', $absolute);
        self::assertSame($expected, $this->object->getRootPath());
    }

    /**
     * Data provider for testGetRootPath
     *
     * @return mixed[] data for testGetRootPath
     */
    public static function rootUris(): array
    {
        return [
            ['', '', '/'],
            ['/', '', '/'],
            ['/index.php', '', '/'],
            ['/foo/bar/phpmyadmin/index.php', '', '/foo/bar/phpmyadmin/'],
            ['/foo/bar/phpmyadmin/', '', '/foo/bar/phpmyadmin/'],
            ['/foo/bar/phpmyadmin', '', '/foo/bar/phpmyadmin/'],
            ['http://example.net/', 'http://example.net/phpmyadmin/', '/phpmyadmin/'],
            ['http://example.net/', 'http://example.net/phpmyadmin', '/phpmyadmin/'],
            ['http://example.net/', '/phpmyadmin2', '/phpmyadmin2/'],
            ['http://example.net/', '/phpmyadmin3/', '/phpmyadmin3/'],
        ];
    }

    /**
     * Tests loading of config file
     *
     * @param string $source File name of config to load
     * @param bool   $result Expected result of loading
     */
    #[DataProvider('configPaths')]
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
     * @return mixed[]
     */
    public static function configPaths(): array
    {
        return [
            [__DIR__ . '/../test_data/config.inc.php', true],
            [__DIR__ . '/../test_data/config-nonexisting.inc.php', false],
        ];
    }

    /**
     * Test for setting user config value
     */
    public function testSetUserValue(): void
    {
        $this->object->setUserValue(null, 'lang', 'cs', 'en');
        $this->object->setUserValue('TEST_COOKIE_USER_VAL', '', 'cfg_val_1');
        self::assertSame(
            $this->object->getUserValue('TEST_COOKIE_USER_VAL', 'fail'),
            'cfg_val_1',
        );
        $this->object->setUserValue(null, 'NavigationWidth', 300);
        self::assertSame($this->object->settings['NavigationWidth'], 300);
    }

    /**
     * Test for getting user config value
     */
    public function testGetUserValue(): void
    {
        self::assertSame($this->object->getUserValue('test_val', 'val'), 'val');
    }

    /**
     * Should test checking of config permissions
     */
    public function testCheckPermissions(): void
    {
        //load file permissions for the current permissions file
        $perms = @fileperms($this->object->getSource());
        //testing for permissions for no configuration file
        self::assertFalse($perms !== false && ($perms & 2));

        //load file permissions for the current permissions file
        $perms = @fileperms($this->permTestObj->getSource());

        if ($perms !== false && ($perms & 2)) {
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
        self::assertFalse(
            $this->object->setCookie(
                'TEST_DEF_COOKIE',
                'test_def_123',
                'test_def_123',
            ),
        );

        self::assertTrue(
            $this->object->setCookie(
                'TEST_CONFIG_COOKIE',
                'test_val_123',
                null,
                3600,
            ),
        );

        self::assertTrue(
            $this->object->setCookie(
                'TEST_CONFIG_COOKIE',
                '',
                'default_val',
            ),
        );

        $_COOKIE['TEST_MANUAL_COOKIE'] = 'some_test_val';
        self::assertTrue(
            $this->object->setCookie(
                'TEST_MANUAL_COOKIE',
                'other',
                'other',
            ),
        );
    }

    /**
     * Test for getTempDir
     */
    #[Group('file-system')]
    public function testGetTempDir(): void
    {
        $dir = realpath(sys_get_temp_dir());
        self::assertNotFalse($dir);
        self::assertDirectoryExists($dir);
        self::assertDirectoryIsWritable($dir);

        (new ReflectionProperty(Config::class, 'tempDir'))->setValue(null, []);
        $this->object->set('TempDir', $dir . DIRECTORY_SEPARATOR);
        // Check no double slash is here
        self::assertSame(
            $dir . DIRECTORY_SEPARATOR . 'upload',
            $this->object->getTempDir('upload'),
        );
    }

    /**
     * Test for getUploadTempDir
     */
    #[Depends('testGetTempDir')]
    #[Group('file-system')]
    public function testGetUploadTempDir(): void
    {
        $dir = realpath(sys_get_temp_dir());
        self::assertNotFalse($dir);
        self::assertDirectoryExists($dir);
        self::assertDirectoryIsWritable($dir);

        $this->object->set('TempDir', $dir . DIRECTORY_SEPARATOR);

        self::assertSame(
            $this->object->getTempDir('upload'),
            $this->object->getUploadTempDir(),
        );
    }

    /**
     * Test for selectServer
     *
     * @param mixed[]        $settings settings array
     * @param string|mixed[] $request  request
     * @param int            $expected expected result
     */
    #[DataProvider('selectServerProvider')]
    public function testSelectServer(array $settings, string|array $request, int $expected): void
    {
        $config = new Config();
        $config->config = new Settings(['Servers' => $settings, 'ServerDefault' => 1]);
        $selectedServer = $config->selectServer($request);
        self::assertSame($expected, $selectedServer);
        self::assertGreaterThanOrEqual(0, $selectedServer);
        self::assertArrayHasKey('Server', $config->settings);
        self::assertSame($expected, $config->server);
        if ($expected >= 1) {
            self::assertTrue($config->hasSelectedServer());
            $expectedServer = $config->config->Servers[$expected]->asArray();
            self::assertSame($expectedServer, $config->settings['Server']);
            self::assertSame($expectedServer, $config->selectedServer);
        } else {
            self::assertFalse($config->hasSelectedServer());
            self::assertSame([], $config->settings['Server']);
            self::assertSame((new Server())->asArray(), $config->selectedServer);
        }
    }

    /**
     * Data provider for selectServer test
     *
     * @return array<string, array{mixed[], string|mixed[], int}>
     */
    public static function selectServerProvider(): array
    {
        return [
            'zero' => [[], '0', 1],
            'number' => [[1 => []], '1', 1],
            'host' => [[2 => ['host' => '127.0.0.1']], '127.0.0.1', 2],
            'verbose' => [[1 => ['verbose' => 'Server 1', 'host' => '']], 'Server 1', 1],
            'md5' => [[66 => ['verbose' => 'Server 66', 'host' => '']], md5('server 66'), 66],
            'nonexisting_string' => [[1 => []], 'invalid', 1],
            'nonexisting' => [[1 => []], '100', 1],
            'none selected' => [[2 => []], '100', 0],
            'none selected with string' => [[2 => []], 'unknown', 0],
            'negative number' => [[1 => []], '-1', 1],
            'array' => [[1 => []], ['1'], 1],
        ];
    }

    /**
     * Test for getConnectionParams
     *
     * @param mixed[] $serverCfg Server configuration
     * @param mixed[] $expected  Expected result
     */
    #[DataProvider('connectionParams')]
    public function testGetConnectionParams(array $serverCfg, ConnectionType $connectionType, array $expected): void
    {
        $result = Config::getConnectionParams(new Server($serverCfg), $connectionType);
        self::assertEquals(new Server($expected), $result);
    }

    /**
     * Data provider for getConnectionParams test
     *
     * @return array<array{mixed[], ConnectionType, mixed[]}>
     */
    public static function connectionParams(): array
    {
        $cfgBasic = [
            'user' => 'u',
            'password' => 'pass',
            'host' => '',
            'controluser' => 'u2',
            'controlpass' => 'p2',
            'hide_connection_errors' => false,
        ];
        $cfgSsl = [
            'user' => 'u',
            'password' => 'pass',
            'host' => '',
            'ssl' => true,
            'controluser' => 'u2',
            'controlpass' => 'p2',
            'hide_connection_errors' => false,
        ];
        $cfgControlSsl = [
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
                $cfgBasic,
                ConnectionType::User,
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
            [
                $cfgBasic,
                ConnectionType::ControlUser,
                [
                    'user' => 'u2',
                    'password' => 'p2',
                    'host' => 'localhost',
                    'socket' => null,
                    'port' => 0,
                    'ssl' => false,
                    'compress' => false,
                    'hide_connection_errors' => false,
                ],
            ],
            [
                $cfgSsl,
                ConnectionType::User,
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
            [
                $cfgSsl,
                ConnectionType::ControlUser,
                [
                    'user' => 'u2',
                    'password' => 'p2',
                    'host' => 'localhost',
                    'socket' => null,
                    'port' => 0,
                    'ssl' => true,
                    'compress' => false,
                    'hide_connection_errors' => false,
                ],
            ],
            [
                $cfgControlSsl,
                ConnectionType::User,
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
            [
                $cfgControlSsl,
                ConnectionType::ControlUser,
                [
                    'user' => 'u2',
                    'password' => 'p2',
                    'host' => 'localhost',
                    'socket' => null,
                    'port' => 0,
                    'ssl' => true,
                    'compress' => false,
                    'hide_connection_errors' => false,
                ],
            ],
        ];
    }

    #[DataProvider('connectionParamsWhenConnectionIsUserOrAuxiliaryProvider')]
    public function testGetConnectionParamsWhenConnectionIsUserOrAuxiliary(
        ConnectionType $connectionType,
        string $host,
        string $port,
        string $expectedHost,
        string $expectedPort,
    ): void {
        $actual = Config::getConnectionParams(new Server(['host' => $host, 'port' => $port]), $connectionType);
        $expected = new Server(['host' => $expectedHost, 'port' => $expectedPort]);
        self::assertEquals($expected, $actual);
    }

    /** @psalm-return iterable<string, array{ConnectionType, string, string, string, string}> */
    public static function connectionParamsWhenConnectionIsUserOrAuxiliaryProvider(): iterable
    {
        yield 'user with only port empty' => [ConnectionType::User, 'test.host', '', 'test.host', '0'];
        yield 'user with only host empty' => [ConnectionType::User, '', '12345', 'localhost', '12345'];
        yield 'user with host and port empty' => [ConnectionType::User, '', '', 'localhost', '0'];
        yield 'user with host and port defined' => [ConnectionType::User, 'test.host', '12345', 'test.host', '12345'];
        yield 'aux with only port empty' => [ConnectionType::Auxiliary, 'test.host', '', 'test.host', '0'];
        yield 'aux with only host empty' => [ConnectionType::Auxiliary, '', '12345', 'localhost', '12345'];
        yield 'aux with host and port empty' => [ConnectionType::Auxiliary, '', '', 'localhost', '0'];
        yield 'aux with host and port defined' => [
            ConnectionType::Auxiliary,
            'test.host',
            '12345',
            'test.host',
            '12345',
        ];
    }

    public function testVendorConfigFile(): void
    {
        $vendorConfig = include ROOT_PATH . 'app/vendor_config.php';
        self::assertIsArray($vendorConfig);
        self::assertIsString($vendorConfig['autoloadFile']);
        self::assertFileExists($vendorConfig['autoloadFile']);
        self::assertIsString($vendorConfig['tempDir']);
        self::assertIsString($vendorConfig['changeLogFile']);
        self::assertFileExists($vendorConfig['changeLogFile']);
        self::assertIsString($vendorConfig['licenseFile']);
        self::assertFileExists($vendorConfig['licenseFile']);
        self::assertIsString($vendorConfig['sqlDir']);
        self::assertDirectoryExists($vendorConfig['sqlDir']);
        self::assertIsString($vendorConfig['configFile']);
        self::assertIsString($vendorConfig['customHeaderFile']);
        self::assertIsString($vendorConfig['customFooterFile']);
        self::assertIsBool($vendorConfig['versionCheckDefault']);
        self::assertIsString($vendorConfig['localePath']);
        self::assertDirectoryExists($vendorConfig['localePath']);
        self::assertIsString($vendorConfig['cacheDir']);
        self::assertDirectoryExists($vendorConfig['cacheDir']);
        self::assertIsString($vendorConfig['versionSuffix']);
    }

    public function testGetChangeLogFilePath(): void
    {
        self::assertSame(CHANGELOG_FILE, (new Config())->getChangeLogFilePath());
    }
}
