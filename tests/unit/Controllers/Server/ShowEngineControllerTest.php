<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\ShowEngineController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;
use function htmlspecialchars;

#[CoversClass(ShowEngineController::class)]
class ShowEngineControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testShowEngine(): void
    {
        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $response = new ResponseRenderer();
        $this->dummyDbi->addSelectDb('mysql');
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withAttribute('routeVars', ['engine' => 'Pbxt', 'page' => 'page']);

        (new ShowEngineController($response, DatabaseInterface::getInstance()))($request);

        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        $enginePlugin = StorageEngine::getEngine('Pbxt');

        self::assertStringContainsString(
            htmlspecialchars($enginePlugin->getTitle()),
            $actual,
        );

        self::assertStringContainsString(
            MySQLDocumentation::show($enginePlugin->getMysqlHelpPage()),
            $actual,
        );

        self::assertStringContainsString(
            htmlspecialchars($enginePlugin->getComment()),
            $actual,
        );

        self::assertStringContainsString(
            __('Variables'),
            $actual,
        );
        self::assertStringContainsString('index.php?route=/server/engines/Pbxt/Documentation', $actual);
        self::assertStringContainsString(
            $enginePlugin->getSupportInformationMessage(),
            $actual,
        );
        self::assertStringContainsString(
            'There is no detailed status information available for this storage engine.',
            $actual,
        );
    }
}
