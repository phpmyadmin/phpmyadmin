<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Util;

use function __;
use function array_sum;
use function htmlspecialchars;

/** @covers \PhpMyAdmin\Controllers\Server\Status\QueriesController */
class QueriesControllerTest extends AbstractTestCase
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
        $this->data->status['Uptime'] = 36000;
        $this->data->usedQueries = [
            'Com_change_db' => '15',
            'Com_select' => '12',
            'Com_set_option' => '54',
            'Com_show_databases' => '16',
            'Com_show_status' => '14',
            'Com_show_tables' => '13',
        ];
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new QueriesController($response, new Template(), $this->data, $GLOBALS['dbi']);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($this->createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $hourFactor = 3600 / $this->data->status['Uptime'];
        $usedQueries = $this->data->usedQueries;
        $totalQueries = array_sum($usedQueries);

        $questionsFromStart = __('Questions since startup:') . ' ' . Util::formatNumber($totalQueries, 0);

        $this->assertStringContainsString('<h3>', $html);
        $this->assertStringContainsString($questionsFromStart, $html);

        $this->assertStringContainsString('per hour:', $html);
        $this->assertStringContainsString(Util::formatNumber($totalQueries * $hourFactor, 0), $html);

        $valuePerMinute = Util::formatNumber($totalQueries * 60 / $this->data->status['Uptime'], 0);
        $this->assertStringContainsString('per minute:', $html);
        $this->assertStringContainsString(htmlspecialchars($valuePerMinute), $html);

        $this->assertStringContainsString('Statements', $html);

        $this->assertStringContainsString('Change Db', $html);
        $this->assertStringContainsString('54', $html);
        $this->assertStringContainsString('Select', $html);
        $this->assertStringContainsString('Set Option', $html);
        $this->assertStringContainsString('Show Databases', $html);
        $this->assertStringContainsString('Show Status', $html);
        $this->assertStringContainsString('Show Tables', $html);

        $this->assertStringContainsString('<canvas id="query-statistics-chart" data-chart-data="', $html);
    }
}
