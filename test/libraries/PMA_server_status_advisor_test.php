<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_status_advisor.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Advisor.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status_advisor.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * class PMA_ServerStatusAdvisor_Test
 *
 * this class is for testing server_status_advisor.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerStatusAdvisor_Test extends PHPUnit_Framework_TestCase
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
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

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
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = false;

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
     * Test for PMA_getHTMLForAdvisor
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHTMLForAdvisor()
    {
        //Call the test function
        $html = PMA_getHTMLForAdvisor();

        //validate 1: Advisor Instructions
        $this->assertContains(
            '<a href="#openAdvisorInstructions">',
            $html
        );
        $this->assertContains(
            '<div id="advisorInstructionsDialog"',
            $html
        );
        //notice
        $this->assertContains(
            'The Advisor system can provide recommendations',
            $html
        );
        $this->assertContains(
            'Do note however that this system provides recommendations',
            $html
        );

        //Advisor datas, we just validate that the Advisor Array is right
        //Advisor logic related with OS should be validate on class Advisor
        $this->assertContains(
            '<div id="advisorData" style="display:none;">',
            $html
        );

        //Advisor data Json encode Items
        $this->assertContains(
            htmlspecialchars(json_encode("parse")),
            $html
        );
        $this->assertContains(
            htmlspecialchars(json_encode("errors")),
            $html
        );
        $this->assertContains(
            htmlspecialchars(json_encode("run")),
            $html
        );
    }
}
