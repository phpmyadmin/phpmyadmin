<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Status
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Status;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Theme;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\Server\StatusTest class
 *
 * this class is for testing PhpMyAdmin\Server\Status methods
 *
 * @package PhpMyAdmin-test
 */
class StatusTest extends TestCase
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
    public function setUp()
    {
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = false;
        $GLOBALS['replication_types'] = array();

        $GLOBALS['table'] = "table";

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
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
                DatabaseInterface::CONNECT_USER,
                0,
                $server_status
            ),
            array(
                "SHOW GLOBAL VARIABLES",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_variables
            ),
            array(
                "SELECT concat('Com_', variable_name), variable_value "
                    . "FROM data_dictionary.GLOBAL_STATEMENTS",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
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

        $this->serverStatusData = new Data();
    }

    /**
     * Test for Status::getHtml
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
        $this->serverStatusData->status['Uptime'] = 36000;
        $this->serverStatusData->status['Bytes_received'] = $bytes_received;
        $this->serverStatusData->status['Bytes_sent'] = $bytes_sent;
        $this->serverStatusData->status['Max_used_connections'] = $max_used_conn;
        $this->serverStatusData->status['Aborted_connects'] = $aborted_conn;
        $this->serverStatusData->status['Connections'] = $conn;

        //Call the test function
        $html = Status::getHtml($this->serverStatusData);

        //validate 1: Status::getHtmlForServerStateGeneralInfo
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

        //validate 2: Status::getHtmlForServerStateTraffic
        $traffic_html = '<table id="serverstatustraffic" class="width100 data noclick">';
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

        //validate 3: Status::getHtmlForServerStateConnections
        $this->assertContains(
            '<th>Connections</th>',
            $html
        );
        $this->assertContains(
            '<th>&oslash; per hour</th>',
            $html
        );
        $this->assertContains(
            '<table id="serverstatusconnections" class="width100 data noclick">',
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
        $this->serverStatusData->status['Connections'] = 0;
        $html = Status::getHtml($this->serverStatusData);

        $this->assertContains(
            'This MySQL server works as <b>master</b> and <b>slave</b>',
            $html
        );

        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = true;
        $html = Status::getHtml($this->serverStatusData);

        $this->assertContains(
            'This MySQL server works as <b>slave</b>',
            $html
        );
    }
}
