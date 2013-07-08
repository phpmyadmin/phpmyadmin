<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_server_status_monitor.lib.php
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
require_once 'libraries/server_status_monitor.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';

class PMA_ServerStatusMonitor_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['SQP']['fmtType'] = 'none';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['host'] = "localhost";   
        $GLOBALS['cfg']['MySQLManualType'] = 'viewable';  
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['server_master_status'] = false;
        $GLOBALS['server_slave_status'] = false;
        
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
        
        $dbi->expects($this->at(0))->method('fetchResult')
            ->with('SHOW GLOBAL STATUS', 0, 1)
            ->will($this->returnValue($server_status));
        
        $server_variables = array();
        $dbi->expects($this->at(1))->method('fetchResult')
            ->with('SHOW GLOBAL VARIABLES', 0, 1)
            ->will($this->returnValue($server_variables));
         
        $GLOBALS['dbi'] = $dbi;
        
        $this->ServerStatusData = new PMA_ServerStatusData();
    }

    /**
     * Test for PMA_getHtmlForMonitor
     *
     * @return void
     */
    public function testPMAGetHtmlForMonitor()
    {
        //Call the test function          
        $html = PMA_getHtmlForMonitor($this->ServerStatusData);

        //validate 1: PMA_getHtmlForTabLinks
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
        //validate 2: PMA_getHtmlForSettingsDialog
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
        //validate 3: PMA_getHtmlForInstructionsDialog
        $this->assertContains(
            __('Monitor Instructions'),
            $html
        ); 
        $this->assertContains(
            'Instructions/Setup',
            $html
        ); 
        $this->assertContains(
            'Settings</a><a href="#monitorInstructionsDialog">',
            $html
        );        	
        //validate 4: PMA_getHtmlForAddChartDialog
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
     * Test for PMA_getHtmlForClientSideDataAndLinks
     *
     * @return void
     */
    public function testPMAGetHtmlForClientSideDataAndLinks()
    {
        //Call the test function          
        $html = PMA_getHtmlForClientSideDataAndLinks($this->ServerStatusData);

        //validate 1: PMA_getHtmlForClientSideDataAndLinks
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
}

