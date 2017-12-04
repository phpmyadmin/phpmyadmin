<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionProperty;

/**
 * Tests for ServeConfigChecks class
 *
 * @package PhpMyAdmin-test
 */
class ServeConfigChecksTest extends PmaTestCase
{
    private $sessionID;

    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['cfg']['AvailableCharsets'] = array();
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 0;

        $cf = new ConfigFile();
        $GLOBALS['ConfigFile'] = $cf;

        $reflection = new ReflectionProperty('PhpMyAdmin\Config\ConfigFile', '_id');
        $reflection->setAccessible(true);
        $this->sessionID = $reflection->getValue($cf);

        unset($_SESSION['messages']);
        unset($_SESSION[$this->sessionID]);
    }

    public function testManyErrors()
    {
        $_SESSION[$this->sessionID]['Servers'] = array(
            '1' => array(
                'host' => 'localhost',
                'ssl' => false,
                'extension' => 'mysql',
                'auth_type' => 'config',
                'user' => 'username',
                'password' => 'password',
                'AllowRoot' => true,
                'AllowNoPassword' => true,
            )
        );

        $_SESSION[$this->sessionID]['AllowArbitraryServer'] = true;
        $_SESSION[$this->sessionID]['LoginCookieValidity'] = 5000;
        $_SESSION[$this->sessionID]['LoginCookieStore'] = 4000;
        $_SESSION[$this->sessionID]['SaveDir'] = true;
        $_SESSION[$this->sessionID]['TempDir'] = true;
        $_SESSION[$this->sessionID]['GZipDump'] = true;
        $_SESSION[$this->sessionID]['BZipDump'] = true;
        $_SESSION[$this->sessionID]['ZipDump'] = true;

        $configChecker = $this->getMockbuilder('PhpMyAdmin\Config\ServerConfigChecks')
            ->setMethods(['functionExists'])
            ->setConstructorArgs([$GLOBALS['ConfigFile']])
            ->getMock();

        // Configure the stub.
        $configChecker->method('functionExists')->willReturn(false);

        $configChecker->performConfigChecks();

        $this->assertEquals(
            array(
                'Servers/1/ssl',
                'Servers/1/auth_type',
                'Servers/1/AllowNoPassword',
                'AllowArbitraryServer',
                'LoginCookieValidity',
                'SaveDir',
                'TempDir',
            ),
            array_keys($_SESSION['messages']['notice'])
        );

        $this->assertEquals(
            array(
                'LoginCookieValidity',
                'GZipDump',
                'BZipDump',
                'ZipDump_import',
                'ZipDump_export',
            ),
            array_keys($_SESSION['messages']['error'])
        );
    }

    public function testBlowfishCreate()
    {
        $_SESSION[$this->sessionID]['Servers'] = array(
            '1' => array(
                'host' => 'localhost',
                'ssl' => true,
                'extension' => 'mysqli',
                'auth_type' => 'cookie',
                'AllowRoot' => false
            )
        );

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
            array('blowfish_secret_created'),
            array_keys($_SESSION['messages']['notice'])
        );

        $this->assertArrayNotHasKey(
            'error',
            $_SESSION['messages']
        );
    }

    public function testBlowfish()
    {

        $_SESSION[$this->sessionID]['blowfish_secret'] = 'sec';

        $_SESSION[$this->sessionID]['Servers'] = array(
            '1' => array(
                'host' => 'localhost',
                'auth_type' => 'cookie'
            )
        );

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        $this->assertArrayHasKey(
            'blowfish_warnings2',
            $_SESSION['messages']['error']
        );
    }
}
