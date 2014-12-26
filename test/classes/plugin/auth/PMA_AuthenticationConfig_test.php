<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for AuthenticationConfig class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/plugins/auth/AuthenticationConfig.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Error_Handler.class.php';
require_once 'libraries/Response.class.php';
/**
 * tests for AuthenticationConfig class
 *
 * @package PhpMyAdmin-test
 */
class PMA_AuthenticationConfig_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['token_provided'] = true;
        $GLOBALS['token_mismatch'] = false;
        $this->object = new AuthenticationConfig();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for AuthenticationConfig::auth
     *
     * @return void
     */
    public function testAuth()
    {
        $this->assertTrue(
            $this->object->auth()
        );
    }

    /**
     * Test for AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testAuthCheck()
    {
        $this->assertTrue(
            $this->object->authCheck()
        );
    }

    /**
     * Test for AuthenticationConfig::authSetUser
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        $this->assertTrue(
            $this->object->authSetUser()
        );
    }

    /**
     * Test for AuthenticationConfig::authFails
     *
     * @return void
     */
    public function testAuthFails()
    {
        $removeConstant = false;
        $GLOBALS['error_handler'] = new PMA_Error_Handler;
        $GLOBALS['cfg']['Servers'] = array(1);
        $GLOBALS['allowDeny_forbidden'] = false;
        if (!defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'chrome');

            $removeConstant = true;

            if (! PMA_HAS_RUNKIT) {
                $this->markTestSkipped('Cannot remove constant');
            }
        }

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $result = $this->object->authFails();
        $html = ob_get_clean();

        $this->assertTrue(
            $result
        );

        $this->assertContains(
            'You probably did not create a configuration file. You might want ' .
            'to use the <a href="setup/">setup script</a> to create one.',
            $html
        );

        $this->assertContains(
            '<strong>MySQL said: </strong><a href="./url.php?url=http%3A%2F%2F' .
            'dev.mysql.com%2Fdoc%2Frefman%2F5.6%2Fen%2Ferror-messages-server.html"' .
            ' target="mysql_doc">' .
            '<img src="themes/dot.gif" title="Documentation" alt="Documentation" ' .
            'class="icon ic_b_help" /></a>',
            $html
        );

        $this->assertContains(
            'Cannot connect: invalid settings.',
            $html
        );

        $this->assertContains(
            '<a href="index.php?server=0&amp;lang=en'
            . '&amp;collation_connection=utf-8&amp;token=token" '
            . 'class="button disableAjax">Retry to connect</a>',
            $html
        );
        if ($removeConstant) {
            runkit_constant_remove('PMA_USR_BROWSER_AGENT');
        }
    }
}
?>
