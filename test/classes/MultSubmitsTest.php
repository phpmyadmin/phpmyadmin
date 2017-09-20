<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\MultSubmits
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\MultSubmits;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * PhpMyAdmin\Tests\MultSubmitsTest class
 *
 * this class is for testing PhpMyAdmin\MultSubmits methods
 *
 * @package PhpMyAdmin-test
 */
class MultSubmitsTest extends TestCase
{
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ActionLinksMode'] = "both";

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => 'relwork',
            'commwork' => 'commwork',
            'pdfwork' => 'pdfwork',
            'column_info' => 'column_info',
            'relation' => 'relation',
        );

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for MultSubmits::getHtmlForReplacePrefixTable
     *
     * @return void
     */
    public function testPMAGetHtmlForReplacePrefixTable()
    {
        $action = 'delete_row';
        $_url_params = array('url_query'=>'PMA_original_url_query');

        //Call the test function
        $html = MultSubmits::getHtmlForReplacePrefixTable($action, $_url_params);

        //form action
        $this->assertContains(
            '<form id="ajax_form" action="delete_row" method="post">',
            $html
        );
        //$Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs($_url_params),
            $html
        );
        //from_prefix
        $this->assertContains(
            '<input type="text" name="from_prefix" id="initialPrefix" />',
            $html
        );
    }

    /**
     * Test for MultSubmits::getHtmlForAddPrefixTable
     *
     * @return void
     */
    public function testPMAGetHtmlForAddPrefixTable()
    {
        $action = 'delete_row';
        $_url_params = array('url_query'=>'PMA_original_url_query');

        //Call the test function
        $html = MultSubmits::getHtmlForAddPrefixTable($action, $_url_params);

        //form action
        $this->assertContains(
            '<form id="ajax_form" action="' . $action . '" method="post">',
            $html
        );
        //$_url_params
        $this->assertContains(
            Url::getHiddenInputs($_url_params),
            $html
        );
        //from_prefix
        $this->assertContains(
            __('Add prefix'),
            $html
        );
    }

    /**
     * Test for MultSubmits::getHtmlForOtherActions
     *
     * @return void
     */
    public function testPMAGetHtmlForOtherActions()
    {
        $what = 'replace_prefix_tbl';
        $action = 'delete_row';
        $_url_params = array('url_query'=>'PMA_original_url_query');
        $full_query = 'select column from PMA_table';

        //Call the test function
        $html = MultSubmits::getHtmlForOtherActions(
            $what, $action, $_url_params, $full_query
        );

        //validate 1: form action
        $this->assertContains(
            '<form action="' . $action . '" method="post">',
            $html
        );
        //validate 2: $_url_params
        $this->assertContains(
            Url::getHiddenInputs($_url_params),
            $html
        );
        //validate 3: conform
        $this->assertContains(
            __('Do you really want to execute the following query?'),
            $html
        );
        //validate 4: query
        $this->assertContains(
            '<code>' . $full_query . '</code>',
            $html
        );
        //validate 5: button : yes or no
        $this->assertContains(
            __('Yes'),
            $html
        );
        $this->assertContains(
            __('No'),
            $html
        );
    }

    /**
     * Test for MultSubmits::getUrlParams
     *
     * @return void
     */
    public function testPMAGetUrlParams()
    {
        $what = 'row_delete';
        $reload = true;
        $action = 'db_delete_row';
        $db = "PMA_db";
        $table = "PMA_table";
        $selected = array(
            "index1" => "table1"
        );
        $views = null;
        $original_sql_query = "original_sql_query";
        $original_url_query = "original_url_query";

        $_url_params = MultSubmits::getUrlParams(
            $what, $reload, $action, $db, $table, $selected, $views,
            $original_sql_query, $original_url_query
        );
        $this->assertEquals(
            $what,
            $_url_params['query_type']
        );
        $this->assertEquals(
            $db,
            $_url_params['db']
        );
        $this->assertEquals(
            array('DELETE FROM `PMA_table` WHERE table1 LIMIT 1;'),
            $_url_params['selected']
        );
        $this->assertEquals(
            $original_sql_query,
            $_url_params['original_sql_query']
        );
        $this->assertEquals(
            $original_url_query,
            $_url_params['original_url_query']
        );
    }

    /**
     * Test for MultSubmits::buildOrExecuteQueryForMulti
     *
     * @return void
     */
    public function testPMABuildOrExecuteQueryForMulti()
    {
        $query_type = 'row_delete';
        $db = "PMA_db";
        $table = "PMA_table";
        $selected = array(
            "table1", "table2"
        );
        $views = null;
        $primary = null;
        $from_prefix = "from_prefix";
        $to_prefix = "to_prefix";

        $_REQUEST['pos'] = 1000;
        $_SESSION['tmpval']['pos'] = 1000;
        $_SESSION['tmpval']['max_rows'] = 25;

        list(
            $result, $rebuild_database_list, $reload_ret,
            $run_parts, $execute_query_later,,
        ) = MultSubmits::buildOrExecuteQueryForMulti(
            $query_type, $selected, $db, $table, $views,
            $primary, $from_prefix, $to_prefix
        );

        //validate 1: $run_parts
        $this->assertEquals(
            true,
            $run_parts
        );

        //validate 2: $result
        $this->assertEquals(
            true,
            $result
        );

        //validate 3: $rebuild_database_list
        $this->assertEquals(
            false,
            $rebuild_database_list
        );

        //validate 4: $reload_ret
        $this->assertEquals(
            null,
            $reload_ret
        );

        $query_type = 'analyze_tbl';
        list(
            ,,,, $execute_query_later,,
        ) = MultSubmits::buildOrExecuteQueryForMulti(
            $query_type, $selected, $db, $table, $views,
            $primary, $from_prefix, $to_prefix
        );

        //validate 5: $execute_query_later
        $this->assertEquals(
            true,
            $execute_query_later
        );
    }

    /**
     * Test for MultSubmits::getQueryFromSelected
     *
     * @return void
     */
    public function testPMAGetQueryFromSelected()
    {
        $what = "drop_tbl";
        $table = "PMA_table";
        $selected = array(
            "table1", "table2"
        );
        $views = array(
            "table1", "table2"
        );

        list($full_query, $reload, $full_query_views)
            = MultSubmits::getQueryFromSelected(
                $what, $table, $selected, $views
            );

        //validate 1: $full_query
        $this->assertContains(
            "DROP VIEW `table1`, `table2`",
            $full_query
        );

        //validate 2: $reload
        $this->assertEquals(
            false,
            $reload
        );

        //validate 3: $full_query_views
        $this->assertEquals(
            null,
            $full_query_views
        );

        $what = "drop_db";

        list($full_query, $reload, $full_query_views)
            = MultSubmits::getQueryFromSelected(
                $what, $table, $selected, $views
            );

        //validate 1: $full_query
        $this->assertContains(
            "DROP DATABASE `table1`;<br />DROP DATABASE `table2`;",
            $full_query
        );

        //validate 2: $reload
        $this->assertEquals(
            true,
            $reload
        );
    }
}
