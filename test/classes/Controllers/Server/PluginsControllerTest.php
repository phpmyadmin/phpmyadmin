<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\PluginsController
 */
class PluginsControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * Test for index method
     */
    public function testIndex(): void
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

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('query')
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->exactly(2))
            ->method('fetchAssoc')
            ->will($this->onConsecutiveCalls($row, []));

        $response = new ResponseRenderer();

        $controller = new PluginsController($response, new Template(), new Plugins($dbi), $GLOBALS['dbi']);
        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        //validate 1:Items
        self::assertStringContainsString('<th scope="col">Plugin</th>', $actual);
        self::assertStringContainsString('<th scope="col">Description</th>', $actual);
        self::assertStringContainsString('<th scope="col">Version</th>', $actual);
        self::assertStringContainsString('<th scope="col">Author</th>', $actual);
        self::assertStringContainsString('<th scope="col">License</th>', $actual);

        //validate 2: one Item HTML
        self::assertStringContainsString('plugin_name1', $actual);
        self::assertStringContainsString('<td>plugin_description1</td>', $actual);
        self::assertStringContainsString('<td>plugin_version1</td>', $actual);
        self::assertStringContainsString('<td>plugin_author1</td>', $actual);
        self::assertStringContainsString('<td>plugin_license1</td>', $actual);
    }
}
