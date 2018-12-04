<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Status\Processes
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server\Status;

use PhpMyAdmin\Core;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\Server\Status\ProcessesTest class
 *
 * this class is for testing PhpMyAdmin\Server\Status\Processes methods
 *
 * @package PhpMyAdmin-test
 */
class ProcessesTest extends TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = false;
        $GLOBALS['replication_types'] = array();

        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for Processes::getHtmlForProcessListAutoRefresh
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForProcessListAutoRefresh()
    {
        $html = Processes::getHtmlForProcessListAutoRefresh();

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
     * Test for Processes::getHtmlForServerProcesslist
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
            "Info" => "Info1",
            "State" => "State1",
            "Time" => "Time1"
        );
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;
        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will($this->onConsecutiveCalls($process));

        $html = Processes::getHtmlForServerProcesslist();

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

        $_POST['full'] = true;
        $_POST['sort_order'] = 'ASC';
        $_POST['order_by_field'] = 'db';
        $_POST['column_name'] = 'Database';
        $html = Processes::getHtmlForServerProcesslist();

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

        $_POST['sort_order'] = 'DESC';
        $_POST['order_by_field'] = 'Host';
        $_POST['column_name'] = 'Host';
        $html = Processes::getHtmlForServerProcesslist();

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
     * Test for Processes::getHtmlForServerProcessItem
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
            "info" => "Info1",
            "state" => "State1",
            "time" => "Time1",
        );
        $show_full_sql = true;

        $_POST['sort_order'] = "desc";
        $_POST['order_by_field'] = "process";
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;

        //Call the test function
        $html = Processes::getHtmlForServerProcessItem($process, $show_full_sql);

        //validate 1: $kill_process
        $kill_process = 'href="server_status_processes.php" data-post="'
            . Url::getCommon(['kill' => $process['id']], '') . '"';
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
        $html = Processes::getHtmlForServerProcessItem($process, $show_full_sql);

        $this->assertContains(
            '---',
            $html
        );
    }
}
