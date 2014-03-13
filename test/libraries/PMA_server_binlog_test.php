<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_bin_log.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_bin_log.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/database_interface.inc.php';

/**
 * PMA_ServerBinlog_Test class
 *
 * this class is for testing server_bin_log.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerBinlog_Test extends PHPUnit_Framework_TestCase
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

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Test for PMA_getLogSelector
     *
     * @return void
     */
    public function testPMAGetLogSelector()
    {
        $binary_log_file_names = array();
        $binary_log_file_names[] = array("Log_name"=>"index1", "File_size"=>100);
        $binary_log_file_names[] = array("Log_name"=>"index2", "File_size"=>200);

        $url_params = array();
        $url_params['log'] = "log";
        $url_params['dontlimitchars'] = 1;

        $html = PMA_getLogSelector($binary_log_file_names, $url_params);
        $this->assertContains(
            'Select binary log to view',
            $html
        );
        $this->assertContains(
            '<option value="index1" selected="selected">index1 (100 B)</option>',
            $html
        );
        $this->assertContains(
            '<option value="index2">index2 (200 B)</option>',
            $html
        );
    }

    /**
     * Test for PMA_getLogInfo
     *
     * @return void
     * @group medium
     */
    public function testPMAGetLogInfo()
    {
        $url_params = array();
        $url_params['log'] = "log";
        $url_params['dontlimitchars'] = 1;

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //expects return value
        $result = array(
            array(
                "SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10",
                null,
                1,
                true,
                array("log1"=>"logd")
            ),
            array(
                array("log2"=>"logb"),
                null,
                0,
                false,
                'executed'
            )
        );
        $value = array(
                'Info' => "index1_Info",
                'Log_name' => "index1_Log_name",
                'Pos' => "index1_Pos",
                'Event_type' => "index1_Event_type",
                'End_log_pos' => "index1_End_log_pos",
                'Server_id' => "index1_Server_id",
        );
        $count = 3;

        //expects functions
        $dbi->expects($this->once())->method('query')
            ->will($this->returnValue($result));

        $dbi->expects($this->once())->method('numRows')
            ->will($this->returnValue($count));

        $dbi->expects($this->at(0))->method('fetchAssoc')
            ->will($this->returnValue($value));

        $dbi->expects($this->at(1))->method('fetchAssoc')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $html = PMA_getLogInfo($url_params);

        //validate 1: the sql has been executed
        $this->assertContains(
            'Your SQL query has been executed successfully',
            $html
        );
        //validate 2: SQL
        $this->assertContains(
            "SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10",
            $html
        );
        //validate 3: BINLOG HTML
        $this->assertContains(
            '<table cellpadding="2" cellspacing="1" id="binlogTable">',
            $html
        );
        //validate 4: PMA_getNavigationRow is right
        $urlNavigation = 'server_binlog.php?log=log&amp;dontlimitchars=1&amp;'
            . 'pos=3&amp;server=1&amp';
        $this->assertContains(
            $urlNavigation,
            $html
        );
        $this->assertContains(
            'title="Previous"',
            $html
        );
        //validate 5: Log Item
        $this->assertContains(
            'Log name',
            $html
        );
        $this->assertContains(
            'Position',
            $html
        );
        $this->assertContains(
            'Event type',
            $html
        );
        $this->assertContains(
            'Server ID',
            $html
        );
        $this->assertContains(
            'Original position',
            $html
        );
    }

    /**
     * Test for PMA_getAllLogItemInfo
     *
     * @return void
     */
    public function testPMAGetAllLogItemInfo()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchAssoc = array(
            'Info' => 'Info',
            'Log_name' => 'Log_name',
            'Pos' => 'Pos',
            'Event_type' => 'Event_type',
            'Server_id' => 'Server_id',
            'Orig_log_pos' => 'Orig_log_pos',
            'End_log_pos' => 'End_log_pos',
        );
        $dbi->expects($this->at(0))->method('fetchAssoc')
            ->will($this->returnValue($fetchAssoc));

        $dbi->expects($this->at(1))->method('fetchAssoc')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['cfg']['LimitChars'] = 2;

        $result = array();
        $dontlimitchars = ";";

        $html = PMA_getAllLogItemInfo($result, $dontlimitchars);
        $value = $fetchAssoc;
        $this->assertContains(
            $value['Log_name'],
            $html
        );
        $this->assertContains(
            $value['Pos'],
            $html
        );
        $this->assertContains(
            $value['Event_type'],
            $html
        );
        $this->assertContains(
            $value['Server_id'],
            $html
        );
        $this->assertContains(
            $value['Orig_log_pos'],
            $html
        );
        $this->assertContains(
            htmlspecialchars($value['Info']),
            $html
        );
    }
}
