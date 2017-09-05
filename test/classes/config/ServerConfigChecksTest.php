<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\ServerConfigChecks;

require_once 'test/PMATestCase.php';

/**
 * Tests for ServeConfigChecks class
 *
 * @package PhpMyAdmin-test
 */
class ServeConfigChecksTest extends PMATestCase
{
    /**
     * Test for ServerConfigChecks::performConfigChecks
     *
     * @return void
     * @group medium
     */
    public function testServerConfigChecksPerformConfigChecks()
    {

        $GLOBALS['cfg']['AvailableCharsets'] = array();
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 0;

        $cf = new ConfigFile();
        $GLOBALS['ConfigFile'] = $cf;

        $reflection = new \ReflectionProperty('PhpMyAdmin\Config\ConfigFile', '_id');
        $reflection->setAccessible(true);
        $sessionID = $reflection->getValue($cf);

        $_SESSION[$sessionID]['Servers'] = array(
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

        $_SESSION[$sessionID]['AllowArbitraryServer'] = true;
        $_SESSION[$sessionID]['LoginCookieValidity'] = 5000;
        $_SESSION[$sessionID]['LoginCookieStore'] = 4000;
        $_SESSION[$sessionID]['SaveDir'] = true;
        $_SESSION[$sessionID]['TempDir'] = true;
        $_SESSION[$sessionID]['GZipDump'] = true;
        $_SESSION[$sessionID]['BZipDump'] = true;
        $_SESSION[$sessionID]['ZipDump'] = true;

        $noticeArrayKeys = array(
            'TempDir',
            'SaveDir',
            'LoginCookieValidity',
            'AllowArbitraryServer',
            'Servers/1/AllowNoPassword',
            'Servers/1/auth_type',
            'Servers/1/ssl'
        );

        $errorArrayKeys = array(
            'LoginCookieValidity'
        );

        if (@!function_exists('gzopen') || @!function_exists('gzencode')) {
            $errorArrayKeys[] = 'GZipDump';
        }

        if (@!function_exists('bzopen') || @!function_exists('bzcompress')) {
            $errorArrayKeys[] = 'BZipDump';
        }

        if (!@function_exists('zip_open')) {
            $errorArrayKeys[] = 'ZipDump_import';
        }

        if (!@function_exists('gzcompress')) {
            $errorArrayKeys[] = 'ZipDump_export';
        }

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        foreach ($noticeArrayKeys as $noticeKey) {
            $this->assertArrayHasKey(
                $noticeKey,
                $_SESSION['messages']['notice']
            );
        }

        foreach ($errorArrayKeys as $errorKey) {
            $this->assertArrayHasKey(
                $errorKey,
                $_SESSION['messages']['error']
            );
        }

        // Case 2

        unset($_SESSION['messages']);
        unset($_SESSION[$sessionID]);

        $_SESSION[$sessionID]['Servers'] = array(
            '1' => array(
                'host' => 'localhost',
                'ssl' => true,
                'extension' => 'mysqli',
                'auth_type' => 'cookie',
                'AllowRoot' => false
            )
        );

        $_SESSION[$sessionID]['AllowArbitraryServer'] = false;
        $_SESSION[$sessionID]['LoginCookieValidity'] = -1;
        $_SESSION[$sessionID]['LoginCookieStore'] = 0;
        $_SESSION[$sessionID]['SaveDir'] = '';
        $_SESSION[$sessionID]['TempDir'] = '';
        $_SESSION[$sessionID]['GZipDump'] = false;
        $_SESSION[$sessionID]['BZipDump'] = false;
        $_SESSION[$sessionID]['ZipDump'] = false;

        $configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
        $configChecker->performConfigChecks();

        $this->assertArrayHasKey(
            'blowfish_secret_created',
            $_SESSION['messages']['notice']
        );

        foreach ($noticeArrayKeys as $noticeKey) {
            $this->assertArrayNotHasKey(
                $noticeKey,
                $_SESSION['messages']['notice']
            );
        }

        $this->assertArrayNotHasKey(
            'error',
            $_SESSION['messages']
        );

        // Case 3

        $_SESSION[$sessionID]['blowfish_secret'] = 'sec';

        $_SESSION[$sessionID]['Servers'] = array(
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
