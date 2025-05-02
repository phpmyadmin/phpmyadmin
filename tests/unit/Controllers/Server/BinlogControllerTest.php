<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BinlogController::class)]
class BinlogControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $config = Config::getInstance();
        $config->settings['MaxRows'] = 10;
        $config->set('ServerDefault', 0);
        $config->selectedServer['DisableIS'] = false;

        Current::$database = 'db';
        Current::$table = 'table';

        SessionCache::set('profiling_supported', true);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new BinlogController($response, DatabaseInterface::getInstance(), Config::getInstance());

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'log' => 'index1',
                'pos' => '3',
            ]);
        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        self::assertStringContainsString('Select binary log to view', $actual);
        self::assertStringContainsString('<option value="index1" selected>', $actual);
        self::assertStringContainsString('<option value="index2">', $actual);

        self::assertStringContainsString('Your SQL query has been executed successfully', $actual);

        self::assertStringContainsString("SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10", $actual);

        self::assertStringContainsString(
            '<table class="table table-striped table-hover align-middle" id="binlogTable">',
            $actual,
        );

        $urlNavigation = Url::getFromRoute('/server/binlog') . '" data-post="log=index1&pos=3&'
            . 'is_full_query=1&server=1&';
        self::assertStringContainsString($urlNavigation, $actual);
        self::assertStringContainsString('title="Previous"', $actual);

        self::assertStringContainsString('Log name', $actual);
        self::assertStringContainsString('Position', $actual);
        self::assertStringContainsString('Event type', $actual);
        self::assertStringContainsString('Server ID', $actual);
        self::assertStringContainsString('Original position', $actual);

        self::assertStringContainsString('index1_Log_name', $actual);
        self::assertStringContainsString('index1_Pos', $actual);
        self::assertStringContainsString('index1_Event_type', $actual);
        self::assertStringContainsString('index1_Server_id', $actual);
        self::assertStringContainsString('index1_Orig_log_pos', $actual);
        self::assertStringContainsString('index1_Info', $actual);
    }
}
