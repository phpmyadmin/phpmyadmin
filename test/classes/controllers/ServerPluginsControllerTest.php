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
use PMA\libraries\di\Container;

require_once 'libraries/database_interface.inc.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for ServerPluginsController class
 *
 * @package PhpMyAdmin-test
 */
class ServerPluginsControllerTest extends PMATestCase
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

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->will($this->returnValue(true));
        $dbi->expects($this->at(1))
            ->method('fetchAssoc')
            ->will($this->returnValue($row));
        $dbi->expects($this->at(2))
            ->method('fetchAssoc')
            ->will($this->returnValue(false));
        $dbi->expects($this->once())
            ->method('freeResult')
            ->will($this->returnValue(true));

        $container = Container::getDefaultContainer();
        $container->set('dbi', $dbi);

        $class = new ReflectionClass('\PMA\libraries\controllers\server\ServerPluginsController');
        $method = $class->getMethod('_getPluginsHtml');
        $method->setAccessible(true);

        $ctrl = new ServerPluginsController();
        $html = $method->invoke($ctrl);

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
            'plugin_name1',
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
