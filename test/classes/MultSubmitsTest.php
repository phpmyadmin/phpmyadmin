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
        $what = 'drop_tbl';
        $action = 'index.php?route=/table/structure';
        $db = 'PMA_db';
        $table = 'PMA_table';
        $selected = [
            'index1' => 'table1',
        ];
        $views = null;

        $urlParams = $this->multSubmits->getUrlParams(
            $what,
            $action,
            $db,
            $table,
            $selected,
            $views
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
            $table,
            $urlParams['table']
        );
        $this->assertEquals(
            ['table1'],
            $urlParams['selected']
        );
    }

    /**
     * Test for buildOrExecuteQuery
     *
     * @return void
     */
    public function testBuildOrExecuteQuery()
    {
        $queryType = 'empty_tbl';
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
            $result,
            $reloadRet,
            $runParts,
            $executeQueryLater,
            ,
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

        //validate 4: $reloadRet
        $this->assertEquals(
            null,
            $reloadRet
        );

        $queryType = 'analyze_tbl';
        list(,,, $executeQueryLater,,) = $this->multSubmits->buildOrExecuteQuery(
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

        list($fullQuery, $fullQueryViews) = $this->multSubmits->getQueryFromSelected(
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

        //validate 3: $fullQueryViews
        $this->assertEquals(
            null,
            $fullQueryViews
        );
    }
}
