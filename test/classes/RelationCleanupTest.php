<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\RelationCleanup
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Tests\RelationCleanupDbiMock;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\RelationCleanupTest class
 *
 * this class is for testing PhpMyAdmin\RelationCleanup methods
 *
 * @package PhpMyAdmin-test
 */
class RelationCleanupTest extends TestCase
{
    /**
     * @var Relation
     */
    private $relation;

    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        $_SESSION['relation'] = array();
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = "user";
        $GLOBALS['cfg']['Server']['pmadb'] = "pmadb";
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'bookmark';
        $GLOBALS['cfg']['Server']['relation'] = 'relation';
        $GLOBALS['cfg']['Server']['table_info'] = 'table_info';
        $GLOBALS['cfg']['Server']['table_coords'] = 'table_coords';
        $GLOBALS['cfg']['Server']['column_info'] = 'column_info';
        $GLOBALS['cfg']['Server']['pdf_pages'] = 'pdf_pages';
        $GLOBALS['cfg']['Server']['history'] = 'history';
        $GLOBALS['cfg']['Server']['recent'] = 'recent';
        $GLOBALS['cfg']['Server']['favorite'] = 'favorite';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = 'table_uiprefs';
        $GLOBALS['cfg']['Server']['tracking'] = 'tracking';
        $GLOBALS['cfg']['Server']['userconfig'] = 'userconfig';
        $GLOBALS['cfg']['Server']['users'] = 'users';
        $GLOBALS['cfg']['Server']['usergroups'] = 'usergroups';
        $GLOBALS['cfg']['Server']['navigationhiding'] = 'navigationhiding';
        $GLOBALS['cfg']['Server']['savedsearches'] = 'savedsearches';
        $GLOBALS['cfg']['Server']['central_columns'] = 'central_columns';
        $GLOBALS['cfg']['Server']['designer_settings'] = 'designer_settings';
        $GLOBALS['cfg']['Server']['export_templates'] = 'pma__export_templates';

        $this->redefineRelation();
        $this->relation = new Relation();
    }


    /**
     * functions for redefine RelationCleanupDbiMock
     *
     * @return void
     */
    public function redefineRelation()
    {
        $GLOBALS['dbi'] = new RelationCleanupDbiMock();
        unset($_SESSION['relation'][$GLOBALS['server']]);
    }

    /**
     * Test for RelationCleanup::column
     *
     * @return void
     * @group medium
     */
    public function testPMARelationsCleanupColumn()
    {
        $db = "PMA";
        $table = "PMA_bookmark";
        $column = "name";
        $this->redefineRelation();

        //the $cfgRelation value before cleanup column
        $cfgRelation = $this->relation->checkRelationsParam();
        $this->assertEquals(
            true,
            $cfgRelation['commwork']
        );
        //validate Relation::getDbComments when commwork = true
        $db_comments = $this->relation->getDbComments();
        $this->assertEquals(
            array('db_name0' => 'comment0','db_name1' => 'comment1'),
            $db_comments
        );

        $this->assertEquals(
            true,
            $cfgRelation['displaywork']
        );
        //validate Relation::getDisplayField when displaywork = true
        $display_field = $this->relation->getDisplayField($db, $table);
        $this->assertEquals(
            'PMA_display_field',
            $display_field
        );
        $this->assertEquals(
            true,
            $cfgRelation['relwork']
        );
        $this->assertEquals(
            'column_info',
            $cfgRelation['column_info']
        );
        $this->assertEquals(
            'table_info',
            $cfgRelation['table_info']
        );
        $this->assertEquals(
            'relation',
            $cfgRelation['relation']
        );

        //cleanup
        RelationCleanup::column($db, $table, $column);

        //the $cfgRelation value after cleanup column
        $cfgRelation = $this->relation->checkRelationsParam();

        $is_defined_column_info
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;

        $this->assertEquals(
            null,
            $is_defined_column_info
        );
        $this->assertEquals(
            null,
            $is_defined_table_info
        );
        $this->assertEquals(
            null,
            $is_defined_relation
        );

    }

    /**
     * Test for RelationCleanup::table
     *
     * @return void
     */
    public function testPMARelationsCleanupTable()
    {
        $db = "PMA";
        $table = "PMA_bookmark";
        $this->redefineRelation();

        //the $cfgRelation value before cleanup column
        $cfgRelation = $this->relation->checkRelationsParam();
        $this->assertEquals(
            'column_info',
            $cfgRelation['column_info']
        );
        $this->assertEquals(
            'table_info',
            $cfgRelation['table_info']
        );
        $this->assertEquals(
            'table_coords',
            $cfgRelation['table_coords']
        );
        $this->assertEquals(
            'relation',
            $cfgRelation['relation']
        );

        //RelationCleanup::table
        RelationCleanup::table($db, $table);

        //the $cfgRelation value after cleanup column
        $cfgRelation = $this->relation->checkRelationsParam();

        $is_defined_column_info
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        $is_defined_table_coords
            = isset($cfgRelation['table_coords'])
            ? $cfgRelation['table_coords']
            : null;

        $this->assertEquals(
            null,
            $is_defined_column_info
        );
        $this->assertEquals(
            null,
            $is_defined_table_info
        );
        $this->assertEquals(
            null,
            $is_defined_relation
        );
        $this->assertEquals(
            null,
            $is_defined_table_coords
        );
    }

    /**
     * Test for RelationCleanup::database
     *
     * @return void
     */
    public function testPMARelationsCleanupDatabase()
    {
        $db = "PMA";
        $this->redefineRelation();

        //the $cfgRelation value before cleanup column
        $cfgRelation = $this->relation->checkRelationsParam();
        $this->assertEquals(
            'column_info',
            $cfgRelation['column_info']
        );
        $this->assertEquals(
            'bookmark',
            $cfgRelation['bookmark']
        );
        $this->assertEquals(
            'table_info',
            $cfgRelation['table_info']
        );
        $this->assertEquals(
            'pdf_pages',
            $cfgRelation['pdf_pages']
        );
        $this->assertEquals(
            'table_coords',
            $cfgRelation['table_coords']
        );
        $this->assertEquals(
            'relation',
            $cfgRelation['relation']
        );

        //cleanup
        RelationCleanup::database($db);

        //the value after cleanup column
        $cfgRelation = $this->relation->checkRelationsParam();

        $is_defined_column_info
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        $is_defined_table_coords
            = isset($cfgRelation['table_coords'])
            ? $cfgRelation['table_coords']
            : null;

        $this->assertEquals(
            null,
            $is_defined_column_info
        );
        $this->assertEquals(
            null,
            $is_defined_table_info
        );
        $this->assertEquals(
            null,
            $is_defined_relation
        );
        $this->assertEquals(
            null,
            $is_defined_table_coords
        );
    }
}
