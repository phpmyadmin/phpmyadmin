<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_plugins.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_plugins.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * PMA_ServerPlugins_Test class
 *
 * this class is for testing server_plugins.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerPlugins_Test extends PHPUnit_Framework_TestCase
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
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Test for PMA_getPluginAndModuleInfo
     *
     * @return void
     */
    public function testPMAGetPluginAndModuleInfo()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        /**
         * Prepare plugin list
         */

        $plugins = array();

        $row = array();
        $row["plugin_name"] = "plugin_name1";
        $row["plugin_type"] = "plugin_type1";
        $row["module_name"] = "module_name1";
        $row["module_library"] = "module_library1";
        $row["module_version"] = "module_version1";
        $row["module_author"] = "module_author1";
        $row["module_license"] = "module_license1";
        $row["module_description"] = "module_description1";
        $row["is_active"] = true;
        $plugins[$row['plugin_type']][] = $row;

        $html = PMA_getPluginTab($plugins);

        //validate 1:Items
        $this->assertContains(
            '<th>Plugin</th>',
            $html
        );
        $this->assertContains(
            '<th>Module</th>',
            $html
        );
        $this->assertContains(
            '<th>Library</th>',
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
            '<td>module_name1</td>',
            $html
        );
        $this->assertContains(
            '<td>module_library1</td>',
            $html
        );
        $this->assertContains(
            '<td>module_version1</td>',
            $html
        );
        $this->assertContains(
            '<td>module_author1</td>',
            $html
        );
        $this->assertContains(
            '<td>module_license1</td>',
            $html
        );
    }
}
