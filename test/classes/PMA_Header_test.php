<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Header class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Header.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/vendor_config.php';
require_once 'libraries/select_lang.lib.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * Test for PMA_Header class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Header_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        if (!defined('PMA_IS_WINDOWS')) {
            define('PMA_IS_WINDOWS', false);
        }
        $GLOBALS['server'] = 0;
        $GLOBALS['lang'] = 'en';
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['server'] = 'server';
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table1';
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
    }

    /**
     * Test for disable
     *
     * @return void
     */
    public function testDisable()
    {
        $header = new PMA_Header();
        $header->disable();
        $this->assertEquals(
            '',
            $header->getDisplay()
        );
    }

    /**
     * Test for print view
     *
     * @return void
     */
    public function testPrintView()
    {
        $header = new PMA_Header();
        $header->enablePrintView();
        $this->assertContains(
            'Print view',
            $header->getDisplay()
        );
    }


}
