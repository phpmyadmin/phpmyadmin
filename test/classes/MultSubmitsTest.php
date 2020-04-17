<?php
/**
 * tests for PhpMyAdmin\MultSubmits
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\MultSubmits;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\MultSubmitsTest class
 *
 * this class is for testing PhpMyAdmin\MultSubmits methods
 */
class MultSubmitsTest extends TestCase
{
    /** @var MultSubmits */
    private $multSubmits;

    /**
     * Test for setUp
     */
    protected function setUp(): void
    {
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'table_coords' => 'table_name',
            'displaywork' => 'displaywork',
            'db' => 'information_schema',
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

        $relation = new Relation($GLOBALS['dbi']);
        $this->multSubmits = new MultSubmits(
            $GLOBALS['dbi'],
            new Template(),
            new Transformations(),
            new RelationCleanup($GLOBALS['dbi'], $relation),
            new Operations($GLOBALS['dbi'], $relation)
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
        $db = 'PMA_db';
        $table = 'PMA_table';
        $selected = [
            'index1' => 'table1',
        ];
        $views = null;
        $originalSqlQuery = 'original_sql_query';
        $originalUrlQuery = 'original_url_query';

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
        $db = 'PMA_db';
        $table = 'PMA_table';
        $selected = [
            'table1',
            'table2',
        ];
        $views = null;
        $primary = null;
        $fromPrefix = 'from_prefix';
        $toPrefix = 'to_prefix';

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
        $what = 'drop_tbl';
        $table = 'PMA_table';
        $selected = [
            'table1',
            'table2',
        ];
        $views = [
            'table1',
            'table2',
        ];

        list($fullQuery, $reload, $fullQueryViews)
            = $this->multSubmits->getQueryFromSelected(
                $what,
                $table,
                $selected,
                $views
            );

        //validate 1: $fullQuery
        $this->assertStringContainsString(
            'DROP VIEW `table1`, `table2`',
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

        $what = 'drop_db';

        list($fullQuery, $reload, $fullQueryViews)
            = $this->multSubmits->getQueryFromSelected(
                $what,
                $table,
                $selected,
                $views
            );

        //validate 1: $fullQuery
        $this->assertStringContainsString(
            'DROP DATABASE `table1`;<br>DROP DATABASE `table2`;',
            $fullQuery
        );

        //validate 2: $reload
        $this->assertEquals(
            true,
            $reload
        );
    }
}
