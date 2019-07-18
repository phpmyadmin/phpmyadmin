<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds PluginsControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PluginsController class
 *
 * @package PhpMyAdmin-test
 */
class PluginsControllerTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * Test for index method
     *
     * @return void
     */
    public function testIndex()
    {
        /**
         * Prepare plugin list
         */
        $row = [
            'PLUGIN_NAME' => 'plugin_name1',
            'PLUGIN_TYPE' => 'plugin_type1',
            'PLUGIN_VERSION' => 'plugin_version1',
            'PLUGIN_AUTHOR' => 'plugin_author1',
            'PLUGIN_LICENSE' => 'plugin_license1',
            'PLUGIN_DESCRIPTION' => 'plugin_description1',
            'PLUGIN_STATUS' => 'ACTIVE',
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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

        $controller = new PluginsController(
            Response::getInstance(),
            $dbi,
            new Template(),
            new Plugins($dbi)
        );
        $actual = $controller->index();

        //validate 1:Items
        $this->assertStringContainsString(
            '<th>Plugin</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<th>Description</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<th>Version</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<th>Author</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<th>License</th>',
            $actual
        );

        //validate 2: one Item HTML
        $this->assertStringContainsString(
            'plugin_name1',
            $actual
        );
        $this->assertStringContainsString(
            '<td>plugin_description1</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>plugin_version1</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>plugin_author1</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>plugin_license1</td>',
            $actual
        );
    }
}
