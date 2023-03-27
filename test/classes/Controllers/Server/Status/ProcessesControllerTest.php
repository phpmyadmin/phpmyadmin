<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Server\Status\ProcessesController */
class ProcessesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Data $data;

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
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->data = new Data($this->dbi, $GLOBALS['config']);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new ProcessesController(
            $response,
            new Template(),
            $this->data,
            $GLOBALS['dbi'],
            new Processes($GLOBALS['dbi']),
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller($this->createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.',
            $html,
        );
        // Test tab links
        $this->assertStringContainsString('<div class="tabLinks row">', $html);
        $this->assertStringContainsString('<a id="toggleRefresh" href="#">', $html);
        $this->assertStringContainsString('play', $html);
        $this->assertStringContainsString('Start auto refresh', $html);
        $this->assertStringContainsString('<select id="id_refreshRate"', $html);
        $this->assertStringContainsString('<option value="5" selected>', $html);
        $this->assertStringContainsString('5 seconds', $html);

        $this->assertStringContainsString(
            '<table id="tableprocesslist" class="table table-striped table-hover sortable w-auto">',
            $html,
        );
        $this->assertStringContainsString('<th>Processes</th>', $html);
        $this->assertStringContainsString('Show full queries', $html);
        $this->assertStringContainsString('index.php?route=/server/status/processes', $html);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['column_name', '', 'Database'],
            ['order_by_field', '', 'Db'],
            ['sort_order', '', 'ASC'],
        ]);
        $request->method('hasBodyParam')->willReturnMap([['full', true], ['showExecuting', false]]);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString('Truncate shown queries', $html);
        $this->assertStringContainsString('Database', $html);
        $this->assertStringContainsString('DESC', $html);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['column_name', '', 'Host'],
            ['order_by_field', '', 'Host'],
            ['sort_order', '', 'DESC'],
        ]);
        $request->method('hasBodyParam')->willReturnMap([['full', true], ['showExecuting', false]]);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString('Host', $html);
        $this->assertStringContainsString('ASC', $html);
    }
}
