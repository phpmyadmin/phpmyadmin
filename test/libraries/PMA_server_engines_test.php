<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_engines.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

if (! defined('PMA_DRIZZLE')) {
	define('PMA_DRIZZLE', 0);
}

require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_engines.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/StorageEngine.class.php';

class PMA_ServerEngines_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['SQP']['fmtType'] = 'none';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['MySQLManualType'] = 'viewable';
        
        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';
        
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();     
    }

    /**
     * Test for PMA_getHtmlForServerEngines for all engines
     *
     * @return void
     */
    public function testPMA_getPluginAndModuleInfo()
    {   
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        
        //expects return value
        $engines = array(
        	"FEDERATED" => array(
        	     "Engine" => "FEDERATED",
        	     "Support" => "NO",
        	     "Comment" => "Federated MySQL storage engine",
        	     "Transactions" => NULL,
        	     "Savepoints" => NULL,
             ),
        	"MRG_MYISAM" => array(
        	     "Engine" => "MRG_MYISAM",
        	     "Support" => "YES",
        	     "Comment" => "Collection of identical MyISAM tables",
        	     "Transactions" => "NO",
        	     "Savepoints" => "NO",
             )
        );
        
        //expects functions
        $dbi->expects($this->once())->method('fetchResult')
        ->will($this->returnValue($engines));
		
        //test PMA_getHtmlForAllServerEngines
        $html = PMA_getHtmlForServerEngines();

        //validate 1: Item header
        $this->assertContains(
            '<th>Storage Engine</th>',
            $html
        );
        $this->assertContains(
            '<th>Description</th>',
            $html
        );
        //validate 2: FEDERATED
        $this->assertContains(
            '<td>Federated MySQL storage engine</td>',
            $html
        );
        $this->assertContains(
            'FEDERATED',
            $html
        );
        $this->assertContains(
            'href="server_engines.php?engine=FEDERATED',
            $html
        );
        
        //validate 3: MRG_MYISAM
        $this->assertContains(
            '<td>Collection of identical MyISAM tables</td>',
            $html
        );
        $this->assertContains(
            'MRG_MYISAM',
            $html
        );
        $this->assertContains(
            'href="server_engines.php?engine=MRG_MYISAM',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerEngines for specific engines "FEDERATED"
     *
     * @return void
     */
    public function testPMA_getPluginAndModuleInfo_Specific()
    {   
    	$_REQUEST['engine'] = "FEDERATED";
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        		
        //test PMA_getHtmlForAllServerEngines for specific engines "FEDERATED"
        $html = PMA_getHtmlForServerEngines();

        //validate 1: Engine header
        $this->assertContains(
            'FEDERATED',
            $html
        );
        $this->assertContains(
            'Federated MySQL storage engine',
            $html
        );
        $this->assertContains(
            'This MySQL server does not support the FEDERATED storage engine.',
            $html
        );
        $this->assertContains(
            'There is no detailed status information available for this storage engine',
            $html
        );
    }
}
