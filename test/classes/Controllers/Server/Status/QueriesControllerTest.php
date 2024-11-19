<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Util;

use function __;
use function array_sum;
use function htmlspecialchars;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\QueriesController
 */
class QueriesControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->data = new Data();
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
        global $dbi;

        $response = new ResponseRenderer();

        $controller = new QueriesController($response, new Template(), $this->data, $dbi);

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $hourFactor = 3600 / $this->data->status['Uptime'];
        $usedQueries = $this->data->usedQueries;
        $totalQueries = array_sum($usedQueries);

        $questionsFromStart = __('Questions since startup:')
            . '    ' . Util::formatNumber($totalQueries, 0);

        self::assertStringContainsString('<h3 id="serverstatusqueries">', $html);
        self::assertStringContainsString($questionsFromStart, $html);

        self::assertStringContainsString(__('per hour:'), $html);
        self::assertStringContainsString(Util::formatNumber($totalQueries * $hourFactor, 0), $html);

        $valuePerMinute = Util::formatNumber($totalQueries * 60 / $this->data->status['Uptime'], 0);
        self::assertStringContainsString(__('per minute:'), $html);
        self::assertStringContainsString(htmlspecialchars($valuePerMinute), $html);

        self::assertStringContainsString(__('Statements'), $html);

        self::assertStringContainsString(htmlspecialchars('change db'), $html);
        self::assertStringContainsString('54', $html);
        self::assertStringContainsString(htmlspecialchars('select'), $html);
        self::assertStringContainsString(htmlspecialchars('set option'), $html);
        self::assertStringContainsString(htmlspecialchars('show databases'), $html);
        self::assertStringContainsString(htmlspecialchars('show status'), $html);
        self::assertStringContainsString(htmlspecialchars('show tables'), $html);

        self::assertStringContainsString(
            '<div id="serverstatusquerieschart" class="w-100 col-12 col-md-6" data-chart="',
            $html
        );
    }
}
