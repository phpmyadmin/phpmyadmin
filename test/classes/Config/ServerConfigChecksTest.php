<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionException;
use ReflectionProperty;
use function array_keys;

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
        $reflection->setAccessible(true);
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
            ->setMethods(['functionExists'])
            ->setConstructorArgs([$GLOBALS['ConfigFile']])
            ->getMock();

        // Configure the stub.
        $configChecker->method('functionExists')->willReturn(false);

        $configChecker->performConfigChecks();

        $this->assertEquals(
            [
                'Servers/1/ssl',
                'Servers/1/auth_type',
                'Servers/1/AllowNoPassword',
                'AllowArbitraryServer',
                'LoginCookieValidity',
                'SaveDir',
                'TempDir',
            ],
            array_keys($_SESSION['messages']['notice'])
        );

        $this->assertEquals(
            [
                'LoginCookieValidity',
                'GZipDump',
                'BZipDump',
                'ZipDump_import',
                'ZipDump_export',
            ],
            array_keys($_SESSION['messages']['error'])
        );
    }

    public function testBlowfishCreate(): void
    {
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

        $this->assertEquals(
            ['blowfish_secret_created'],
            array_keys($_SESSION['messages']['notice'])
        );

        $this->assertArrayNotHasKey(
            'error',
            $_SESSION['messages']
        );
    }

    public function testBlowfish(): void
    {
        $_SESSION[$this->sessionID]['blowfish_secret'] = 'sec';

        $_SESSION[$this->sessionID]['Servers'] = [
            '1' => [
                'host' => 'localhost',
                'auth_type' => 'cookie',
            ],
        ];

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        $this->assertArrayHasKey(
            'blowfish_warnings2',
            $_SESSION['messages']['error']
        );
    }
}
