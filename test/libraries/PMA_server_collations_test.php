<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_collations.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
//$GLOBALS
$GLOBALS['server'] = 1;
$GLOBALS['is_superuser'] = false;
$GLOBALS['cfg']['ServerDefault'] = 1;
$GLOBALS['url_query'] = "url_query";
$GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
$GLOBALS['lang'] = "en";
$GLOBALS['available_languages']= array(
		"en" => array("English", "US-ENGLISH"), 
		"ch" => array("Chinese", "TW-Chinese")
);
$GLOBALS['text_dir'] = "text_dir";

//$_SESSION
require_once 'libraries/Theme.class.php';
$_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme'); 

require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/server_collations.lib.php';

class PMA_ServerCollations_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['is_ajax_request'] = true;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['SQP']['fmtType'] = 'none';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        
        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';    
    }

    /**
     * Test for PMA_getHtmlForCharsets
     *
     * @return void
     */
    public function testPMA_getHtmlForCharsets()
    {
        global $mysql_charsets, $mysql_collations, $mysql_charsets_descriptions, 
        $mysql_default_collations, $mysql_collations_available;
        
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

        $GLOBALS['dbi'] = $dbi;

        //Call the test function
        $html = PMA_getHtmlForCharsets(
            $mysql_charsets,
            $mysql_collations,
            $mysql_charsets_descriptions,
            $mysql_default_collations,
            $mysql_collations_available
        );
    
        //validate 1: Charset HTML
        $this->assertContains(
            '<div id="div_mysql_charset_collations">',
            $html
        );
        $this->assertContains(
            __('Collation'),
            $html
        );
        $this->assertContains(
            __('Description'),
            $html
        );
        //validate 2: Charset Item
        $this->assertContains(
            '<td>utf8_bin</td>',
            $html
        );
        $this->assertContains(
            '<td>Unicode (multilingual), Binary</td>',
            $html
        );
        $this->assertContains(
            '<td>utf8_general_ci</td>',
            $html
        );
        $this->assertContains(
            '<td>Unicode (multilingual), case-insensitive</td>',
            $html
        );
    }
}
