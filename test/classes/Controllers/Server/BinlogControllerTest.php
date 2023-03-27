<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PhpMyAdmin\Utils\SessionCache;

/** @covers \PhpMyAdmin\Controllers\Server\BinlogController */
class BinlogControllerTest extends AbstractTestCase
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
        $GLOBALS['dbi'] = $this->dbi;

        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        SessionCache::set('profiling_supported', true);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new BinlogController($response, new Template(), $GLOBALS['dbi']);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['log', null, 'index1'], ['pos', 0, '3']]);
        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString('Select binary log to view', $actual);
        $this->assertStringContainsString('<option value="index1" selected>', $actual);
        $this->assertStringContainsString('<option value="index2">', $actual);

        $this->assertStringContainsString('Your SQL query has been executed successfully', $actual);

        $this->assertStringContainsString("SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10", $actual);

        $this->assertStringContainsString(
            '<table class="table table-striped table-hover align-middle" id="binlogTable">',
            $actual,
        );

        $urlNavigation = Url::getFromRoute('/server/binlog') . '" data-post="log=index1&pos=3&'
            . 'is_full_query=1&server=1&';
        $this->assertStringContainsString($urlNavigation, $actual);
        $this->assertStringContainsString('title="Previous"', $actual);

        $this->assertStringContainsString('Log name', $actual);
        $this->assertStringContainsString('Position', $actual);
        $this->assertStringContainsString('Event type', $actual);
        $this->assertStringContainsString('Server ID', $actual);
        $this->assertStringContainsString('Original position', $actual);

        $this->assertStringContainsString('index1_Log_name', $actual);
        $this->assertStringContainsString('index1_Pos', $actual);
        $this->assertStringContainsString('index1_Event_type', $actual);
        $this->assertStringContainsString('index1_Server_id', $actual);
        $this->assertStringContainsString('index1_Orig_log_pos', $actual);
        $this->assertStringContainsString('index1_Info', $actual);
    }
}
