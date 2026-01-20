<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PluginsController::class)]
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

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;
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

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::once())
            ->method('query')
            ->willReturn($resultStub);
        $resultStub->expects(self::exactly(1))
            ->method('fetchAllAssoc')
            ->willReturn([$row]);

        $response = new ResponseRenderer();

        $controller = new PluginsController($response, new Plugins($dbi), $this->dbi);
        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
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
