<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for relation_cleanup.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */


require_once 'libraries/database_interface.inc.php';

require_once 'libraries/relation.lib.php';
require_once 'libraries/relation_cleanup.lib.php';

use PMA\libraries\DatabaseInterface;

/**
 * PMA_Relation_Cleanup_Test class
 *
 * this class is for testing relation_cleanup.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_Relation_Cleanup_Test extends PHPUnit_Framework_TestCase
{
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
    }


    /**
     * functions for redefine DBI_PMA_Relation_Cleanup
     *
     * @return void
     */
    public function redefineRelation()
    {
        $GLOBALS['dbi'] = new DBI_PMA_Relation_Cleanup();
        unset($_SESSION['relation'][$GLOBALS['server']]);
    }

    /**
     * Test for PMA_relationsCleanupColumn
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
        $cfgRelation = PMA_checkRelationsParam();
        $this->assertEquals(
            true,
            $cfgRelation['commwork']
        );
        //validate PMA_getDbComments when commwork = true
        $db_comments = PMA_getDbComments();
        $this->assertEquals(
            array('db_name0' => 'comment0','db_name1' => 'comment1'),
            $db_comments
        );

        $this->assertEquals(
            true,
            $cfgRelation['displaywork']
        );
        //validate PMA_getDisplayField when displaywork = true
        $display_field = PMA_getDisplayField($db, $table);
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
        PMA_relationsCleanupColumn($db, $table, $column);

        //the $cfgRelation value after cleanup column
        $cfgRelation = PMA_checkRelationsParam();

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
     * Test for PMA_relationsCleanupTable
     *
     * @return void
     */
    public function testPMARelationsCleanupTable()
    {
        $db = "PMA";
        $table = "PMA_bookmark";
        $this->redefineRelation();

        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_checkRelationsParam();
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

        //PMA_relationsCleanupTable
        PMA_relationsCleanupTable($db, $table);

        //the $cfgRelation value after cleanup column
        $cfgRelation = PMA_checkRelationsParam();

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
     * Test for PMA_relationsCleanupDatabase
     *
     * @return void
     */
    public function testPMARelationsCleanupDatabase()
    {
        $db = "PMA";
        $this->redefineRelation();

        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_checkRelationsParam();
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
        PMA_relationsCleanupDatabase($db);

        //the value after cleanup column
        $cfgRelation = PMA_checkRelationsParam();

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

/**
 * DBI_PMA_Relation_Cleanup for Mock DBI class
 *
 * this class is for Mock DBI
 *
 * @package PhpMyAdmin-test
 */
class DBI_PMA_Relation_Cleanup extends DatabaseInterface
{
    var $index;
    var $assocIndex;
    var $totalNum;
    var $values = array();
    var $indexs = array();

    /**
     * Constructor
     *
     */
    public function __construct()
    {
         $this->index = 0;
         $this->assocIndex = 0;
         $this->totalNum = 2;
         $this->values = array(
             'bookmark',
             'relation',
             'table_info',
             'table_coords',
             'column_info',
             'pdf_pages',
             'history',
             'recent',
             'table_uiprefs',
             'tracking',
             'userconfig',
             'users',
             'usergroups',
             'navigationhiding',
         );
         $this->indexs = array(
             'bookmark' => 0,
             'relation' => 1,
             'table_info' => 2,
             'table_coords' => 3,
             'column_info' => 4,
             'pdf_pages' => 5,
             'history' => 6,
             'recent' => 7,
             'table_uiprefs' => 8,
             'tracking' => 9,
             'userconfig' => 10,
             'users' => 11,
             'usergroups' => 12,
             'navigationhiding' => 13,
         );
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    function fetchRow($result)
    {
        $curr_table = array();
        if ($this->index < count($this->values)) {
            $curr_table[0] = $this->values[$this->index];
            $this->index++;
            return $curr_table;
        }

        $this->index = 0;
        return false;
    }


    /**
     * runs a query
     *
     * @param string $sql                 SQL query to execute
     * @param mixed  $link                optional database link to use
     * @param int    $options             optional query options
     * @param bool   $cache_affected_rows whether to cache affected rows
     *
     * @return mixed
     */
    function query($sql, $link = null, $options = 0, $cache_affected_rows = true)
    {
        if (mb_stripos($sql, "column_info") !== false) {
            unset($this->values[$this->indexs['column_info']]);
        }

        if (mb_stripos($sql, "table_info") !== false) {
            unset($this->values[$this->indexs['table_info']]);
        }

        if (mb_stripos($sql, "table_coords") !== false) {
            unset($this->values[$this->indexs['table_coords']]);
        }

        if (mb_stripos($sql, "relation") !== false) {
            unset($this->values[$this->indexs['relation']]);
        }

        if (mb_stripos($sql, "pdf_pages") !== false) {
            unset($GLOBALS [$this->indexs['pdf_pages']]);
        }

        if (mb_stripos($sql, "bookmark") !== false) {
            unset($GLOBALS [$this->indexs['bookmark']]);
        }
        return true;
    }

    /**
     * runs a query and returns the result
     *
     * @param string   $query               query to run
     * @param resource $link                mysql link resource
     * @param integer  $options             query options
     * @param bool     $cache_affected_rows whether to cache affected row
     *
     * @return mixed
     */
    public function tryQuery(
        $query, $link = null, $options = 0, $cache_affected_rows = true
    ) {
        return true;
    }

    /**
     * selects given database
     *
     * @param string $dbname database name to select
     * @param object $link   connection object
     *
     * @return boolean
     */
    public function selectDb($dbname, $link = null)
    {
        return true;
    }

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     *
     * @return bool
     */
    public function freeResult($result)
    {
        return true;
    }

    /**
     * returns only the first row from the result
     *
     * <code>
     * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
     * $user = $GLOBALS['dbi']->fetchSingleRow($sql);
     * // produces
     * // $user = array('id' => 123, 'name' => 'John Doe')
     * </code>
     *
     * @param string|mysql_result $result query or mysql result
     * @param string              $type   NUM|ASSOC|BOTH
     *                                    returned array should either numeric
     *                                    associative or booth
     * @param resource            $link   mysql link
     *
     * @return array|boolean first row from result
     *                       or false if result is empty
     */
    public function fetchSingleRow($result, $type = 'ASSOC', $link = null)
    {
        return array(
            'display_field' => "PMA_display_field"
        );
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        $assocResult = array();
        if ($this->assocIndex < $this->totalNum) {
            $assocResult['db_name'] = "db_name" . $this->assocIndex;
            $assocResult['comment'] = "comment" . $this->assocIndex;
            $this->assocIndex++;
            return $assocResult;
        }

        $this->assocIndex = 0;
        return false;
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result)
    {
        return $this->totalNum;
    }
}
