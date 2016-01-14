<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for display_change_password.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;


require_once 'libraries/url_generating.lib.php';
require_once 'libraries/display_change_password.lib.php';

require_once 'libraries/database_interface.inc.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

require_once 'libraries/config.default.php';

/**
 * class PMA_DisplayChangePassword_Test
 *
 * this class is for testing display_change_password.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_DisplayChangePassword_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['Server']['user'] = "pma_user";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['PMA_PHP_SELF'] = "server_privileges.php";
        $GLOBALS['server'] = 0;
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();
        $_SESSION['relation'][$GLOBALS['server']] = "relation";
    }

    /**
     * Test for PMA_getHtmlForChangePassword
     *
     * @return void
     */
    public function testPMAGetHtmlForChangePassword()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";

        //Call the test function
        $html = PMA_getHtmlForChangePassword('change_pw', $username, $hostname);

        //PMA_PHP_SELF
        $this->assertContains(
            $GLOBALS['PMA_PHP_SELF'],
            $html
        );

        //PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs(),
            $html
        );

        //$username & $hostname
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($hostname),
            $html
        );

        //labels
        $this->assertContains(
            __('Change password'),
            $html
        );
        $this->assertContains(
            __('No Password'),
            $html
        );
        $this->assertContains(
            __('Password:'),
            $html
        );
        $this->assertContains(
            __('Password:'),
            $html
        );
    }
}
