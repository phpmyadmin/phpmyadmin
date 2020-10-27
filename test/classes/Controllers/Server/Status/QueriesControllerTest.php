<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Util;
use function array_sum;
use function htmlspecialchars;

class QueriesControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
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

        $response = new Response();

        $controller = new QueriesController($response, new Template(), $this->data, $dbi);

        $controller->index();
        $html = $response->getHTMLResult();

        $hourFactor = 3600 / $this->data->status['Uptime'];
        $usedQueries = $this->data->usedQueries;
        $totalQueries = array_sum($usedQueries);

        $questionsFromStart = __('Questions since startup:')
            . '    ' . Util::formatNumber($totalQueries, 0);

        $this->assertStringContainsString(
            '<h3 id="serverstatusqueries">',
            $html
        );
        $this->assertStringContainsString(
            $questionsFromStart,
            $html
        );

        $this->assertStringContainsString(
            __('per hour:'),
            $html
        );
        $this->assertStringContainsString(
            Util::formatNumber($totalQueries * $hourFactor, 0),
            $html
        );

        $valuePerMinute = Util::formatNumber(
            $totalQueries * 60 / $this->data->status['Uptime'],
            0
        );
        $this->assertStringContainsString(
            __('per minute:'),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($valuePerMinute),
            $html
        );

        $this->assertStringContainsString(
            __('Statements'),
            $html
        );

        $this->assertStringContainsString(
            htmlspecialchars('change db'),
            $html
        );
        $this->assertStringContainsString(
            '54',
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars('select'),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars('set option'),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars('show databases'),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars('show status'),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars('show tables'),
            $html
        );

        $this->assertStringContainsString(
            '<div id="serverstatusquerieschart" class="w-100 col-12 col-md-6" data-chart="',
            $html
        );
    }
}
