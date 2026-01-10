<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function file_put_contents;
use function get_defined_constants;
use function md5;
use function realpath;
use function stristr;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const CHANGELOG_FILE;
use const CONFIG_FILE;
use const DIRECTORY_SEPARATOR;
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
        $config->loadFromFile(CONFIG_FILE);
        $config->settings['ProxyUrl'] = '';

        //for testing file permissions
        $this->permTestObj = new Config();
        $this->permTestObj->loadFromFile(ROOT_PATH . 'config.sample.inc.php');
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
        $config->loadFromFile($tmpConfig);
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
        $config->loadFromFile($tmpConfig);
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
     * test for isGd2Available
     */
    public function testCheckGd2(): void
    {
        $this->object->set('GD2Available', 'yes');
        self::assertTrue($this->object->isGd2Available());

        $this->object->set('GD2Available', 'no');
        self::assertFalse($this->object->isGd2Available());

        $this->object->set('GD2Available', 'auto');
        self::assertSame(
            (get_defined_constants()['GD_MAJOR_VERSION'] ?? 0) >= 2,
            $this->object->isGd2Available(),
        );
    }

    /**
     * test for CheckWebServerOs
     */
    public function testCheckWebServerOs(): void
    {
        $isWindows = $this->object->isWindows();

        if (stristr(PHP_OS, 'darwin')) {
            self::assertFalse($isWindows);
        } elseif (stristr(PHP_OS, 'win')) {
            self::assertTrue($isWindows);
        } elseif (stristr(PHP_OS, 'OS/2')) {
            self::assertTrue($isWindows);
        } elseif (stristr(PHP_OS, 'Linux')) {
            self::assertFalse($isWindows);
        } else {
            self::markTestIncomplete('Not known PHP_OS: ' . PHP_OS);
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
     * Test getting and setting config values
     */
    public function testGetAndSet(): void
    {
        $originalValue = $this->object->config->TempDir;
        $this->object->set('TempDir', 'test_value');
        self::assertSame('test_value', $this->object->settings['TempDir']);
        $this->object->set('TempDir', $originalValue);
    }

    /**
     * Tests setting configuration source
     */
    public function testGetSetSource(): void
    {
        self::assertSame('', $this->object->getSource());

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

        $this->object->set('PmaAbsoluteUri', $pmaAbsoluteUri);
        self::assertSame($expected, $this->object->isHttps());
    }

    /**
     * Data provider for https detection
     *
     * @return array<array<string|int|bool>>
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
     * @return array<array<string>>
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

    #[DataProvider('configPaths')]
    public function testConfigFileExists(string $source, bool $result): void
    {
        $this->object->setSource($source);
        self::assertSame($result, $this->object->configFileExists());
    }

    /** @return array<array{string, bool}> */
    public static function configPaths(): array
    {
        return [
            [__DIR__ . '/../test_data/config.inc.php', true],
            [__DIR__ . '/../test_data/config-nonexisting.inc.php', false],
            ['', false],
        ];
    }

    /**
     * Test for setting cookies
     */
    public function testSetCookie(): void
    {
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
     * @param array<int, array<string, string>> $settings settings array
     * @param string|mixed[]                    $request  request
     * @param int                               $expected expected result
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
     * @return array<string, array{array<int, array<string, string>>, string|mixed[], int}>
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
     * @param array<string, bool|string>          $serverCfg Server configuration
     * @param array<string, bool|string|int|null> $expected  Expected result
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
     * @return array<array{array<string, bool|string>, ConnectionType, array<string, bool|string|int|null>}>
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

    /** @return iterable<string, array{ConnectionType, string, string, string, string}> */
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
