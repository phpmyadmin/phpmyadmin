<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;
use function array_sum;
use function htmlspecialchars;

#[CoversClass(QueriesController::class)]
class QueriesControllerTest extends AbstractTestCase
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

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $dummyDbi->addResult('SHOW GLOBAL STATUS', [
            ['Uptime' , '36000'],
            ['Aborted_connects' , '0'],
            ['Aborted_clients', '0'],
            ['Com_delete_multi', '0'],
            ['Com_create_function', '0'],
            ['Com_empty_query', '0'],
            ['Com_change_db' , '15'],
            ['Com_select' , '12'],
            ['Com_set_option' , '54'],
            ['Com_show_databases' , '16'],
            ['Com_show_status' , '14'],
            ['Com_show_tables' , '13'],
        ], ['Variable_name', 'Value']);

        $this->data = new Data($dbi, $config);
    }

    public function testUsedQueries(): void
    {
        self::assertSame([
            'Com_change_db' => '15',
            'Com_select' => '12',
            'Com_set_option' => '54',
            'Com_show_databases' => '16',
            'Com_show_status' => '14',
            'Com_show_tables' => '13',
        ], $this->data->usedQueries);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new QueriesController($response, new Template(), $this->data, DatabaseInterface::getInstance());

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $hourFactor = 3600 / $this->data->status['Uptime'];
        $usedQueries = $this->data->usedQueries;
        $totalQueries = array_sum($usedQueries);

        $questionsFromStart = __('Questions since startup:') . ' ' . Util::formatNumber($totalQueries, 0);

        self::assertStringContainsString('<h3>', $html);
        self::assertStringContainsString($questionsFromStart, $html);

        self::assertStringContainsString('per hour:', $html);
        self::assertStringContainsString(Util::formatNumber($totalQueries * $hourFactor, 0), $html);

        $valuePerMinute = Util::formatNumber($totalQueries * 60 / $this->data->status['Uptime'], 0);
        self::assertStringContainsString('per minute:', $html);
        self::assertStringContainsString(htmlspecialchars($valuePerMinute), $html);

        self::assertStringContainsString('Statements', $html);

        self::assertStringContainsString('Change Db', $html);
        self::assertStringContainsString('54', $html);
        self::assertStringContainsString('Select', $html);
        self::assertStringContainsString('Set Option', $html);
        self::assertStringContainsString('Show Databases', $html);
        self::assertStringContainsString('Show Status', $html);
        self::assertStringContainsString('Show Tables', $html);

        self::assertStringContainsString('<canvas id="query-statistics-chart" data-chart-data="', $html);
    }
}
