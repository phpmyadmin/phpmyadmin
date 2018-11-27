<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Status\Monitor
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\Status;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PHPUnit\Framework\TestCase;

/**
 * this class is for testing PhpMyAdmin\Server\Status\Monitor methods
 *
 * @package PhpMyAdmin-test
 */
class MonitorTest extends TestCase
{
    /**
     * @var Data
     */
    private $statusData;

    /**
     * @var Monitor
     */
    private $statusMonitor;

    /**
     * Test for setUp
     *
     * @return void
     */
    protected function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

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
                $server_status
            ],
            [
                "SHOW GLOBAL VARIABLES",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_variables
            ],
            [
                "SELECT concat('Com_', variable_name), variable_value "
                    . "FROM data_dictionary.GLOBAL_STATEMENTS",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_status
            ],
        ];

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $this->statusMonitor = new Monitor();
        $this->statusData = new Data();
    }

    /**
     * Test for getHtmlForMonitor
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForMonitor()
    {
        //Call the test function
        $html = $this->statusMonitor->getHtmlForMonitor($this->statusData);

        //validate 1: getHtmlForTabLinks
        $this->assertContains(
            '<div class="tabLinks">',
            $html
        );
        $this->assertContains(
            __('Start Monitor'),
            $html
        );
        $this->assertContains(
            __('Settings'),
            $html
        );
        $this->assertContains(
            __('Done dragging (rearranging) charts'),
            $html
        );
        //validate 2: getHtmlForSettingsDialog
        $this->assertContains(
            '<div class="popupContent settingsPopup">',
            $html
        );
        $this->assertContains(
            '<a href="#settingsPopup" class="popupLink">',
            $html
        );
        $this->assertContains(
            __('Enable charts dragging'),
            $html
        );
        $this->assertContains(
            '<option>3</option>',
            $html
        );
        //validate 3: getHtmlForInstructionsDialog
        $this->assertContains(
            __('Monitor Instructions'),
            $html
        );
        $this->assertContains(
            'monitorInstructionsDialog',
            $html
        );
        //validate 4: getHtmlForAddChartDialog
        $this->assertContains(
            '<div id="addChartDialog"',
            $html
        );
        $this->assertContains(
            '<div id="chartVariableSettings">',
            $html
        );
        $this->assertContains(
            '<option>Processes</option>',
            $html
        );
        $this->assertContains(
            '<option>Connections</option>',
            $html
        );
    }

    /**
     * Test for getHtmlForClientSideDataAndLinks
     *
     * @return void
     */
    public function testPMAGetHtmlForClientSideDataAndLinks()
    {
        //Call the test function
        $html = $this->statusMonitor->getHtmlForClientSideDataAndLinks($this->statusData);

        //validate 1: getHtmlForClientSideDataAndLinks
        $from = '<form id="js_data" class="hide">'
            . '<input type="hidden" name="server_time"';
        $this->assertContains(
            $from,
            $html
        );
        //validate 2: inputs
        $this->assertContains(
            '<input type="hidden" name="is_superuser"',
            $html
        );
        $this->assertContains(
            '<input type="hidden" name="server_db_isLocal"',
            $html
        );
        $this->assertContains(
            '<div id="explain_docu" class="hide">',
            $html
        );
    }

    /**
     * Test for getJsonForLogDataTypeSlow
     *
     * @return void
     */
    public function testPMAGetJsonForLogDataTypeSlow()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 11,
        ];

        $value2 = [
            'sql_text' => 'update sql_text',
            '#' => 10,
        ];

        $dbi->expects($this->at(1))->method('fetchAssoc')
            ->will($this->returnValue($value));
        $dbi->expects($this->at(2))->method('fetchAssoc')
            ->will($this->returnValue($value2));
        $dbi->expects($this->at(3))->method('fetchAssoc')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $start = 0;
        $end = 10;
        $ret = $this->statusMonitor->getJsonForLogDataTypeSlow($start, $end);

        $result_rows = [
            ['sql_text' => 'insert sql_text', '#' => 11],
            ['sql_text' => 'update sql_text', '#' => 10]
        ];
        $result_sum = ['insert' => 11, 'TOTAL' => 21, 'update' => 10];
        $this->assertEquals(
            2,
            $ret['numRows']
        );
        $this->assertEquals(
            $result_rows,
            $ret['rows']
        );
        $this->assertEquals(
            $result_sum,
            $ret['sum']
        );
    }

    /**
     * Test for getJsonForLogDataTypeGeneral
     *
     * @return void
     */
    public function testPMAGetJsonForLogDataTypeGeneral()
    {
        $_REQUEST['limitTypes'] = true;

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 10,
            'argument' => 'argument argument2',
        ];

        $value2 = [
            'sql_text' => 'update sql_text',
            '#' => 11,
            'argument' => 'argument3 argument4',
        ];

        $dbi->expects($this->at(1))->method('fetchAssoc')
            ->will($this->returnValue($value));
        $dbi->expects($this->at(2))->method('fetchAssoc')
            ->will($this->returnValue($value2));
        $dbi->expects($this->at(3))->method('fetchAssoc')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $start = 0;
        $end = 10;
        $ret = $this->statusMonitor->getJsonForLogDataTypeGeneral($start, $end);

        $result_rows = [
            $value,
            $value2,
        ];
        $result_sum = ['argument' => 10, 'TOTAL' => 21, 'argument3' => 11];

        $this->assertEquals(
            2,
            $ret['numRows']
        );
        $this->assertEquals(
            $result_rows,
            $ret['rows']
        );
        $this->assertEquals(
            $result_sum,
            $ret['sum']
        );
    }

    /**
     * Test for getJsonForLoggingVars
     *
     * @return void
     */
    public function testPMAGetJsonForLoggingVars()
    {
        $_REQUEST['varName'] = "varName";

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 22,
            'argument' => 'argument argument2',
        ];

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($value));

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $ret = $this->statusMonitor->getJsonForLoggingVars();

        //validate that, the result is the same as fetchResult
        $this->assertEquals(
            $value,
            $ret
        );
    }

    /**
     * Test for getJsonForQueryAnalyzer
     *
     * @return void
     */
    public function testPMAGetJsonForQueryAnalyzer()
    {
        $_REQUEST['database'] = "database";
        $_REQUEST['query'] = 'query';
        $GLOBALS['server'] = 'server';
        $GLOBALS['cached_affected_rows'] = 'cached_affected_rows';
        $_SESSION['cache']['server_server']['profiling_supported'] = true;

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 33,
            'argument' => 'argument argument2',
        ];

        $dbi->expects($this->at(4))->method('fetchAssoc')
            ->will($this->returnValue($value));
        $dbi->expects($this->at(5))->method('fetchAssoc')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $ret = $this->statusMonitor->getJsonForQueryAnalyzer();

        $this->assertEquals(
            'cached_affected_rows',
            $ret['affectedRows']
        );
        $this->assertEquals(
            [],
            $ret['profiling']
        );
        $this->assertEquals(
            [$value],
            $ret['explain']
        );
    }
}
