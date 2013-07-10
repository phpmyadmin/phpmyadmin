<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_databases.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/build_html_for_db.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_databases.lib.php';
require_once 'libraries/mysql_charsets.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * PMA_ServerDatabases_Test class
 *
 * this class is for testing server_databases.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
define("PMA_USR_BROWSER_AGENT", "IE");
define("PMA_USR_BROWSER_VER", "8.0");

class PMA_ServerDatabases_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['MaxDbList'] = 100;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['SQP']['fmtType'] = 'none';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['ActionLinksMode'] = "both";
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'db_structure.php';
        
        $GLOBALS['table'] = "table";
        $GLOBALS['server_master_status'] = false;
        $GLOBALS['server_slave_status'] = false;
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['text_dir'] = "text_dir";
        
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();   

    }

    /**
     * Test for PMA_getHtmlForDatabase
     *
     * @return void
     */
    public function testPMAGetHtmlForDatabase()
    {   
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $databases = array(
            array("SCHEMA_NAME" => "pma_bookmark"),
            array("SCHEMA_NAME" => "information_schema"),
            array("SCHEMA_NAME" => "mysql"),
            array("SCHEMA_NAME" => "performance_schema"),
            array("SCHEMA_NAME" => "phpmyadmin")                
        );
        
        $databases_count = 5;
        $pos = 0;
        $dbstats = 0;
        $sort_by = "SCHEMA_NAME";
        $sort_order = "asc";
        $is_superuser = true;
        $cfg = array(
            "AllowUserDropDatabase" => false,
            "ActionLinksMode" => "both",
        );
        $replication_types = array("master", "slave");
        $replication_info = array(
            "master" => array(
                 "status" => true,
                 "Ignore_DB" => array("DB" => "Ignore_DB"),
                 "Do_DB" => array(""),
            ),
            "slave" => array(
                 "status" => false,
                 "Ignore_DB" => array("DB" => "Ignore_DB"),
                 "Do_DB" => array(""),
            ),
        );
        $url_query = "token=27ae04f0b003a84e5c2796182f361ff1";
               
        $html = PMA_getHtmlForDatabase(
            $databases, 
            $databases_count, 
            $pos, 
            $dbstats, 
            $sort_by, 
            $sort_order, 
            $is_superuser, 
            $cfg, 
            $replication_types, 
            $replication_info, 
            $url_query
        );

        //validate 1: General info
        $this->assertContains(
            '<div id="tableslistcontainer">',
            $html
        );
        
        //validate 2:ajax Form
        $this->assertContains(
            '<form class="ajax" action="server_databases.php" ',
            $html
        );
        
        $this->assertContains(
            '<table id="tabledatabases" class="data">',
            $html
        );
        
        //validate 3: PMA_getHtmlForColumnOrderWithSort
        $this->assertContains(
            '<a href="server_databases.php?pos=0',
            $html
        );
        $this->assertContains(
            'sort_by=SCHEMA_NAME',
            $html
        );
        
        //validate 4: PMA_getHtmlForDatabaseList
        $this->assertContains(
            'title="pma_bookmark" value="pma_bookmark"',
            $html
        );
        $this->assertContains(
            'title="information_schema" value="information_schema"',
            $html
        );
        $this->assertContains(
            'title="performance_schema" value="performance_schema"',
            $html
        );
        $this->assertContains(
            'title="phpmyadmin" value="phpmyadmin"',
            $html
        );
       
        //validate 5: PMA_getHtmlForTableFooter
        $this->assertContains(
            'Total: <span id="databases_count">5</span>',
            $html
        );
        
        //validate 6: PMA_getHtmlForTableFooterButtons
        $this->assertContains(
            'Check All',
            $html
        );
        
        //validate 7: PMA_getHtmlForNoticeEnableStatistics
        $this->assertContains(
            'Note: Enabling the database statistics here might cause heavy traffic',
            $html
        );
        $this->assertContains(
            'Enable Statistics',
            $html
        );
    }
}
