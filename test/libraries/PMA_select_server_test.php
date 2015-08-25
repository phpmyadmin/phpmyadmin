<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for select_server.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/select_server.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * PMA_SelectServer_Test class
 *
 * this class is for testing select_server.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_SelectServer_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Test for PMA_selectServer
     *
     * @return void
     */
    public function testPMASelectServer()
    {
        $not_only_options = false;
        $omit_fieldset = false;

        $GLOBALS['cfg']['DefaultTabServer'] = "welcome";

        $GLOBALS['cfg']['Servers'] = array(
            '0' => array(
                'host'=>'host0',
                'port'=>'port0',
                'only_db'=>'only_db0',
                'user'=>'user0',
                'auth_type'=>'config',
            ),
            '1' => array(
                'host'=>'host1',
                'port'=>'port1',
                'only_db'=>'only_db1',
                'user'=>'user1',
                'auth_type'=>'config',
            ),
        );

        //$not_only_options=false & $omit_fieldset=false
        $html = PMA_selectServer($not_only_options, $omit_fieldset);
        $server = $GLOBALS['cfg']['Servers']['0'];

        //server items
        $this->assertContains(
            $server['host'],
            $html
        );
        $this->assertContains(
            $server['port'],
            $html
        );
        $this->assertContains(
            $server['only_db'],
            $html
        );
        $this->assertContains(
            $server['user'],
            $html
        );

        $not_only_options = true;
        $omit_fieldset = true;
        $GLOBALS['cfg']['DisplayServersList'] = null;

        //$not_only_options=true & $omit_fieldset=true
        $html = PMA_selectServer($not_only_options, $omit_fieldset);

        //$GLOBALS['cfg']['DefaultTabServer']
        $this->assertContains(
            PMA_Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabServer'], 'server'
            ),
            $html
        );

        //labels
        $this->assertContains(
            __('Current server:'),
            $html
        );
        $this->assertContains(
            '(' . __('Servers') . ')',
            $html
        );

        //server items
        $server = $GLOBALS['cfg']['Servers']['0'];
        $this->assertContains(
            $server['host'],
            $html
        );
        $this->assertContains(
            $server['port'],
            $html
        );
        $this->assertContains(
            $server['only_db'],
            $html
        );
        $this->assertContains(
            $server['user'],
            $html
        );
    }
}
