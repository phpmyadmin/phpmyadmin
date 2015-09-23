<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_status_variables.lib.php
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
require_once 'libraries/server_status_variables.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * class PMA_ServerStatusVariables_Test
 *
 * this class is for testing server_status_variables.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerStatusVariables_Test extends PHPUnit_Framework_TestCase
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

        $dbi->expects($this->at(0))
            ->method('tryQuery')
            ->with('SHOW GLOBAL STATUS')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->will($this->returnValue(array("Aborted_clients", "0")));
        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->will($this->returnValue(array("Aborted_connects", "0")));
        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->will($this->returnValue(array("Com_delete_multi", "0")));
        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->will($this->returnValue(array("Com_create_function", "0")));
        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->will($this->returnValue(array("Com_empty_query", "0")));
        $dbi->expects($this->at(6))
            ->method('fetchRow')
            ->will($this->returnValue(false));

        $dbi->expects($this->at(7))->method('freeResult');

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
        $this->ServerStatusData = new PMA_ServerStatusData();
    }

    /**
     * Test for PMA_getHtmlForFilter
     *
     * @return void
     */
    public function testPMAGetHtmlForFilter()
    {
        //Call the test function
        $html = PMA_getHtmlForFilter($this->ServerStatusData);

        //validate 1: PMA_getHtmlForFilter
        $this->assertContains(
            '<fieldset id="tableFilter">',
            $html
        );
        $this->assertContains(
            'server_status_variables.php',
            $html
        );
        //validate 2: filter
        $this->assertContains(
            '<label for="filterText">Containing the word:</label>',
            $html
        );
        //validate 3:Items
        $this->assertContains(
            '<label for="filterAlert">Show only alert values</label>',
            $html
        );
        $this->assertContains(
            'Filter by category',
            $html
        );
        $this->assertContains(
            'Show unformatted values',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForLinkSuggestions
     *
     * @return void
     */
    public function testPMAGetHtmlForLinkSuggestions()
    {
        //Call the test function
        $html = PMA_getHtmlForLinkSuggestions($this->ServerStatusData);

        //validate 1: PMA_getHtmlForLinkSuggestions
        $this->assertContains(
            '<div id="linkSuggestions" class="defaultLinks"',
            $html
        );
        //validate 2: linkSuggestions
        $this->assertContains(
            '<p class="notice">Related links:',
            $html
        );
        $this->assertContains(
            'Flush (close) all tables',
            $html
        );
        $this->assertContains(
            '<span class="status_binlog_cache">',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForVariablesList
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForVariablesList()
    {
        //Call the test function
        $html = PMA_getHtmlForVariablesList($this->ServerStatusData);

        //validate 1: PMA_getHtmlForVariablesList
        $table = '<table class="data noclick" '
            . 'id="serverstatusvariables">';
        $this->assertContains(
            $table,
            $html
        );
        $this->assertContains(
            '<th>Variable</th>',
            $html
        );
        $this->assertContains(
            '<th>Value</th>',
            $html
        );
        $this->assertContains(
            '<th>Description</th>',
            $html
        );
        //validate 3:Items
        $this->assertContains(
            '<th class="name">Aborted clients',
            $html
        );
        $this->assertContains(
            '<span class="allfine">0</span>',
            $html
        );
        $this->assertContains(
            '<th class="name">Aborted connects',
            $html
        );
        $this->assertContains(
            '<th class="name">Com delete multi',
            $html
        );
        $this->assertContains(
            '<th class="name">Com create function',
            $html
        );
        $this->assertContains(
            '<th class="name">Com empty query',
            $html
        );
    }
}
