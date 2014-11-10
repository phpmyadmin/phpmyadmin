<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_status_processes.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_status_processes.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * class PMA_ServerStatusProcesses_Test
 *
 * this class is for testing server_status_processes.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerStatusProcesses_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = false;
        $GLOBALS['replication_types'] = array();

        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getHtmlForServerProcesses
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForServerProcesses()
    {
        $html = PMA_getHtmlForServerProcesses();

        // Test Notice
        $this->assertContains(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.',
            $html
        );
        // Test tab links
        $this->assertContains(
            '<div class="tabLinks">',
            $html
        );
        $this->assertContains(
            '<a id="toggleRefresh" href="#">',
            $html
        );
        $this->assertContains(
            'play',
            $html
        );
        $this->assertContains(
            'Start auto refresh</a>',
            $html
        );
        $this->assertContains(
            '<label>Refresh rate: <select',
            $html
        );
        $this->assertContains(
            '<option value="5" selected="selected">5 seconds</option>',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerProcesslist
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForServerProcessList()
    {
        $process = array(
            "User" => "User1",
            "Host" => "Host1",
            "Id" => "Id1",
            "db" => "db1",
            "Command" => "Command1",
            "State" => "State1",
            "Info" => "Info1",
            "State" => "State1",
            "Time" => "Time1"
        );
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;
        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will($this->onConsecutiveCalls($process));

        $html = PMA_getHtmlForServerProcesslist();

        // Test process table
        $this->assertContains(
            '<table id="tableprocesslist" '
            . 'class="data clearfloat noclick sortable">',
            $html
        );
        $this->assertContains(
            '<th>Processes</th>',
            $html
        );
        $this->assertContains(
            'Show Full Queries',
            $html
        );
        $this->assertContains(
            'server_status_processes.php',
            $html
        );

        $_REQUEST['full'] = true;
        $_REQUEST['sort_order'] = 'ASC';
        $_REQUEST['order_by_field'] = 'db';
        $_REQUEST['column_name'] = 'Database';
        $html = PMA_getHtmlForServerProcesslist();

        $this->assertContains(
            'Truncate Shown Queries',
            $html
        );
        $this->assertContains(
            'Database',
            $html
        );
        $this->assertContains(
            'DESC',
            $html
        );

        $_REQUEST['sort_order'] = 'DESC';
        $_REQUEST['order_by_field'] = 'Host';
        $_REQUEST['column_name'] = 'Host';
        $html = PMA_getHtmlForServerProcesslist();

        $this->assertContains(
            'Host',
            $html
        );
        $this->assertContains(
            'ASC',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerProcessItem
     *
     * @return void
     */
    public function testPMAGetHtmlForServerProcessItem()
    {
        //parameters
        $process = array(
            "user" => "User1",
            "host" => "Host1",
            "id" => "Id1",
            "db" => "db1",
            "command" => "Command1",
            "state" => "State1",
            "info" => "Info1",
            "state" => "State1",
            "time" => "Time1",
        );
        $odd_row = true;
        $show_full_sql = true;

        $_REQUEST['sort_order'] = "desc";
        $_REQUEST['order_by_field'] = "process";
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;

        //Call the test function
        $html = PMA_getHtmlForServerProcessItem($process, $odd_row, $show_full_sql);

        //validate 1: $kill_process
        $url_params = array(
            'kill' => $process['id'],
            'ajax_request' => true
        );
        $kill_process = 'server_status_processes.php'
            . PMA_URL_getCommon($url_params);
        $this->assertContains(
            $kill_process,
            $html
        );
        $this->assertContains(
            'ajax kill_process',
            $html
        );
        $this->assertContains(
            __('Kill'),
            $html
        );

        //validate 2: $process['User']
        $this->assertContains(
            htmlspecialchars($process['user']),
            $html
        );

        //validate 3: $process['Host']
        $this->assertContains(
            htmlspecialchars($process['host']),
            $html
        );

        //validate 4: $process['db']
        $this->assertContains(
            __('None'),
            $html
        );

        //validate 5: $process['Command']
        $this->assertContains(
            htmlspecialchars($process['command']),
            $html
        );

        //validate 6: $process['Time']
        $this->assertContains(
            $process['time'],
            $html
        );

        //validate 7: $process['state']
        $this->assertContains(
            $process['state'],
            $html
        );

        //validate 8: $process['info']
        $this->assertContains(
            $process['info'],
            $html
        );

        unset($process['info']);
        $html = PMA_getHtmlForServerProcessItem($process, $odd_row, $show_full_sql);

        $this->assertContains(
            '---',
            $html
        );
    }
}
?>
