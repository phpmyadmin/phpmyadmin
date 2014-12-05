<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_status.lib.php
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
require_once 'libraries/server_status.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';

/**
 * class PMA_ServerStatus_Test
 *
 * this class is for testing server_status.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerStatus_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = false;
        $GLOBALS['replication_types'] = array();

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
            "Com_execute_sql" => 2,
            "Com_stmt_execute" => 2,
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
            ),
            array(
                "SELECT concat('Com_', variable_name), variable_value "
                    . "FROM data_dictionary.GLOBAL_STATEMENTS",
                0,
                1,
                null,
                0,
                $server_status
            ),
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $this->ServerStatusData = new PMA_ServerStatusData();
    }

    /**
     * Test for PMA_getHtmlForServerStatus
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForServerStatus()
    {
        //parameters
        $upTime = "10h";
        $bytes_received = 100;
        $bytes_sent = 200;
        $max_used_conn = 500;
        $aborted_conn = 200;
        $conn = 1000;
        $this->ServerStatusData->status['Uptime'] = $upTime;
        $this->ServerStatusData->status['Bytes_received'] = $bytes_received;
        $this->ServerStatusData->status['Bytes_sent'] = $bytes_sent;
        $this->ServerStatusData->status['Max_used_connections'] = $max_used_conn;
        $this->ServerStatusData->status['Aborted_connects'] = $aborted_conn;
        $this->ServerStatusData->status['Connections'] = $conn;

        //Call the test function
        $html = PMA_getHtmlForServerStatus($this->ServerStatusData);

        //validate 1: PMA_getHtmlForServerStateGeneralInfo
        //traffic: $bytes_received + $bytes_sent
        $traffic = $bytes_received + $bytes_sent;
        $traffic_html = 'Network traffic since startup: ' . $traffic . ' B';
        $this->assertContains(
            $traffic_html,
            $html
        );
        //updatetime
        $upTime_html = 'This MySQL server has been running for '
            . '0 days, 0 hours, 0 minutes and 10h seconds';
        $this->assertContains(
            $upTime_html,
            $html
        );
        //master state
        $master_html = 'This MySQL server works as <b>master</b>';
        $this->assertContains(
            $master_html,
            $html
        );

        //validate 2: PMA_getHtmlForServerStateTraffic
        $traffic_html = '<table id="serverstatustraffic" class="data noclick">';
        $this->assertContains(
            $traffic_html,
            $html
        );
        //traffic hint
        $traffic_html = 'On a busy server, the byte counters may overrun';
        $this->assertContains(
            $traffic_html,
            $html
        );
        //$bytes_received
        $this->assertContains(
            '<td class="value">' . $bytes_received . ' B',
            $html
        );
        //$bytes_sent
        $this->assertContains(
            '<td class="value">' . $bytes_sent . ' B',
            $html
        );

        //validate 3: PMA_getHtmlForServerStateConnections
        $this->assertContains(
            '<th colspan="2">Connections</th>',
            $html
        );
        $this->assertContains(
            '<th>&oslash; per hour</th>',
            $html
        );
        $this->assertContains(
            '<table id="serverstatusconnections" class="data noclick">',
            $html
        );
        $this->assertContains(
            '<th class="name">max. concurrent connections</th>',
            $html
        );
        //Max_used_connections
        $this->assertContains(
            '<td class="value">' . $max_used_conn,
            $html
        );
        $this->assertContains(
            '<th class="name">Failed attempts</th>',
            $html
        );
        //Aborted_connects
        $this->assertContains(
            '<td class="value">' . $aborted_conn,
            $html
        );
        $this->assertContains(
            '<th class="name">Aborted</th>',
            $html
        );

        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = true;
        $this->ServerStatusData->status['Connections'] = 0;
        $html = PMA_getHtmlForServerStatus($this->ServerStatusData);

        $this->assertContains(
            'This MySQL server works as <b>master</b> and <b>slave</b>',
            $html
        );

        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = true;
        $html = PMA_getHtmlForServerStatus($this->ServerStatusData);

        $this->assertContains(
            'This MySQL server works as <b>slave</b>',
            $html
        );
    }
}
?>
