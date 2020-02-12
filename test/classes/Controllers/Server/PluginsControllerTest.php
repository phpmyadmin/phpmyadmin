<?php
/**
 * Holds PluginsControllerTest class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PluginsController class
 */
class PluginsControllerTest extends TestCase
{
    /**
     * Prepares environment for the test.
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

        $response = new Response();

        $controller = new PluginsController(
            $response,
            $dbi,
            new Template(),
            new Plugins($dbi)
        );
        $controller->index();
        $actual = $response->getHTMLResult();

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
