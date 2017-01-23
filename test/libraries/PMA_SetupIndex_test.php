<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under setup/lib/index.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test
 */
use PMA\libraries\config\ConfigFile;
use PMA\libraries\config\ServerConfigChecks;

require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'setup/lib/index.lib.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * tests for methods under setup/lib/index.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_SetupIndex_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['cfg']['ProxyUrl'] = '';
    }

    /**
     * Test for PMA_messagesBegin()
     *
     * @return void
     */
    public function testPMAmessagesBegin()
    {
        $_SESSION['messages'] = array(
            array(
                array('foo'),
                array('bar')
            )
        );

        PMA_messagesBegin();

        $this->assertEquals(
            array(
                array(
                    array(
                        0 => 'foo',
                        'fresh' => false,
                        'active' => false
                    ),
                    array(
                        0 => 'bar',
                        'fresh' => false,
                        'active' => false
                    )
                )
            ),
            $_SESSION['messages']
        );

        // case 2

        unset($_SESSION['messages']);
        PMA_messagesBegin();
        $this->assertEquals(
            array(
                'error' => array(),
                'notice' => array()
            ),
            $_SESSION['messages']
        );
    }

    /**
     * Test for PMA_messagesSet
     *
     * @return void
     */
    public function testPMAmessagesSet()
    {
        PMA_messagesSet('type', '123', 'testTitle', 'msg');

        $this->assertEquals(
            array(
                'fresh' => true,
                'active' => true,
                'title' => 'testTitle',
                'message' => 'msg'
            ),
            $_SESSION['messages']['type']['123']
        );
    }

    /**
     * Test for PMA_messagesEnd
     *
     * @return void
     */
    public function testPMAmessagesEnd()
    {
        $_SESSION['messages'] = array(
            array(
                array('msg' => 'foo', 'active' => false),
                array('msg' => 'bar', 'active' => true),
            )
        );

        PMA_messagesEnd();

        $this->assertEquals(
            array(
                array(
                    '1' => array(
                        'msg' => 'bar',
                        'active' => 1
                    )
                )
            ),
            $_SESSION['messages']
        );
    }

    /**
     * Test for PMA_messagesShowHtml
     *
     * @return void
     */
    public function testPMAMessagesShowHTML()
    {
        $_SESSION['messages'] = array(
            'type' => array(
                array('title' => 'foo', 'message' => '123', 'fresh' => false),
                array('title' => 'bar', 'message' => '321', 'fresh' => true),
            )
        );

        ob_start();
        PMA_messagesShowHtml();
        $result = ob_get_clean();

        $this->assertContains(
            '<div class="type hiddenmessage" id="0"><h4>foo</h4>123</div>',
            $result
        );

        $this->assertContains(
            '<div class="type" id="1"><h4>bar</h4>321</div>',
            $result
        );
    }

    /**
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

        $reflection = new \ReflectionProperty('PMA\libraries\config\ConfigFile', '_id');
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
