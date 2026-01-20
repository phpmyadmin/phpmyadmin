<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProcessesController::class)]
class ProcessesControllerTest extends AbstractTestCase
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

        $dbi = DatabaseInterface::getInstance();
        $controller = new ProcessesController(
            $response,
            new Template(),
            $this->data,
            $dbi,
            new Processes($dbi),
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.',
            $html,
        );
        // Test tab links
        self::assertStringContainsString('<div class="tabLinks row">', $html);
        self::assertStringContainsString('<a id="toggleRefresh" href="#">', $html);
        self::assertStringContainsString('play', $html);
        self::assertStringContainsString('Start auto refresh', $html);
        self::assertStringContainsString('<select id="id_refreshRate"', $html);
        self::assertStringContainsString('<option value="5" selected>', $html);
        self::assertStringContainsString('5 seconds', $html);

        self::assertStringContainsString(
            '<table id="tableprocesslist" class="table table-striped table-hover sortable w-auto">',
            $html,
        );
        self::assertStringContainsString('<th>Processes</th>', $html);
        self::assertStringContainsString('Show full queries', $html);
        self::assertStringContainsString('index.php?route=/server/status/processes', $html);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'column_name' => 'Database',
                'full' => '1',
                'order_by_field' => 'Db',
                'sort_order' => 'ASC',
            ]);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('Truncate shown queries', $html);
        self::assertStringContainsString('Database', $html);
        self::assertStringContainsString('DESC', $html);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'column_name' => 'Host',
                'full' => '1',
                'order_by_field' => 'Host',
                'sort_order' => 'DESC',
            ]);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('Host', $html);
        self::assertStringContainsString('ASC', $html);
    }
}
