<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\MultSubmits
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\MultSubmits;
use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\MultSubmitsTest class
 *
 * this class is for testing PhpMyAdmin\MultSubmits methods
 *
 * @package PhpMyAdmin-test
 */
class MultSubmitsTest extends TestCase
{
    private $multSubmits;

    /**
     * Test for setUp
     *
     * @return void
     */
    protected function setUp()
    {
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ActionLinksMode'] = "both";

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = [
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
        ];

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;

        $this->multSubmits = new MultSubmits();
    }

    /**
     * Test for getHtmlForReplacePrefixTable
     *
     * @return void
     */
    public function testGetHtmlForReplacePrefixTable()
    {
        $action = 'delete_row';
        $urlParams = ['url_query'=>'PMA_original_url_query'];

        //Call the test function
        $html = $this->multSubmits->getHtmlForReplacePrefixTable($action, $urlParams);

        //form action
        $this->assertContains(
            '<form id="ajax_form" action="delete_row" method="post">',
            $html
        );
        //$Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs($urlParams),
            $html
        );
        //from_prefix
        $this->assertContains(
            '<input type="text" name="from_prefix" id="initialPrefix" />',
            $html
        );
    }

    /**
     * Test for getHtmlForAddPrefixTable
     *
     * @return void
     */
    public function testGetHtmlForAddPrefixTable()
    {
        $action = 'delete_row';
        $urlParams = ['url_query'=>'PMA_original_url_query'];

        //Call the test function
        $html = $this->multSubmits->getHtmlForAddPrefixTable($action, $urlParams);

        //form action
        $this->assertContains(
            '<form id="ajax_form" action="' . $action . '" method="post">',
            $html
        );
        //$urlParams
        $this->assertContains(
            Url::getHiddenInputs($urlParams),
            $html
        );
        //from_prefix
        $this->assertContains(
            __('Add prefix'),
            $html
        );
    }

    /**
     * Test for getHtmlForOtherActions
     *
     * @return void
     */
    public function testGetHtmlForOtherActions()
    {
        $what = 'replace_prefix_tbl';
        $action = 'delete_row';
        $urlParams = ['url_query'=>'PMA_original_url_query'];
        $fullQuery = 'select column from PMA_table';

        //Call the test function
        $html = $this->multSubmits->getHtmlForOtherActions(
            $what,
            $action,
            $urlParams,
            $fullQuery
        );

        //validate 1: form action
        $this->assertContains(
            '<form action="' . $action . '" method="post">',
            $html
        );
        //validate 2: $urlParams
        $this->assertContains(
            Url::getHiddenInputs($urlParams),
            $html
        );
        //validate 3: conform
        $this->assertContains(
            __('Do you really want to execute the following query?'),
            $html
        );
        //validate 4: query
        $this->assertContains(
            '<code>' . $fullQuery . '</code>',
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
     * Test for getUrlParams
     *
     * @return void
     */
    public function testGetUrlParams()
    {
        $what = 'row_delete';
        $reload = true;
        $action = 'db_delete_row';
        $db = "PMA_db";
        $table = "PMA_table";
        $selected = [
            "index1" => "table1"
        ];
        $views = null;
        $originalSqlQuery = "original_sql_query";
        $originalUrlQuery = "original_url_query";

        $urlParams = $this->multSubmits->getUrlParams(
            $what,
            $reload,
            $action,
            $db,
            $table,
            $selected,
            $views,
            $originalSqlQuery,
            $originalUrlQuery
        );
        $this->assertEquals(
            $what,
            $urlParams['query_type']
        );
        $this->assertEquals(
            $db,
            $urlParams['db']
        );
        $this->assertEquals(
            ['DELETE FROM `PMA_table` WHERE table1 LIMIT 1;'],
            $urlParams['selected']
        );
        $this->assertEquals(
            $originalSqlQuery,
            $urlParams['original_sql_query']
        );
        $this->assertEquals(
            $originalUrlQuery,
            $urlParams['original_url_query']
        );
    }

    /**
     * Test for buildOrExecuteQuery
     *
     * @return void
     */
    public function testBuildOrExecuteQuery()
    {
        $queryType = 'row_delete';
        $db = "PMA_db";
        $table = "PMA_table";
        $selected = [
            "table1", "table2"
        ];
        $views = null;
        $primary = null;
        $fromPrefix = "from_prefix";
        $toPrefix = "to_prefix";

        $_REQUEST['pos'] = 1000;
        $_SESSION['tmpval']['pos'] = 1000;
        $_SESSION['tmpval']['max_rows'] = 25;

        list(
            $result, $rebuildDatabaseList, $reloadRet,
            $runParts, $executeQueryLater,,
        ) = $this->multSubmits->buildOrExecuteQuery(
            $queryType,
            $selected,
            $db,
            $table,
            $views,
            $primary,
            $fromPrefix,
            $toPrefix
        );

        //validate 1: $runParts
        $this->assertEquals(
            true,
            $runParts
        );

        //validate 2: $result
        $this->assertEquals(
            true,
            $result
        );

        //validate 3: $rebuildDatabaseList
        $this->assertEquals(
            false,
            $rebuildDatabaseList
        );

        //validate 4: $reloadRet
        $this->assertEquals(
            null,
            $reloadRet
        );

        $queryType = 'analyze_tbl';
        list(
            ,,,, $executeQueryLater,,
        ) = $this->multSubmits->buildOrExecuteQuery(
            $queryType,
            $selected,
            $db,
            $table,
            $views,
            $primary,
            $fromPrefix,
            $toPrefix
        );

        //validate 5: $executeQueryLater
        $this->assertEquals(
            true,
            $executeQueryLater
        );
    }

    /**
     * Test for getQueryFromSelected
     *
     * @return void
     */
    public function testGetQueryFromSelected()
    {
        $what = "drop_tbl";
        $table = "PMA_table";
        $selected = [
            "table1", "table2"
        ];
        $views = [
            "table1", "table2"
        ];

        list($fullQuery, $reload, $fullQueryViews)
            = $this->multSubmits->getQueryFromSelected(
                $what,
                $table,
                $selected,
                $views
            );

        //validate 1: $fullQuery
        $this->assertContains(
            "DROP VIEW `table1`, `table2`",
            $fullQuery
        );

        //validate 2: $reload
        $this->assertEquals(
            false,
            $reload
        );

        //validate 3: $fullQueryViews
        $this->assertEquals(
            null,
            $fullQueryViews
        );

        $what = "drop_db";

        list($fullQuery, $reload, $fullQueryViews)
            = $this->multSubmits->getQueryFromSelected(
                $what,
                $table,
                $selected,
                $views
            );

        //validate 1: $fullQuery
        $this->assertContains(
            "DROP DATABASE `table1`;<br />DROP DATABASE `table2`;",
            $fullQuery
        );

        //validate 2: $reload
        $this->assertEquals(
            true,
            $reload
        );
    }
}
