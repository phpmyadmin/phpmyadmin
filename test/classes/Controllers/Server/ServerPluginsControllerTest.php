<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerPluginsControllerTest class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\ServerPluginsController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Theme;
use ReflectionClass;

/**
 * Tests for ServerPluginsController class
 *
 * @package PhpMyAdmin-test
 */
class ServerPluginsControllerTest extends PmaTestCase
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
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
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
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
        $container->set('PhpMyAdmin\Response', new ResponseStub());
        $container->alias('response', 'PhpMyAdmin\Response');
        $container->set('dbi', $dbi);

        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Server\ServerPluginsController');
        $method = $class->getMethod('_getPluginsHtml');
        $method->setAccessible(true);

        $ctrl = new ServerPluginsController(
            $container->get('response'),
            $container->get('dbi')
        );
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
