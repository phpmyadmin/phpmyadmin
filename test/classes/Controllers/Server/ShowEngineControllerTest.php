<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\ShowEngineController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
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

        $GLOBALS['text_dir'] = 'ltr';

        parent::setGlobalConfig();

        parent::setTheme();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testShowEngine(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $response = new ResponseRenderer();
        $this->dummyDbi->addSelectDb('mysql');
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withAttribute('routeVars', ['engine' => 'Pbxt', 'page' => 'page']);

        (new ShowEngineController($response, new Template(), DatabaseInterface::getInstance()))($request);

        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        $enginePlugin = StorageEngine::getEngine('Pbxt');

        $this->assertStringContainsString(
            htmlspecialchars($enginePlugin->getTitle()),
            $actual,
        );

        $this->assertStringContainsString(
            MySQLDocumentation::show($enginePlugin->getMysqlHelpPage()),
            $actual,
        );

        $this->assertStringContainsString(
            htmlspecialchars($enginePlugin->getComment()),
            $actual,
        );

        $this->assertStringContainsString(
            __('Variables'),
            $actual,
        );
        $this->assertStringContainsString('index.php?route=/server/engines/Pbxt/Documentation', $actual);
        $this->assertStringContainsString(
            $enginePlugin->getSupportInformationMessage(),
            $actual,
        );
        $this->assertStringContainsString(
            'There is no detailed status information available for this storage engine.',
            $actual,
        );
    }
}
