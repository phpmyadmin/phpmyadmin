<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Status\Variables
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server\Status;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Variables;
use PhpMyAdmin\Theme;
use PHPUnit\Framework\TestCase;

/**
 * This class is for testing PhpMyAdmin\Server\Status\Variables methods
 *
 * @package PhpMyAdmin-test
 */
class VariablesTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public $serverStatusData;

    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
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
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['replication_info']['master']['status'] = false;
        $GLOBALS['replication_info']['slave']['status'] = false;

        $GLOBALS['table'] = "table";

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
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
                DatabaseInterface::CONNECT_USER,
                0,
                $server_status
            ),
            array(
                "SHOW GLOBAL VARIABLES",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_variables
            ),
            array(
                "SELECT concat('Com_', variable_name), variable_value "
                    . "FROM data_dictionary.GLOBAL_STATEMENTS",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
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
        $this->serverStatusData = new Data();
    }

    /**
     * Test for Variables::getHtmlForFilter
     *
     * @return void
     */
    public function testPMAGetHtmlForFilter()
    {
        //Call the test function
        $html = Variables::getHtmlForFilter($this->serverStatusData);

        //validate 1: Variables::getHtmlForFilter
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
     * Test for Variables::getHtmlForLinkSuggestions
     *
     * @return void
     */
    public function testPMAGetHtmlForLinkSuggestions()
    {
        //Call the test function
        $html = Variables::getHtmlForLinkSuggestions($this->serverStatusData);

        //validate 1: Variables::getHtmlForLinkSuggestions
        $this->assertContains(
            '<div id="linkSuggestions" class="defaultLinks hide"',
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
     * Test for Variables::getHtmlForVariablesList
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForVariablesList()
    {
        //Call the test function
        $html = Variables::getHtmlForVariablesList($this->serverStatusData);

        //validate 1: Variables::getHtmlForVariablesList
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
