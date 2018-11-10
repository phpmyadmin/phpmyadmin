<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\ReplicationGui
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Theme;
use PHPUnit\Framework\TestCase;

/*
* Include to test.
*/
require_once 'libraries/replication.inc.php';

/**
 * PhpMyAdmin\Tests\ReplicationGuiTest class
 *
 * this class is for testing PhpMyAdmin\ReplicationGui methods
 *
 * @package PhpMyAdmin-test
 */
class ReplicationGuiTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_POST
        $_POST['mr_adduser'] = "mr_adduser";

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
        $GLOBALS['cfg']['ShowHint'] = true;

        $GLOBALS['table'] = "table";
        $GLOBALS['url_params'] = array();

        //$_SESSION

        //Mock DBI

        $slave_host = array(
            array('Server_id'=>'Server_id1', 'Host'=>'Host1'),
            array('Server_id'=>'Server_id2', 'Host'=>'Host2'),
        );

        $fetchResult = array(
            array(
                "SHOW SLAVE HOSTS",
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                $slave_host
            ),
        );

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for ReplicationGui::getHtmlForMasterReplication
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForMasterReplication()
    {
        global $master_variables_alerts;
        global $master_variables_oks;
        global $strReplicationStatus_master;

        $master_variables_alerts = null;
        $master_variables_oks = null;
        $strReplicationStatus_master = null;

        //Call the test function
        $html = ReplicationGui::getHtmlForMasterReplication();

        //validate 1: Master replication
        $this->assertContains(
            '<legend>Master replication</legend>',
            $html
        );
        $this->assertContains(
            'This server is configured as master in a replication process.',
            $html
        );

        //validate 2: ReplicationGui::getHtmlForReplicationStatusTable
        $this->assertContains(
            '<div id="replication_master_section"',
            $html
        );
        //$master_variables
        $this->assertContains(
            "Binlog_Do_DB",
            $html
        );
        $this->assertContains(
            "Binlog_Ignore_DB",
            $html
        );
        //$server_master_replication
        $this->assertContains(
            "master-bin.000030",
            $html
        );

        //validate 3: ReplicationGui::getHtmlForReplicationSlavesTable
        $this->assertContains(
            'replication_slaves_section',
            $html
        );
        $this->assertContains(
            '<th>Server ID</th>',
            $html
        );
        $this->assertContains(
            '<th>Host</th>',
            $html
        );
        //slave host
        $this->assertContains(
            '<td class="value">Server_id1</td>',
            $html
        );
        $this->assertContains(
            '<td class="value">Server_id2</td>',
            $html
        );
        $this->assertContains(
            '<td class="value">Host1</td>',
            $html
        );
        $this->assertContains(
            '<td class="value">Host2</td>',
            $html
        );
        //Notice
        $this->assertContains(
            'Only slaves started with the',
            $html
        );

        //validate 4: navigation URL
        $this->assertContains(
            '<a href="server_replication.php',
            $html
        );
        $this->assertContains(
            'Add slave replication user',
            $html
        );

        //validate 5: 'Add replication slave user' form
        $this->assertContains(
            '<div id="master_addslaveuser_gui">',
            $html
        );
    }

    /**
     * Test for ReplicationGui::getHtmlForNotServerReplication
     *
     * @return void
     */
    public function testPMAGetHtmlForNotServerReplication()
    {
        //Call the test function
        $html = ReplicationGui::getHtmlForNotServerReplication();

        $this->assertContains(
            '<legend>Master replication</legend>',
            $html
        );
        $this->assertContains(
            'This server is not configured as master in a replication process.',
            $html
        );
    }

    /**
     * Test for ReplicationGui::getHtmlForSlaveConfiguration
     *
     * @return void
     */
    public function testPMAGetHtmlForSlaveConfiguration()
    {
        global $server_slave_replication;

        //Call the test function
        $html = ReplicationGui::getHtmlForSlaveConfiguration(
            true,
            $server_slave_replication
        );

        //legend
        $this->assertContains(
            '<legend>Slave replication</legend>',
            $html
        );
        $this->assertContains(
            '<div id="slave_configuration_gui">',
            $html
        );
        //notice
        $this->assertContains(
            'Server is configured as slave in a replication process.',
            $html
        );
        //slave session
        $this->assertContains(
            '<div id="replication_slave_section"',
            $html
        );
        //variable
        $this->assertContains(
            'Master_SSL_CA_Path',
            $html
        );
        $this->assertContains(
            'Master_SSL_Cert',
            $html
        );
        $this->assertContains(
            'Master_SSL_Cipher',
            $html
        );
        $this->assertContains(
            'Seconds_Behind_Master',
            $html
        );
    }

    /**
     * Test for ReplicationGui::getHtmlForReplicationChangeMaster
     *
     * @return void
     */
    public function testPMAGetHtmlForReplicationChangeMaster()
    {
        //Call the test function
        $html = ReplicationGui::getHtmlForReplicationChangeMaster("slave_changemaster");

        $this->assertContains(
            '<form method="post" action="server_replication.php">',
            $html
        );
        $this->assertContains(
            'Slave configuration',
            $html
        );
        $this->assertContains(
            'Change or reconfigure master server',
            $html
        );
        $notice = 'Make sure you have a unique server-id '
            . 'in your configuration file (my.cnf)';
        $this->assertContains(
            $notice,
            $html
        );
    }
}
