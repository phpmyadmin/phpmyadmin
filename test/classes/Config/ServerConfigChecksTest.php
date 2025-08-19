<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionException;
use ReflectionProperty;

use function array_keys;
use function mb_strlen;
use function str_repeat;

use const PHP_VERSION_ID;
use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

/**
 * @covers \PhpMyAdmin\Config\ServerConfigChecks
 */
class ServerConfigChecksTest extends AbstractTestCase
{
    /** @var string */
    private $sessionID;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        $GLOBALS['cfg']['AvailableCharsets'] = [];
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 0;

        $cf = new ConfigFile();
        $GLOBALS['ConfigFile'] = $cf;

        $reflection = new ReflectionProperty(ConfigFile::class, 'id');
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        $this->sessionID = $reflection->getValue($cf);

        unset($_SESSION['messages']);
        unset($_SESSION[$this->sessionID]);
    }

    public function testManyErrors(): void
    {
        $_SESSION[$this->sessionID]['Servers'] = [
            '1' => [
                'host' => 'localhost',
                'ssl' => false,
                'auth_type' => 'config',
                'user' => 'username',
                'password' => 'password',
                'AllowRoot' => true,
                'AllowNoPassword' => true,
            ],
        ];

        $_SESSION[$this->sessionID]['AllowArbitraryServer'] = true;
        $_SESSION[$this->sessionID]['LoginCookieValidity'] = 5000;
        $_SESSION[$this->sessionID]['LoginCookieStore'] = 4000;
        $_SESSION[$this->sessionID]['SaveDir'] = true;
        $_SESSION[$this->sessionID]['TempDir'] = true;
        $_SESSION[$this->sessionID]['GZipDump'] = true;
        $_SESSION[$this->sessionID]['BZipDump'] = true;
        $_SESSION[$this->sessionID]['ZipDump'] = true;

        $configChecker = $this->getMockBuilder(ServerConfigChecks::class)
            ->onlyMethods(['functionExists'])
            ->setConstructorArgs([$GLOBALS['ConfigFile']])
            ->getMock();

        // Configure the stub.
        $configChecker->method('functionExists')->willReturn(false);

        $configChecker->performConfigChecks();

        self::assertSame([
            'Servers/1/ssl',
            'Servers/1/auth_type',
            'Servers/1/AllowNoPassword',
            'AllowArbitraryServer',
            'LoginCookieValidity',
            'SaveDir',
            'TempDir',
        ], array_keys($_SESSION['messages']['notice']));

        self::assertSame([
            'LoginCookieValidity',
            'GZipDump',
            'BZipDump',
            'ZipDump_import',
            'ZipDump_export',
        ], array_keys($_SESSION['messages']['error']));
    }

    public function testBlowfish(): void
    {
        $_SESSION[$this->sessionID] = [];
        $_SESSION[$this->sessionID]['blowfish_secret'] = null;
        $_SESSION[$this->sessionID]['Servers'] = [
            '1' => [
                'host' => 'localhost',
                'ssl' => true,
                'auth_type' => 'cookie',
                'AllowRoot' => false,
            ],
        ];
        $_SESSION[$this->sessionID]['AllowArbitraryServer'] = false;
        $_SESSION[$this->sessionID]['LoginCookieValidity'] = -1;
        $_SESSION[$this->sessionID]['LoginCookieStore'] = 0;
        $_SESSION[$this->sessionID]['SaveDir'] = '';
        $_SESSION[$this->sessionID]['TempDir'] = '';
        $_SESSION[$this->sessionID]['GZipDump'] = false;
        $_SESSION[$this->sessionID]['BZipDump'] = false;
        $_SESSION[$this->sessionID]['ZipDump'] = false;

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        /**
         * @var mixed $secret
         * @psalm-suppress TypeDoesNotContainType
         */
        $secret = $_SESSION[$this->sessionID]['blowfish_secret'] ?? '';
        self::assertIsString($secret);
        self::assertSame(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, mb_strlen($secret, '8bit'));
        $messages = $_SESSION['messages'] ?? null;
        self::assertIsArray($messages);
        self::assertArrayHasKey('notice', $messages);
        self::assertIsArray($messages['notice']);
        self::assertArrayHasKey('blowfish_secret_created', $messages['notice']);
        self::assertArrayNotHasKey('error', $messages);
    }

    /**
     * @requires extension zip
     * @requires extension bz2
     */
    public function testBlowfishWithInvalidSecret(): void
    {
        $_SESSION[$this->sessionID] = [];
        $_SESSION[$this->sessionID]['blowfish_secret'] = str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES + 1);
        $_SESSION[$this->sessionID]['Servers'] = [
            '1' => [
                'host' => 'localhost',
                'ssl' => true,
                'auth_type' => 'cookie',
                'AllowRoot' => false,
            ],
        ];

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        /**
         * @var mixed $secret
         * @psalm-suppress TypeDoesNotContainType
         */
        $secret = $_SESSION[$this->sessionID]['blowfish_secret'] ?? '';
        self::assertIsString($secret);
        self::assertSame(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, mb_strlen($secret, '8bit'));
        $messages = $_SESSION['messages'] ?? null;
        self::assertIsArray($messages);
        self::assertArrayHasKey('notice', $messages);
        self::assertIsArray($messages['notice']);
        self::assertArrayHasKey('blowfish_secret_created', $messages['notice']);
        self::assertArrayNotHasKey('error', $messages);
    }

    /**
     * @requires extension zip
     * @requires extension bz2
     */
    public function testBlowfishWithValidSecret(): void
    {
        $_SESSION[$this->sessionID] = [];
        $_SESSION[$this->sessionID]['blowfish_secret'] = str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $_SESSION[$this->sessionID]['Servers'] = ['1' => ['host' => 'localhost', 'auth_type' => 'cookie']];

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        /**
         * @var mixed $secret
         * @psalm-suppress TypeDoesNotContainType
         */
        $secret = $_SESSION[$this->sessionID]['blowfish_secret'] ?? '';
        self::assertIsString($secret);
        self::assertSame(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, mb_strlen($secret, '8bit'));
        $messages = $_SESSION['messages'] ?? null;
        self::assertIsArray($messages);
        self::assertArrayHasKey('notice', $messages);
        self::assertIsArray($messages['notice']);
        self::assertArrayNotHasKey('blowfish_secret_created', $messages['notice']);
        self::assertArrayNotHasKey('error', $messages);
    }
}
