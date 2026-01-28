<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\VariablesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(VariablesController::class)]
class VariablesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';

        $this->data = new Data($this->dbi, $config);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new VariablesController($response, new Template(), $this->data, DatabaseInterface::getInstance());

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('<div class="card mb-3" id="tableFilter">', $html);
        self::assertStringContainsString('index.php?route=/server/status/variables', $html);

        self::assertStringContainsString(
            '<label class="col-12 col-form-label" for="filterText">Containing the word:</label>',
            $html,
        );

        self::assertStringContainsString('<label class="form-check-label" for="filterAlert">', $html);
        self::assertStringContainsString('Show only alert values', $html);
        self::assertStringContainsString('Filter by category', $html);
        self::assertStringContainsString('Show unformatted values', $html);

        self::assertStringContainsString('<div id="linkSuggestions" class="defaultLinks hide"', $html);

        self::assertStringContainsString('Related links:', $html);
        self::assertStringContainsString('Flush (close) all tables', $html);
        self::assertStringContainsString('<span class="status_binlog_cache">', $html);

        self::assertStringContainsString(
            '<table class="table table-striped table-hover table-sm" id="serverStatusVariables">',
            $html,
        );
        self::assertStringContainsString('<th scope="col">Variable</th>', $html);
        self::assertStringContainsString('<th scope="col">Value</th>', $html);
        self::assertStringContainsString('<th scope="col">Description</th>', $html);

        self::assertStringContainsString('Aborted clients', $html);
        self::assertStringContainsString('<span class="text-success">', $html);
        self::assertStringContainsString('Aborted connects', $html);
        self::assertStringContainsString('Com delete multi', $html);
        self::assertStringContainsString('Com create function', $html);
        self::assertStringContainsString('Com empty query', $html);
    }
}
