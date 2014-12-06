<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Menu class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Menu.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/vendor_config.php';
require_once 'libraries/select_lang.lib.php';
require_once 'libraries/relation.lib.php';

/**
 * Test for PMA_Menu class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Menu_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'both';
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['DefaultTabServer'] = 'main.php';
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'db_structure.php';
        $GLOBALS['cfg']['DefaultTabTable'] = 'sql.php';
        $GLOBALS['cfg']['OBGzip'] = false;
        $GLOBALS['cfg']['NaturalOrder'] = true;
        $GLOBALS['cfg']['TabsMode'] = 'both';
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['server'] = 'server';
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table1';
    }

    /**
     * Server menu test
     *
     * @return void
     */
    function testServer()
    {
        $menu = new PMA_Menu('server', '', '');
        $this->assertContains(
            'floating_menubar',
            $menu->getDisplay()
        );
    }

    /**
     * Database menu test
     *
     * @return void
     */
    function testDatabase()
    {
        $menu = new PMA_Menu('server', 'pma_test', '');
        $this->assertContains(
            'floating_menubar',
            $menu->getDisplay()
        );
    }

    /**
     * Table menu test
     *
     * @return void
     */
    function testTable()
    {
        $menu = new PMA_Menu('server', 'pma_test', 'table1');
        $this->assertContains(
            'floating_menubar',
            $menu->getDisplay()
        );
    }

    /**
     * Table menu display test
     *
     * @return void
     */
    function testTableDisplay()
    {
        $menu = new PMA_Menu('server', 'pma_test', '');
        $this->expectOutputString(
            $menu->getDisplay()
        );
        $menu->display();
    }


    /**
     * Table menu setTable test
     *
     * @return void
     */
    function testSetTable()
    {
        $menu = new PMA_Menu('server', 'pma_test', '');
        $menu->setTable('table1');
        $this->assertContains(
            'table1',
            $menu->getDisplay()
        );
    }
}
