<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Server\PluginsController */
class PluginsControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['text_dir'] = 'ltr';

        parent::setGlobalConfig();

        parent::setTheme();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
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
        $resultStub->expects($this->exactly(1))
            ->method('fetchAllAssoc')
            ->will($this->onConsecutiveCalls([$row]));

        $response = new ResponseRenderer();

        $controller = new PluginsController($response, new Template(), new Plugins($dbi), $GLOBALS['dbi']);
        $this->dummyDbi->addSelectDb('mysql');
        $controller($this->createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        //validate 1:Items
        $this->assertStringContainsString('<th scope="col">Plugin</th>', $actual);
        $this->assertStringContainsString('<th scope="col">Description</th>', $actual);
        $this->assertStringContainsString('<th scope="col">Version</th>', $actual);
        $this->assertStringContainsString('<th scope="col">Author</th>', $actual);
        $this->assertStringContainsString('<th scope="col">License</th>', $actual);

        //validate 2: one Item HTML
        $this->assertStringContainsString('plugin_name1', $actual);
        $this->assertStringContainsString('<td>plugin_description1</td>', $actual);
        $this->assertStringContainsString('<td>plugin_version1</td>', $actual);
        $this->assertStringContainsString('<td>plugin_author1</td>', $actual);
        $this->assertStringContainsString('<td>plugin_license1</td>', $actual);
    }
}
