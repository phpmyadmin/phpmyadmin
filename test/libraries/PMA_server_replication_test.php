<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for replication_gui.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/replication.inc.php';
require_once 'libraries/replication_gui.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * PMA_Serverreplication_Test class
 *
 * this class is for testing replication_gui.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerReplication_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */ 
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;
        $_REQUEST['mr_adduser'] = "mr_adduser";
        
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['SQP']['fmtType'] = 'none';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['ShowHint'] = true;
        
        $GLOBALS['table'] = "table";
        $GLOBALS['url_params'] = array();
        $GLOBALS['pmaThemeImage'] = 'image';
        
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        
        //Mock DBI
        $server_master_replication = array(
            array(
                "File" => "master-bin.000030",
                "Position" => "107",
                "Binlog_Do_DB" => "Binlog_Do_DB",
                "Binlog_Ignore_DB" => "Binlog_Ignore_DB",
            )
        );
        $server_slave_replication = array(
            array(
                "File" => "slave-bin.000030",
                "Position" => "slave_Position",
                "Binlog_Do_DB" => "slave__Do_DB",
                "Binlog_Ignore_DB" => "slave_Binlog_Ignore_DB",
            )
        );

        $slave_host = array(
            array('Server_id'=>'Server_id1', 'Host'=>'Host1'),
            array('Server_id'=>'Server_id2', 'Host'=>'Host2'),
        );
        
        $fetchResult = array(
            array(
                "SHOW MASTER STATUS",
                null,
                null,
                null,
                0,
                $server_master_replication
            ),
            array(
                "SHOW SLAVE STATUS",
                null,
                null,
                null,
                0,
                $server_slave_replication
            ),
            array(
                "SHOW SLAVE HOSTS",
                null,
                null,
                null,
                0,
                $slave_host
            ),
        );
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
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
     * Test for PMA_getHtmlForMasterReplication
     *
     * @return void
     */
    public function testPMAGetHtmlForMasterReplication()
    {      
        global $master_variables;
        global $master_variables_alerts;
        global $master_variables_oks;
        global $server_master_replication;
        global $strReplicationStatus_master;

        $server_master_replication = array(
            array(
                "File" => "master-bin.000030",
                "Position" => "107",
                "Binlog_Do_DB" => "Binlog_Do_DB",
                "Binlog_Ignore_DB" => "Binlog_Ignore_DB",
            )
        );
        $master_variables_alerts = null;
        $master_variables_oks = null;
        $strReplicationStatus_master = null;

        //Call the test function
        $html = PMA_getHtmlForMasterReplication();
        
        //validate 1: Master replication
        $this->assertContains(
            '<legend>Master replication</legend>',
            $html
        );
        $this->assertContains(
            'This server is configured as master in a replication process.',
            $html
        );
        
        //validate 2: PMA_getHtmlForReplicationStatusTable
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
        
        //validate 3: PMA_getHtmlForReplicationSlavesTable
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
     * Test for PMA_getHtmlForNotServerReplication
     *
     * @return void
     */
    public function testPMAGetHtmlForNotServerReplication()
    {
        //Call the test function             
        $html = PMA_getHtmlForNotServerReplication();
        
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
     * Test for PMA_getHtmlForSlaveConfiguration
     *
     * @return void
     */
    public function testPMAGetHtmlForSlaveConfiguration()
    {
        global $server_slave_replication;
        $server_slave_replication = array(
            array(
                'Slave_IO_State' => 'running',
                'Master_Host' => 'locahost',
                'Master_User' => 'Master_User',
                'Master_Port' => '1002',
                'Connect_Retry' => 'Connect_Retry',
                'Master_Log_File' => 'Master_Log_File',
                'Read_Master_Log_Pos' => 'Read_Master_Log_Pos',
                'Relay_Log_File' => 'Relay_Log_File',
                'Relay_Log_Pos' => 'Relay_Log_Pos',
                'Relay_Master_Log_File' =>  'Relay_Master_Log_File',
                'Slave_IO_Running' => 'NO',
                'Slave_SQL_Running' => 'NO',
                'Replicate_Do_DB' => 'Replicate_Do_DB',
                'Replicate_Ignore_DB' => 'Replicate_Ignore_DB',
                'Replicate_Do_Table' => 'Replicate_Do_Table',
                'Replicate_Ignore_Table' => 'Replicate_Ignore_Table',
                'Replicate_Wild_Do_Table' => 'Replicate_Wild_Do_Table',
                'Replicate_Wild_Ignore_Table' => 'Replicate_Wild_Ignore_Table',
                'Last_Errno' => 'Last_Errno',
                'Last_Error' => 'Last_Error',
                'Skip_Counter' =>  'Skip_Counter',
                'Exec_Master_Log_Pos' => 'Exec_Master_Log_Pos',
                'Relay_Log_Space' => 'Relay_Log_Space',
                'Until_Condition' => 'Until_Condition',
                'Until_Log_File' => 'Until_Log_File',
                'Until_Log_Pos' => 'Until_Log_Pos',
                'Master_SSL_Allowed' => 'Master_SSL_Allowed',
                'Master_SSL_CA_File' => 'Master_SSL_CA_File',
                'Master_SSL_CA_Path' => 'Master_SSL_CA_Path',
                'Master_SSL_Cert' => 'Master_SSL_Cert',
                'Master_SSL_Cipher' => 'Master_SSL_Cipher',
                'Master_SSL_Key' => 'Master_SSL_Key',
                'Seconds_Behind_Master' => 'Seconds_Behind_Master',
            )
        );
        
        //Call the test function
        $html = PMA_getHtmlForSlaveConfiguration(
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
     * Test for PMA_getHtmlForReplicationChangeMaster
     *
     * @return void
     */
    public function testPMAGetHtmlForReplicationChangeMaster()
    {
        //Call the test function             
        $html = PMA_getHtmlForReplicationChangeMaster("slave_changemaster");

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
        $notice = 'Make sure, you have unique server-id ' 
            . 'in your configuration file (my.cnf)';
        $this->assertContains(
            $notice,
            $html
        );
    }
}
