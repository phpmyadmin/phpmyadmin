<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerPluginsControllerTest class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;
use PMA\libraries\controllers\server\ServerPluginsController;

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * Tests for ServerPluginsController class
 *
 * @package PhpMyAdmin-test
 */
class ServerPluginsControllerTest extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();
    }

    /**
     * Test for _getPluginsHtml() method
     *
     * @return void
     */
    public function testPMAGetPluginAndModuleInfo()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        /**
         * Prepare plugin list
         */
        $row = array();
        $row["plugin_name"] = "plugin_name1";
        $row["plugin_type"] = "plugin_type1";
        $row["plugin_type_version"] = "plugin_version1";
        $row["plugin_author"] = "plugin_author1";
        $row["plugin_license"] = "plugin_license1";
        $row["plugin_description"] = "plugin_description1";
        $row["is_active"] = true;

        $plugins = array();
        $plugins[$row['plugin_type']][] = $row;

        $class = new ReflectionClass('\PMA\libraries\controllers\server\ServerPluginsController');
        $method = $class->getMethod('_getPluginsHtml');
        $method->setAccessible(true);
        $prop = $class->getProperty('plugins');
        $prop->setAccessible(true);

        $ctrl = new ServerPluginsController();
        $prop->setValue($ctrl, $plugins);
        $html = $method->invoke();

        //validate 1:Items
        $this->assertContains(
            '<th>Plugin</th>',
            $html
        );
        $this->assertContains(
            '<th>Description</th>',
            $html
        );
        $this->assertContains(
            '<th>Version</th>',
            $html
        );
        $this->assertContains(
            '<th>Author</th>',
            $html
        );
        $this->assertContains(
            '<th>License</th>',
            $html
        );

        //validate 2: one Item HTML
        $this->assertContains(
            '<th>plugin_name1</th>',
            $html
        );
        $this->assertContains(
            '<td>plugin_description1</td>',
            $html
        );
        $this->assertContains(
            '<td>plugin_version1</td>',
            $html
        );
        $this->assertContains(
            '<td>plugin_author1</td>',
            $html
        );
        $this->assertContains(
            '<td>plugin_license1</td>',
            $html
        );
    }
}
