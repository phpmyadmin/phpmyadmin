<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds QueriesControllerTest
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * Class QueriesControllerTest
 * @package PhpMyAdmin\Tests\Controllers\Server\Status
 */
class QueriesControllerTest extends TestCase
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = true;
        $GLOBALS['replication_types'] = [];

        $serverStatus = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '0',
            'Com_delete_multi' => '0',
            'Com_create_function' => '0',
            'Com_empty_query' => '0',
        ];

        $serverVariables = [
            'auto_increment_increment' => '1',
            'auto_increment_offset' => '1',
            'automatic_sp_privileges' => 'ON',
            'back_log' => '50',
            'big_tables' => 'OFF',
        ];

        $fetchResult = [
            [
                'SHOW GLOBAL STATUS',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverStatus,
            ],
            [
                'SHOW GLOBAL VARIABLES',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverVariables,
            ],
            [
                "SELECT concat('Com_', variable_name), variable_value "
                . "FROM data_dictionary.GLOBAL_STATEMENTS",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverStatus,
            ],
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $this->data = new Data();
        $this->data->status['Uptime'] = 36000;
        $this->data->used_queries = [
            'Com_change_db' => '15',
            'Com_select' => '12',
            'Com_set_option' => '54',
            'Com_show_databases' => '16',
            'Com_show_status' => '14',
            'Com_show_tables' => '13',
        ];
    }

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $controller = new QueriesController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template(),
            $this->data
        );

        $html = $controller->index();

        $hourFactor = 3600 / $this->data->status['Uptime'];
        $usedQueries = $this->data->used_queries;
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
            '<div id="serverstatusquerieschart" class="width100" data-chart="',
            $html
        );
    }
}
