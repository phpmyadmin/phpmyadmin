<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_ServerStatusData class
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
require_once 'libraries/database_interface.inc.php';

/**
 * Test for PMA_ServerStatusData class
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerStatusData_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['cfg']['Server']['host'] = "::1";
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = true;
        $GLOBALS['replication_types'] = array();

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
            "Com_empty_query" => 3,
            "Key_blocks_used" => 2,
            "Key_writes" => true,
            "Key_reads" => true,
            "Key_write_requests" => 5,
            "Key_read_requests" => 1,
            "Threads_created" => true,
            "Connections" => 2,
        );

        $server_variables= array(
            "auto_increment_increment" => "1",
            "auto_increment_offset" => "1",
            "automatic_sp_privileges" => "ON",
            "back_log" => "50",
            "big_tables" => "OFF",
            "key_buffer_size" => 10,
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

        $this->object = new PMA_ServerStatusData();
    }

    /**
     * tests getMenuHtml()
     *
     * @return void
     */
    function testGetMenuHtml()
    {
        $html = $this->object->getMenuHtml();

        $this->assertContains('Server', $html);
        $this->assertContains('server_status.php', $html);

        $this->assertContains('Processes', $html);
        $this->assertContains('server_status_processes.php', $html);

        $this->assertContains('Query statistics', $html);
        $this->assertContains('server_status_queries.php', $html);

        $this->assertContains('All status variables', $html);
        $this->assertContains('server_status_variables.php', $html);

        $this->assertContains('Monitor', $html);
        $this->assertContains('server_status_monitor.php', $html);

        $this->assertContains('Advisor', $html);
        $this->assertContains('server_status_advisor.php', $html);
    }
}
