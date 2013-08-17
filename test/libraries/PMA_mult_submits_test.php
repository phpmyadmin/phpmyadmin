<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for mult_submits.lib.php
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
require_once 'libraries/mult_submits.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * class PMA_MultSubmits_Test
 *
 * this class is for testing mult_submits.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_MultSubmits_Test extends PHPUnit_Framework_TestCase
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
    }

    /**
     * Test for PMA_getHtmlForReplacePrefixTable
     *
     * @return void
     */
    public function testPMAGetHtmlForReplacePrefixTable()
    {
        $what = 'replace_prefix_tbl';
        $action = 'delete_row';
        $_url_params = array('url_query'=>'PMA_original_url_query');
        
        //Call the test function
        $html = PMA_getHtmlForReplacePrefixTable($what, $action, $_url_params);

        //validate 1: form action
        $this->assertContains(
            '<form action="' . $action . '" method="post">',
            $html
        );
        //validate 2: $PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs($_url_params),
            $html
        );
        //validate 3: title
        $this->assertContains(
            __('Replace table prefix:'),
            $html
        );
        //validate 4: from_prefix
        $this->assertContains(
            '<input type="text" name="from_prefix" id="initialPrefix" />',
            $html
        );
        //validate 5: Submit button
        $this->assertContains(
            __('Submit'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForAddPrefixTable
     *
     * @return void
     */
    public function testPMAGetHtmlForAddPrefixTable()
    {
        $action = 'delete_row';
        $_url_params = array('url_query'=>'PMA_original_url_query');
        
        //Call the test function
        $html = PMA_getHtmlForAddPrefixTable($action, $_url_params);

        //validate 1: form action
        $this->assertContains(
            '<form action="' . $action . '" method="post">',
            $html
        );
        //validate 2: $_url_params
        $this->assertContains(
            PMA_URL_getHiddenInputs($_url_params),
            $html
        );
        //validate 3: title
        $this->assertContains(
            '<legend>' . __('Add table prefix:') . '</legend>',
            $html
        );
        //validate 4: from_prefix
        $this->assertContains(
            __('Add prefix'),
            $html
        );
        //validate 5: Submit
        $this->assertContains(
            __('Submit'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForOtherActions
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
        $html = PMA_getHtmlForOtherActions(
            $what, $action, $_url_params, $full_query
        );

        //validate 1: form action
        $this->assertContains(
            '<form action="' . $action . '" method="post">',
            $html
        );
        //validate 2: $_url_params
        $this->assertContains(
            PMA_URL_getHiddenInputs($_url_params),
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
     * Test for PMA_getUrlParams
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
        
        $_url_params = PMA_getUrlParams(
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
            array('DELETE FROM `PMA_db`.`PMA_table` WHERE table1 LIMIT 1;'),
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
}

