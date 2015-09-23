<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_status_queries.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status_queries.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * class PMA_ServerStatusVariables_Test
 *
 * this class is for testing server_status_queries.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerStatusQueries_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public $ServerStatusData;

    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PMA_ServerStatusData constructs
        $server_status = array(
            "Aborted_clients" => "0",
            "Aborted_connects" => "0",
            "Com_delete_multi" => "0",
            "Com_create_function" => "0",
            "Com_empty_query" => "0",
        );

        $server_variables= array(
            "auto_increment_increment" => "1",
            "auto_increment_offset" => "1",
            "automatic_sp_privileges" => "ON",
            "back_log" => "50",
            "big_tables" => "OFF",
        );

        $fetchResult = array(
            array(
                "SHOW GLOBAL STATUS",
                0,
                1,
                null,
                0,
                $server_status
            ),
            array(
                "SHOW GLOBAL VARIABLES",
                0,
                1,
                null,
                0,
                $server_variables
            )
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
        $this->ServerStatusData = new PMA_ServerStatusData();
        $upTime = "10h";
        $this->ServerStatusData->status['Uptime'] = $upTime;
        $this->ServerStatusData->used_queries = array(
            "Com_change_db" => "15",
            "Com_select" => "12",
            "Com_set_option" => "54",
            "Com_show_databases" => "16",
            "Com_show_status" => "14",
            "Com_show_tables" => "13",
        );
    }

    /**
     * Test for PMA_getHtmlForQueryStatistics
     *
     * @return void
     */
    public function testPMAGetHtmlForQueryStatistics()
    {
        //Call the test function
        $html = PMA_getHtmlForQueryStatistics($this->ServerStatusData);

        $hour_factor   = 3600 / $this->ServerStatusData->status['Uptime'];
        $used_queries = $this->ServerStatusData->used_queries;
        $total_queries = array_sum($used_queries);

        $questions_from_start = sprintf(
            __('Questions since startup: %s'),
            PMA_Util::formatNumber($total_queries, 0)
        );

        //validate 1: PMA_getHtmlForQueryStatistics
        $this->assertContains(
            '<h3 id="serverstatusqueries">',
            $html
        );
        $this->assertContains(
            $questions_from_start,
            $html
        );

        //validate 2: per hour
        $this->assertContains(
            __('per hour:'),
            $html
        );
        $this->assertContains(
            PMA_Util::formatNumber($total_queries * $hour_factor, 0),
            $html
        );

        //validate 3:per minute
        $value_per_minute = PMA_Util::formatNumber(
            $total_queries * 60 / $this->ServerStatusData->status['Uptime'],
            0
        );
        $this->assertContains(
            __('per minute:'),
            $html
        );
        $this->assertContains(
            $value_per_minute,
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerStatusQueriesDetails
     *
     * @return void
     */
    public function testPMAGetHtmlForServerStatusQueriesDetails()
    {
        //Call the test function
        $html = PMA_getHtmlForServerStatusQueriesDetails($this->ServerStatusData);

        //validate 1: PMA_getHtmlForServerStatusQueriesDetails
        $this->assertContains(
            __('Statements'),
            $html
        );

        //validate 2: used queries
        $this->assertContains(
            htmlspecialchars("change db"),
            $html
        );
        $this->assertContains(
            '54',
            $html
        );
        $this->assertContains(
            htmlspecialchars("select"),
            $html
        );
        $this->assertContains(
            htmlspecialchars("set option"),
            $html
        );
        $this->assertContains(
            htmlspecialchars("show databases"),
            $html
        );
        $this->assertContains(
            htmlspecialchars("show status"),
            $html
        );
        $this->assertContains(
            htmlspecialchars("show tables"),
            $html
        );

        //validate 3:serverstatusquerieschart
        $this->assertContains(
            '<div id="serverstatusquerieschart"></div>',
            $html
        );
        $this->assertContains(
            '<div id="serverstatusquerieschart_data"',
            $html
        );
    }
}
