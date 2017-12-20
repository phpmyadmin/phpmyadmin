<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerEnginesControllerTest class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\ServerEnginesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use ReflectionClass;

/**
 * Tests for ServerEnginesController class
 *
 * @package PhpMyAdmin-test
 */
class ServerEnginesControllerTest extends PmaTestCase
{
    /**
     * @var \PhpMyAdmin\Di\Container
     */
    private $container;

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

        $this->container = Container::getDefaultContainer();
        $this->container->set('PhpMyAdmin\Response', new ResponseStub());
        $this->container->alias('response', 'PhpMyAdmin\Response');
    }

    /**
     * Tests for _getHtmlForAllServerEngines() method
     *
     * @return void
     */
    public function testGetHtmlForAllServerEngines()
    {
        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Server\ServerEnginesController');
        $method = $class->getMethod('_getHtmlForAllServerEngines');
        $method->setAccessible(true);

        $ctrl = new ServerEnginesController(
            $this->container->get('response'),
            $this->container->get('dbi')
        );
        $html = $method->invoke($ctrl);

        //validate 1: Item header
        $this->assertContains(
            '<th>Storage Engine</th>',
            $html
        );
        $this->assertContains(
            '<th>Description</th>',
            $html
        );

        //validate 2: FEDERATED
        $this->assertContains(
            '<td>Federated MySQL storage engine</td>',
            $html
        );
        $this->assertContains(
            'FEDERATED',
            $html
        );
        $this->assertContains(
            'server_engines.php?engine=FEDERATED',
            $html
        );

        //validate 3: dummy
        $this->assertContains(
            '<td>dummy comment</td>',
            $html
        );
        $this->assertContains(
            'dummy',
            $html
        );
        $this->assertContains(
            'server_engines.php?engine=dummy',
            $html
        );
    }

    /**
     * Tests for _getHtmlForServerEngine() method
     *
     * @return void
     */
    public function testGetHtmlForServerEngine()
    {
        $_REQUEST['engine'] = "Pbxt";
        $_REQUEST['page'] = "page";
        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Server\ServerEnginesController');
        $method = $class->getMethod('_getHtmlForServerEngine');
        $method->setAccessible(true);

        $engine_plugin = StorageEngine::getEngine("Pbxt");
        $ctrl = new ServerEnginesController(
            $this->container->get('response'),
            $this->container->get('dbi')
        );
        $html = $method->invoke($ctrl, $engine_plugin);

        //validate 1: Engine title
        $this->assertContains(
            htmlspecialchars($engine_plugin->getTitle()),
            $html
        );

        //validate 2: Engine Mysql Help Page
        $this->assertContains(
            Util::showMySQLDocu($engine_plugin->getMysqlHelpPage()),
            $html
        );

        //validate 3: Engine Comment
        $this->assertContains(
            htmlspecialchars($engine_plugin->getComment()),
            $html
        );

        //validate 4: Engine Info Pages
        $this->assertContains(
            __('Variables'),
            $html
        );
        $this->assertContains(
            Url::getCommon(
                array('engine' => $_REQUEST['engine'], 'page' => "Documentation")
            ),
            $html
        );

        //validate 5: other items
        $this->assertContains(
            Url::getCommon(array('engine' => $_REQUEST['engine'])),
            $html
        );
        $this->assertContains(
            $engine_plugin->getSupportInformationMessage(),
            $html
        );
        $this->assertContains(
            'There is no detailed status information available for this '
            . 'storage engine.',
            $html
        );
    }
}
