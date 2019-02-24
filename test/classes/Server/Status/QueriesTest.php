<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Status\Queries
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\Status;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Queries;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\Server\Status\QueriesTest class
 *
 * this class is for testing PhpMyAdmin\Server\Status\Queries methods
 *
 * @package PhpMyAdmin-test
 */
class QueriesTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public $serverStatusData;

    /**
     * Test for setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = false;

        $GLOBALS['table'] = "table";

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
        $server_status = [
            "Aborted_clients" => "0",
            "Aborted_connects" => "0",
            "Com_delete_multi" => "0",
            "Com_create_function" => "0",
            "Com_empty_query" => "0",
        ];

        $server_variables = [
            "auto_increment_increment" => "1",
            "auto_increment_offset" => "1",
            "automatic_sp_privileges" => "ON",
            "back_log" => "50",
            "big_tables" => "OFF",
        ];

        $fetchResult = [
            [
                "SHOW GLOBAL STATUS",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_status,
            ],
            [
                "SHOW GLOBAL VARIABLES",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_variables,
            ],
        ];

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
        $this->serverStatusData = new Data();
        $this->serverStatusData->status['Uptime'] = 36000;
        $this->serverStatusData->used_queries = [
            "Com_change_db" => "15",
            "Com_select" => "12",
            "Com_set_option" => "54",
            "Com_show_databases" => "16",
            "Com_show_status" => "14",
            "Com_show_tables" => "13",
        ];
    }

    /**
     * Test for Queries::getHtmlForQueryStatistics
     *
     * @return void
     */
    public function testPMAGetHtmlForQueryStatistics()
    {
        //Call the test function
        $html = Queries::getHtmlForQueryStatistics($this->serverStatusData);

        $hour_factor   = 3600 / $this->serverStatusData->status['Uptime'];
        $used_queries = $this->serverStatusData->used_queries;
        $total_queries = array_sum($used_queries);

        $questions_from_start = sprintf(
            __('Questions since startup: %s'),
            Util::formatNumber($total_queries, 0)
        );

        //validate 1: Queries::getHtmlForQueryStatistics
        $this->assertStringContainsString(
            '<h3 id="serverstatusqueries">',
            $html
        );
        $this->assertStringContainsString(
            $questions_from_start,
            $html
        );

        //validate 2: per hour
        $this->assertStringContainsString(
            __('per hour:'),
            $html
        );
        $this->assertStringContainsString(
            Util::formatNumber($total_queries * $hour_factor, 0),
            $html
        );

        //validate 3:per minute
        $value_per_minute = Util::formatNumber(
            $total_queries * 60 / $this->serverStatusData->status['Uptime'],
            0
        );
        $this->assertStringContainsString(
            __('per minute:'),
            $html
        );
        $this->assertStringContainsString(
            $value_per_minute,
            $html
        );
    }

    /**
     * Test for Queries::getHtmlForDetails
     *
     * @return void
     */
    public function testPMAGetHtmlForServerStatusQueriesDetails()
    {
        //Call the test function
        $html = Queries::getHtmlForDetails($this->serverStatusData);

        //validate 1: Queries::getHtmlForDetails
        $this->assertStringContainsString(
            __('Statements'),
            $html
        );

        //validate 2: used queries
        $this->assertStringContainsString(
            htmlspecialchars("change db"),
            $html
        );
        $this->assertStringContainsString(
            '54',
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars("select"),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars("set option"),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars("show databases"),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars("show status"),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars("show tables"),
            $html
        );

        //validate 3:serverstatusquerieschart
        $this->assertStringContainsString(
            '<div id="serverstatusquerieschart" class="width100" data-chart="',
            $html
        );
    }
}
