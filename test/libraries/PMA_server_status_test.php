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
use PMA\libraries\ServerStatusData;
use PMA\libraries\Theme;


require_once 'libraries/url_generating.lib.php';

require_once 'libraries/server_status.lib.php';

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
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when ServerStatusData constructs
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

        $dbi->expects($this->at(0))
            ->method('tryQuery')
            ->with('SHOW GLOBAL STATUS')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->will($this->returnValue(array("Aborted_clients", "0")));
        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->will($this->returnValue(array("Aborted_connects", "0")));
        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->will($this->returnValue(array("Com_delete_multi", "0")));
        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->will($this->returnValue(array("Com_create_function", "0")));
        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->will($this->returnValue(array("Com_empty_query", "0")));
        $dbi->expects($this->at(6))
            ->method('fetchRow')
            ->will($this->returnValue(false));

        $dbi->expects($this->at(7))->method('freeResult');

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $this->ServerStatusData = new ServerStatusData();
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
        $bytes_received = 100;
        $bytes_sent = 200;
        $max_used_conn = 500;
        $aborted_conn = 200;
        $conn = 1000;
        $this->ServerStatusData->status['Uptime'] = 36000;
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
            . '0 days, 10 hours, 0 minutes and 0 seconds';
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
            '<th>Connections</th>',
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
            '<th class="name">Max. concurrent connections</th>',
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
